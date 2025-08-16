# Mobile Social Login Setup Guide

This guide will help you set up Google and Apple Sign-In for your mobile application.

## 1. Database Setup

### Run the Migration
```bash
php artisan migrate
```

This will add the following fields to your `users` table:
- `google_id` - Stores Google user ID
- `apple_id` - Stores Apple user ID

## 2. Environment Configuration

Add the following variables to your `.env` file:

### Google Configuration
```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=your_redirect_uri
```

### Apple Configuration
```env
APPLE_CLIENT_ID=your_apple_client_id
APPLE_CLIENT_SECRET=your_apple_client_secret
APPLE_REDIRECT_URI=your_redirect_uri
APPLE_KEY_ID=your_apple_key_id
APPLE_TEAM_ID=your_apple_team_id
```

## 3. Google Setup

### 3.1 Create Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API and Google Identity API

### 3.2 Configure OAuth 2.0
1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth 2.0 Client IDs"
3. Choose "Web application" or "Android/iOS" based on your app type
4. Add authorized redirect URIs
5. Copy the Client ID and Client Secret

### 3.3 Mobile App Integration
For Android:
```kotlin
// Add to your Android app
implementation 'com.google.android.gms:play-services-auth:20.7.0'
```

For iOS:
```swift
// Add to your iOS app
// Use Google Sign-In SDK
```

## 4. Apple Setup

### 4.1 Apple Developer Account
1. Go to [Apple Developer Portal](https://developer.apple.com/)
2. Create an App ID with Sign In with Apple capability
3. Create a Services ID for your web application

### 4.2 Generate Private Key
1. Go to "Keys" section
2. Create a new key with "Sign In with Apple" capability
3. Download the private key (.p8 file)
4. Note the Key ID and Team ID

### 4.3 Mobile App Integration
For Android:
```kotlin
// Use Apple's Sign In with Apple SDK
implementation 'com.apple.android.sdk:signin:1.0.0'
```

For iOS:
```swift
// Use Apple's Sign In with Apple framework
import AuthenticationServices
```

## 5. API Endpoints

### Google Login
```
POST /api/mobile/auth/login/google
```

**Request Body:**
```json
{
    "id_token": "google_id_token",
    "access_token": "google_access_token",
    "name": "User Name", // optional
    "email": "user@example.com", // optional
    "profile_image": "https://example.com/image.jpg" // optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Google login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "User Name",
            "email": "user@example.com",
            "profile_image": "https://example.com/image.jpg",
            "account_status": "active",
            "account_verification": "yes",
            "subscription_plan": "rookie"
        },
        "token": "1|abc123...",
        "is_new_user": false
    }
}
```

### Apple Login
```
POST /api/mobile/auth/login/apple
```

**Request Body:**
```json
{
    "identity_token": "apple_identity_token",
    "authorization_code": "apple_authorization_code",
    "name": "User Name", // optional
    "email": "user@example.com", // optional
    "profile_image": "https://example.com/image.jpg" // optional
}
```

**Response:**
```json
{
    "success": true,
    "message": "Apple login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "User Name",
            "email": "user@example.com",
            "profile_image": "https://example.com/image.jpg",
            "account_status": "active",
            "account_verification": "yes",
            "subscription_plan": "rookie"
        },
        "token": "1|abc123...",
        "is_new_user": false
    }
}
```

## 6. Mobile App Implementation

### 6.1 Android (Kotlin)
```kotlin
// Google Sign-In
private fun signInWithGoogle() {
    val gso = GoogleSignInOptions.Builder(GoogleSignInOptions.DEFAULT_SIGN_IN)
        .requestIdToken(getString(R.string.google_client_id))
        .requestEmail()
        .build()

    val googleSignInClient = GoogleSignIn.getClient(this, gso)
    val signInIntent = googleSignInClient.signInIntent
    startActivityForResult(signInIntent, RC_SIGN_IN)
}

override fun onActivityResult(requestCode: Int, resultCode: Int, data: Intent?) {
    super.onActivityResult(requestCode, resultCode, data)

    if (requestCode == RC_SIGN_IN) {
        val task = GoogleSignIn.getSignedInAccountFromIntent(data)
        try {
            val account = task.getResult(ApiException::class.java)
            val idToken = account.idToken
            val accessToken = account.id
            
            // Send to your API
            loginWithGoogle(idToken, accessToken, account.displayName, account.email)
        } catch (e: ApiException) {
            // Handle error
        }
    }
}

private fun loginWithGoogle(idToken: String, accessToken: String, name: String?, email: String?) {
    val requestBody = JSONObject().apply {
        put("id_token", idToken)
        put("access_token", accessToken)
        put("name", name)
        put("email", email)
    }

    // Make API call to your backend
    apiService.loginWithGoogle(requestBody.toString())
        .enqueue(object : Callback<LoginResponse> {
            override fun onResponse(call: Call<LoginResponse>, response: Response<LoginResponse>) {
                if (response.isSuccessful) {
                    // Handle successful login
                    val token = response.body()?.data?.token
                    // Store token and navigate to main screen
                }
            }

            override fun onFailure(call: Call<LoginResponse>, t: Throwable) {
                // Handle error
            }
        })
}
```

### 6.2 iOS (Swift)
```swift
// Google Sign-In
import GoogleSignIn

func signInWithGoogle() {
    guard let presentingViewController = self else { return }
    
    GIDSignIn.sharedInstance.signIn(withPresenting: presentingViewController) { [weak self] result, error in
        guard let self = self else { return }
        
        if let error = error {
            // Handle error
            return
        }
        
        guard let user = result?.user,
              let idToken = user.idToken?.tokenString else {
            return
        }
        
        // Send to your API
        self.loginWithGoogle(
            idToken: idToken,
            accessToken: user.accessToken.tokenString,
            name: user.profile?.name,
            email: user.profile?.email
        )
    }
}

func loginWithGoogle(idToken: String, accessToken: String, name: String?, email: String?) {
    let parameters: [String: Any] = [
        "id_token": idToken,
        "access_token": accessToken,
        "name": name ?? "",
        "email": email ?? ""
    ]
    
    // Make API call to your backend
    APIService.shared.loginWithGoogle(parameters: parameters) { [weak self] result in
        DispatchQueue.main.async {
            switch result {
            case .success(let response):
                // Handle successful login
                let token = response.data.token
                // Store token and navigate to main screen
            case .failure(let error):
                // Handle error
            }
        }
    }
}

// Apple Sign-In
import AuthenticationServices

func signInWithApple() {
    let request = ASAuthorizationAppleIDProvider().createRequest()
    request.requestedScopes = [.fullName, .email]
    
    let authorizationController = ASAuthorizationController(authorizationRequests: [request])
    authorizationController.delegate = self
    authorizationController.presentationContextProvider = self
    authorizationController.performRequests()
}

extension YourViewController: ASAuthorizationControllerDelegate {
    func authorizationController(controller: ASAuthorizationController, didCompleteWithAuthorization authorization: ASAuthorization) {
        if let appleIDCredential = authorization.credential as? ASAuthorizationAppleIDCredential {
            guard let identityToken = appleIDCredential.identityToken,
                  let identityTokenString = String(data: identityToken, encoding: .utf8) else {
                return
            }
            
            let authorizationCode = appleIDCredential.authorizationCode
            let authorizationCodeString = String(data: authorizationCode, encoding: .utf8) ?? ""
            
            // Send to your API
            loginWithApple(
                identityToken: identityTokenString,
                authorizationCode: authorizationCodeString,
                name: appleIDCredential.fullName?.formatted(),
                email: appleIDCredential.email
            )
        }
    }
    
    func authorizationController(controller: ASAuthorizationController, didCompleteWithError error: Error) {
        // Handle error
    }
}

func loginWithApple(identityToken: String, authorizationCode: String, name: String?, email: String?) {
    let parameters: [String: Any] = [
        "identity_token": identityToken,
        "authorization_code": authorizationCode,
        "name": name ?? "",
        "email": email ?? ""
    ]
    
    // Make API call to your backend
    APIService.shared.loginWithApple(parameters: parameters) { [weak self] result in
        DispatchQueue.main.async {
            switch result {
            case .success(let response):
                // Handle successful login
                let token = response.data.token
                // Store token and navigate to main screen
            case .failure(let error):
                // Handle error
            }
        }
    }
}
```

## 7. Security Considerations

### 7.1 Token Verification
- Always verify tokens on the server side
- Use HTTPS for all API calls
- Implement proper error handling
- Log authentication attempts for security monitoring

### 7.2 Data Privacy
- Only request necessary scopes
- Handle user data according to privacy policies
- Implement proper data deletion when users delete their accounts

### 7.3 Error Handling
- Handle network errors gracefully
- Provide clear error messages to users
- Implement retry mechanisms for failed requests

## 8. Testing

### 8.1 Test Cases
1. **New User Registration**: Test creating new users via social login
2. **Existing User Login**: Test logging in existing users
3. **Token Validation**: Test with invalid tokens
4. **Network Errors**: Test behavior when network is unavailable
5. **User Cancellation**: Test when user cancels the sign-in process

### 8.2 Test Environment
- Use test accounts for development
- Set up separate OAuth applications for testing
- Use staging environment for integration testing

## 9. Production Deployment

### 9.1 Checklist
- [ ] Update environment variables with production values
- [ ] Configure proper redirect URIs
- [ ] Set up SSL certificates
- [ ] Configure proper logging
- [ ] Set up monitoring and alerting
- [ ] Test with real user accounts
- [ ] Review security settings

### 9.2 Monitoring
- Monitor authentication success/failure rates
- Track user registration via social login
- Monitor API response times
- Set up alerts for authentication failures

## 10. Troubleshooting

### Common Issues
1. **Invalid Client ID**: Ensure the client ID matches your OAuth configuration
2. **Redirect URI Mismatch**: Verify redirect URIs are correctly configured
3. **Token Expiration**: Handle token refresh properly
4. **Network Issues**: Implement proper retry logic

### Debug Tips
- Enable detailed logging in development
- Use browser developer tools to inspect network requests
- Test with different user accounts
- Verify all environment variables are set correctly 