# Reminder Notifications Cron Job Setup

This document explains how to set up automated reminder notifications that will be sent to users when their reminder time comes.

## Overview

The system includes:
- A Laravel command `reminders:send-notifications` that checks for active reminders matching the current time and day
- Automatic scheduling to run every minute
- Integration with FCM (Firebase Cloud Messaging) to send push notifications
- OutNotification records for tracking sent notifications

## How It Works

1. **Command Execution**: The `reminders:send-notifications` command runs every minute
2. **Reminder Matching**: It finds active reminders where:
   - The reminder time matches the current time (HH:MM:SS)
   - The reminder day matches the current day (or is set to "all days")
   - The reminder is marked as active (`is_active = true`)
3. **Notification Sending**: For each matching reminder:
   - Gets the user's device tokens
   - Creates an OutNotification record
   - Sends FCM push notification to all user devices
   - Logs the activity for monitoring

## Server Setup

### 1. Laravel Scheduler Setup

The command is already scheduled in `bootstrap/app.php` to run every minute. To activate it on your server, you need to add a single cron job entry:

```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path/to/your/project` with the actual path to your Laravel project.

### 2. Adding the Cron Job

#### On Linux/Unix servers:

1. Open the crontab editor:
   ```bash
   crontab -e
   ```

2. Add the following line:
   ```bash
   * * * * * cd /path/to/your/remedies/project && php artisan schedule:run >> /dev/null 2>&1
   ```

3. Save and exit the editor.

#### On shared hosting:

Most shared hosting providers have a cron job management interface in their control panel. Add the same command there.

### 3. Verify Setup

You can verify the setup by running:

```bash
php artisan schedule:list
```

This should show:
```
* * * * *  php artisan reminders:send-notifications .................... Next Due: X seconds from now
```

## Testing

### Manual Testing

You can manually test the command:

```bash
php artisan reminders:send-notifications
```

This will:
- Show current time and day
- Display how many matching reminders were found
- Log each notification sent
- Show total notifications sent

### Test with Sample Data

To test with sample reminders:

1. Create a test reminder in the database with:
   - Current time (or a time a few minutes from now)
   - Current day in the `days` JSON field
   - `is_active = true`
   - Valid `user_id` with device tokens

2. Wait for the scheduled time or run the command manually

3. Check the `out_notifications` table for new records

4. Verify FCM notifications are received on the user's device

## Monitoring

### Logs

The command logs its activity. You can monitor it by checking:

```bash
tail -f storage/logs/laravel.log
```

Look for:
- "Starting reminder notifications job..."
- "Found X matching reminders"
- "Sent reminder notification to user X"
- "Reminder notifications job completed. Sent X notifications."

### Error Handling

The system handles various error scenarios:
- Users without device tokens (logs warning, skips)
- Missing reminder elements (uses fallback messages)
- FCM sending failures (logs errors, continues with other reminders)
- Database connection issues (Laravel's standard error handling)

## Notification Content

### Title
- "Remedy Reminder" for remedy reminders
- "Course Reminder" for course reminders  
- "Lesson Reminder" for lesson reminders
- "Reminder" for other types

### Body
- Remedy: "Don't forget to take your remedy: [remedy name]"
- Course: "Time to continue your course: [course title]"
- Lesson: "Time for your lesson: [lesson title]"
- Generic: "Reminder: [item name]"

### Action URLs
- Remedies: `/remedies/{id}`
- Courses: `/courses/{id}`
- Lessons: `/lessons/{id}`

## Performance Considerations

- The command uses `withoutOverlapping()` to prevent multiple instances running simultaneously
- Database queries are optimized with proper indexing on time and day fields
- The command runs in background mode for better performance
- Only active reminders are processed to reduce load

## Troubleshooting

### Command Not Running
1. Verify cron job is properly set up
2. Check server time zone matches application time zone
3. Ensure PHP path is correct in cron job
4. Check file permissions on project directory

### No Notifications Sent
1. Verify reminders exist with matching time/day
2. Check users have valid device tokens
3. Verify FCM configuration is correct
4. Check Laravel logs for errors

### Performance Issues
1. Monitor database query performance
2. Consider adding database indexes on reminder fields
3. Check server resource usage during peak times
4. Consider optimizing the command for large datasets

## Database Schema Requirements

Ensure these tables and columns exist:

### reminders table
- `time` (time field)
- `days` (JSON field for multiple days)
- `day` (string field for backward compatibility)
- `is_active` (boolean)
- `user_id` (foreign key)
- `element_type` and `element_id` (polymorphic relationship)

### device_tokens table
- `user_id` (foreign key)
- `token` (FCM device token)

### out_notifications table
- All fields as defined in the OutNotification model

## Security Considerations

- FCM tokens are sensitive data - ensure proper encryption at rest
- Monitor for unusual notification patterns that might indicate abuse
- Consider rate limiting for users with many reminders
- Regularly clean up old OutNotification records to manage database size
