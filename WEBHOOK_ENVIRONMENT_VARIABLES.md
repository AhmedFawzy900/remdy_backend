# In-App Purchase Webhook Environment Variables

Add the following environment variables to your `.env` file to configure the in-app purchase webhook system:

## Apple App Store Configuration

```env
# Apple In-App Purchase Webhook Configuration
APPLE_SHARED_SECRET=your_apple_shared_secret_here
APPLE_WEBHOOK_URL=${APP_URL}/api/webhooks/apple
APPLE_SANDBOX_MODE=true

# Existing Apple OAuth (if not already configured)
APPLE_CLIENT_ID=your_apple_client_id
APPLE_CLIENT_SECRET=your_apple_client_secret
APPLE_REDIRECT_URI=your_apple_redirect_uri
APPLE_KEY_ID=your_apple_key_id
APPLE_TEAM_ID=your_apple_team_id
APPLE_PRIVATE_KEY_PATH=storage/app/private/AuthKey_YOUR_KEY_ID.p8
```

## Google Play Store Configuration

```env
# Google Play In-App Purchase Webhook Configuration
GOOGLE_PLAY_CREDENTIALS_PATH=storage/app/remdy-9668a-4bba7c728033.json
GOOGLE_WEBHOOK_SECRET=your_google_webhook_secret_here
GOOGLE_WEBHOOK_URL=${APP_URL}/api/webhooks/google
GOOGLE_PACKAGE_NAME=com.yourapp.package

# Existing Google OAuth (if not already configured)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=your_google_redirect_uri
```

## Setup Instructions

### Apple App Store Setup

1. **Get Shared Secret:**
   - Go to App Store Connect
   - Navigate to your app → App Information
   - Scroll to App-Specific Shared Secret
   - Generate or copy the shared secret
   - Set `APPLE_SHARED_SECRET` in your .env file

2. **Configure Webhook URL:**
   - In App Store Connect, go to your app settings
   - Set the webhook URL to: `https://yourdomain.com/api/webhooks/apple`
   - The webhook will receive notifications for subscription events

3. **Private Key:**
   - Ensure your Apple private key file is in `storage/app/private/`
   - Update `APPLE_PRIVATE_KEY_PATH` if the path is different

### Google Play Store Setup

1. **Service Account Credentials:**
   - Go to Google Play Console
   - Navigate to Setup → API access
   - Create or use existing service account
   - Download the JSON credentials file
   - Place it in `storage/app/` and update `GOOGLE_PLAY_CREDENTIALS_PATH`

2. **Configure Pub/Sub Topic:**
   - In Google Play Console, go to Monetization → Subscriptions
   - Set up Real-time developer notifications
   - Configure the endpoint URL to: `https://yourdomain.com/api/webhooks/google`
   - Set up the Pub/Sub topic and subscription

3. **Package Name:**
   - Set `GOOGLE_PACKAGE_NAME` to your app's package name (e.g., com.yourapp.package)

## Webhook Endpoints

The following webhook endpoints are available (no authentication required):

- **Apple:** `POST /api/webhooks/apple`
- **Google:** `POST /api/webhooks/google`
- **Test:** `POST /api/webhooks/test` (for development/testing)

## Testing

Use the test webhook endpoint for development:

```bash
curl -X POST http://localhost:8000/api/webhooks/test \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "test",
    "user_id": 1,
    "action": "downgrade"
  }'
```

## Security Notes

1. **Production Environment:**
   - Set `APPLE_SANDBOX_MODE=false` in production
   - Implement proper JWT signature verification for Apple webhooks
   - Implement proper Pub/Sub message verification for Google webhooks

2. **Webhook Security:**
   - The webhook endpoints are intentionally without authentication middleware
   - Signature verification is implemented in the controller methods
   - All webhook events are logged for monitoring

3. **SSL Required:**
   - Both Apple and Google require HTTPS endpoints for webhooks
   - Ensure your production environment has valid SSL certificates

## Supported Events

### Apple App Store Events (Auto-downgrade to rookie plan):
- `EXPIRED` - Subscription expired
- `DID_FAIL_TO_RENEW` - Renewal failed
- `GRACE_PERIOD_EXPIRED` - Grace period ended
- `REFUND` - Subscription refunded
- `REVOKE` - Subscription revoked
- `DID_CHANGE_RENEWAL_STATUS` - Auto-renewal disabled

### Google Play Events (Auto-downgrade to rookie plan):
- `3` - SUBSCRIPTION_CANCELED
- `5` - SUBSCRIPTION_ON_HOLD
- `12` - SUBSCRIPTION_REVOKED
- `13` - SUBSCRIPTION_EXPIRED

## Logging

All webhook events are logged to `storage/logs/laravel.log` with the following information:
- Webhook payload
- Processing results
- User downgrade actions
- Error details (if any)

Monitor these logs to track webhook processing and troubleshoot issues.
