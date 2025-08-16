# Mobile Social Login API Documentation

## Overview

This document provides the API endpoints and implementation details for Google and Apple Sign-In integration in your mobile application.

## Base URL
```
https://your-domain.com/api/mobile/auth
```

## Authentication Flow

### 1. Google Sign-In

#### Endpoint
```
POST /login/google
```

#### Request Headers
```
Content-Type: application/json
Accept: application/json
```

#### Request Body
```json
{
    "id_token": "google_id_token_from_mobile_sdk",
    "access_token": "google_access_token_from_mobile_sdk",
    "name": "User Name", // optional
    "email": "user@example.com", // optional
    "profile_image": "https://example.com/image.jpg" // optional
}
```

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Google login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "User Name",
            "full_name": "User Name",
            "email": "user@example.com",
            "profile_image": "https://example.com/image.jpg",
            "phone": null,
            "account_status": "active",
            "account_verification": "yes",
            "subscription_plan": "rookie",
            "google_id": "123456789",
            "created_at": "2025-01-28T10:00:00.000000Z",
            "updated_at": "2025-01-28T10:00:00.000000Z"
        },
        "token": "1|abc123def456ghi789...",
        "is_new_user": false
    }
}
```

#### Response (Error - 401)
```json
{
    "success": false,
    "message": "Invalid Google token"
}
```

#### Response (Error - 422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "id_token": ["The id token field is required."],
        "access_token": ["The access token field is required."]
    }
}
```

### 2. Apple Sign-In

#### Endpoint
```
POST /login/apple
```

#### Request Headers
```
Content-Type: application/json
Accept: application/json
```

#### Request Body
```json
{
    "identity_token": "apple_identity_token_from_mobile_sdk",
    "authorization_code": "apple_authorization_code_from_mobile_sdk",
    "name": "User Name", // optional
    "email": "user@example.com", // optional
    "profile_image": "https://example.com/image.jpg" // optional
}
```

#### Response (Success - 200)
```json
{
    "success": true,
    "message": "Apple login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "User Name",
            "full_name": "User Name",
            "email": "user@example.com",
            "profile_image": "https://example.com/image.jpg",
            "phone": null,
            "account_status": "active",
            "account_verification": "yes",
            "subscription_plan": "rookie",
            "apple_id": "apple_123456",
            "created_at": "2025-01-28T10:00:00.000000Z",
            "updated_at": "2025-01-28T10:00:00.000000Z"
        },
        "token": "1|abc123def456ghi789...",
        "is_new_user": true
    }
}
```

#### Response (Error - 401)
```json
{
    "success": false,
    "message": "Invalid Apple token"
}
```

#### Response (Error - 422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "identity_token": ["The identity token field is required."],
        "authorization_code": ["The authorization code field is required."]
    }
}
```

## Using the Authentication Token

After successful login, you'll receive a Bearer token. Use this token for all subsequent API requests:

```
Authorization: Bearer 1|abc123def456ghi789...
```

## Error Handling

### Common Error Codes
- `200` - Success
- `401` - Unauthorized (Invalid token)
- `422` - Validation Error
- `500` - Server Error

### Error Response Format
```json
{
    "success": false,
    "message": "Error description",
    "error": "Detailed error message (only in development)"
}
```

## Implementation Examples

### Android (Kotlin) - Google Sign-In
```kotlin
class AuthRepository {
    private val apiService: ApiService = // Your API service

    suspend fun loginWithGoogle(idToken: String, accessToken: String, name: String?, email: String?): Result<LoginResponse> {
        return try {
            val requestBody = JSONObject().apply {
                put("id_token", idToken)
                put("access_token", accessToken)
                put("name", name)
                put("email", email)
            }

            val response = apiService.loginWithGoogle(requestBody.toString())
            if (response.isSuccessful) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Login failed: ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}

// Usage in ViewModel
class AuthViewModel : ViewModel() {
    private val authRepository = AuthRepository()

    fun signInWithGoogle(idToken: String, accessToken: String, name: String?, email: String?) {
        viewModelScope.launch {
            val result = authRepository.loginWithGoogle(idToken, accessToken, name, email)
            result.fold(
                onSuccess = { response ->
                    // Store token
                    saveAuthToken(response.data.token)
                    // Navigate to main screen
                    _uiState.value = UiState.Success(response.data.user)
                },
                onFailure = { error ->
                    _uiState.value = UiState.Error(error.message ?: "Login failed")
                }
            )
        }
    }
}
```

### iOS (Swift) - Google Sign-In
```swift
class AuthService {
    private let apiClient: APIClient
    
    init(apiClient: APIClient) {
        self.apiClient = apiClient
    }
    
    func loginWithGoogle(idToken: String, accessToken: String, name: String?, email: String?) async throws -> LoginResponse {
        let parameters: [String: Any] = [
            "id_token": idToken,
            "access_token": accessToken,
            "name": name ?? "",
            "email": email ?? ""
        ]
        
        let request = APIRequest(
            endpoint: "/mobile/auth/login/google",
            method: .POST,
            parameters: parameters
        )
        
        return try await apiClient.perform(request)
    }
}

// Usage in ViewModel
class AuthViewModel: ObservableObject {
    @Published var authState: AuthState = .idle
    private let authService: AuthService
    
    init(authService: AuthService) {
        self.authService = authService
    }
    
    @MainActor
    func signInWithGoogle(idToken: String, accessToken: String, name: String?, email: String?) {
        Task {
            do {
                authState = .loading
                let response = try await authService.loginWithGoogle(
                    idToken: idToken,
                    accessToken: accessToken,
                    name: name,
                    email: email
                )
                
                // Store token
                UserDefaults.standard.set(response.data.token, forKey: "auth_token")
                
                authState = .authenticated(response.data.user)
            } catch {
                authState = .error(error.localizedDescription)
            }
        }
    }
}
```

### iOS (Swift) - Apple Sign-In
```swift
class AuthService {
    func loginWithApple(identityToken: String, authorizationCode: String, name: String?, email: String?) async throws -> LoginResponse {
        let parameters: [String: Any] = [
            "identity_token": identityToken,
            "authorization_code": authorizationCode,
            "name": name ?? "",
            "email": email ?? ""
        ]
        
        let request = APIRequest(
            endpoint: "/mobile/auth/login/apple",
            method: .POST,
            parameters: parameters
        )
        
        return try await apiClient.perform(request)
    }
}
```

## Testing

### Test Endpoints
For testing purposes, you can use these endpoints with mock data:

```bash
# Test Google Login
curl -X POST https://your-domain.com/api/mobile/auth/login/google \
  -H "Content-Type: application/json" \
  -d '{
    "id_token": "test_google_id_token",
    "access_token": "test_access_token",
    "name": "Test User",
    "email": "test@example.com"
  }'

# Test Apple Login
curl -X POST https://your-domain.com/api/mobile/auth/login/apple \
  -H "Content-Type: application/json" \
  -d '{
    "identity_token": "test_apple_identity_token",
    "authorization_code": "test_auth_code",
    "name": "Test User",
    "email": "test@example.com"
  }'
```

## Security Notes

1. **Token Storage**: Store the authentication token securely (Keychain for iOS, EncryptedSharedPreferences for Android)
2. **Token Refresh**: Implement token refresh logic when tokens expire
3. **HTTPS**: Always use HTTPS for API calls
4. **Token Validation**: The server validates all tokens before processing
5. **Error Handling**: Implement proper error handling for network failures

## Support

For technical support or questions about the API:
- Email: support@yourdomain.com
- Documentation: https://yourdomain.com/docs
- API Status: https://status.yourdomain.com 