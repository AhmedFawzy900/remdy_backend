# Required Environment Variables for Laravel In-App Purchase Webhook System

## 🔑 Essential Keys to Add to Your .env File

### Apple App Store Configuration
```env
# Apple In-App Purchase Webhook Configuration
APPLE_SHARED_SECRET=your_apple_shared_secret_from_app_store_connect
APPLE_WEBHOOK_URL=${APP_URL}/api/webhooks/apple
APPLE_SANDBOX_MODE=true

# Apple OAuth (if not already configured)
APPLE_CLIENT_ID=your_apple_client_id
APPLE_CLIENT_SECRET=your_apple_client_secret
APPLE_REDIRECT_URI=your_apple_redirect_uri
APPLE_KEY_ID=your_apple_key_id_from_developer_account
APPLE_TEAM_ID=your_apple_team_id
APPLE_PRIVATE_KEY_PATH=storage/app/private/AuthKey_YOUR_KEY_ID.p8
```

### Google Play Store Configuration
```env
# Google Play In-App Purchase Webhook Configuration
GOOGLE_PLAY_CREDENTIALS_PATH=storage/app/remdy-9668a-4bba7c728033.json
GOOGLE_WEBHOOK_SECRET=your_google_webhook_secret_key
GOOGLE_WEBHOOK_URL=${APP_URL}/api/webhooks/google
GOOGLE_PACKAGE_NAME=com.yourapp.packagename

# Google OAuth (if not already configured)
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=your_google_redirect_uri
```

## 📋 Implementation Summary

✅ **Completed Features:**
- `SubscriptionWebhookController` with Apple/Google webhook handlers
- Automatic user downgrade to 'rookie' plan on subscription events
- Webhook routes without authentication middleware
- Apple/Google service configuration in `config/services.php`
- User model relationships for subscription history
- Comprehensive logging and error handling

## 🔗 Webhook Endpoints

The following endpoints are now available:

| Platform | Endpoint | Method | Authentication |
|----------|----------|---------|----------------|
| Apple | `/api/webhooks/apple` | POST | None (signature verified) |
| Google | `/api/webhooks/google` | POST | None (signature verified) |
| Test | `/api/webhooks/test` | POST | None (for development) |

## 🎯 Supported Downgrade Events

### Apple App Store Events:
- `EXPIRED` - Subscription expired
- `DID_FAIL_TO_RENEW` - Renewal failed
- `GRACE_PERIOD_EXPIRED` - Grace period ended
- `REFUND` - Subscription refunded
- `REVOKE` - Subscription revoked
- `DID_CHANGE_RENEWAL_STATUS` - Auto-renewal disabled

### Google Play Events:
- `3` - SUBSCRIPTION_CANCELED
- `5` - SUBSCRIPTION_ON_HOLD  
- `12` - SUBSCRIPTION_REVOKED
- `13` - SUBSCRIPTION_EXPIRED

## 🧪 Testing the Webhook System

You can test the webhook system using the test endpoint:

```bash
curl -X POST http://localhost:8000/api/webhooks/test \
  -H "Content-Type: application/json" \
  -d '{
    "platform": "test",
    "user_id": 1,
    "action": "downgrade"
  }'
```

## 🔧 Next Steps

1. **Add the required environment variables** to your `.env` file
2. **Configure Apple App Store Connect** webhook URL
3. **Set up Google Play Console** Pub/Sub notifications
4. **Test the webhook endpoints** using the test endpoint
5. **Monitor logs** in `storage/logs/laravel.log` for webhook events

## 🛡️ Security Notes

- Webhook endpoints are intentionally without Laravel's auth middleware
- Signature verification is implemented within the controller methods
- All webhook events are logged for monitoring and debugging
- SSL/HTTPS is required for production webhook endpoints

## 📁 Files Modified/Created

- `app/Http/Controllers/SubscriptionWebhookController.php` - Main webhook controller
- `config/services.php` - Added Apple/Google webhook configuration
- `routes/api.php` - Added webhook routes
- `app/Models/User.php` - Added subscription relationship methods
- `WEBHOOK_ENVIRONMENT_VARIABLES.md` - Detailed setup guide
- `REQUIRED_ENV_KEYS.md` - This summary file

The webhook system is now fully implemented and ready for use! 🚀
