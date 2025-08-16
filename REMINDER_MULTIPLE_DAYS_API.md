# Reminder Multiple Days API

## Overview

The reminder system has been updated to support multiple days per reminder. Instead of a single day, reminders can now be set for multiple days of the week.

## Database Changes

- Changed `day` column to `days` (JSON column)
- Updated unique constraint to allow same element/time combinations
- Added data migration for existing reminders

## API Changes

### Create Reminder

**Endpoint:** `POST /api/mobile/reminders`

**Request Body:**
```json
{
    "element_type": "remedy|article|course|video",
    "element_id": 1,
    "days": ["monday", "wednesday", "friday"],  // Array of days or null for all days
    "time": "09:00"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reminder created successfully",
    "data": {
        "id": 1,
        "element_type": "remedy",
        "element": { /* element data */ },
        "days": ["monday", "wednesday", "friday"],
        "day_names": "Monday, Wednesday, Friday",
        "time": "09:00",
        "formatted_time": "9:00 AM",
        "is_active": true,
        "created_at": "2025-08-15T10:00:00.000000Z"
    }
}
```

### Update Reminder

**Endpoint:** `PUT /api/mobile/reminders/{id}`

**Request Body:**
```json
{
    "days": ["tuesday", "thursday"],  // Array of days or null for all days
    "time": "10:00",
    "is_active": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "Reminder updated successfully",
    "data": {
        "id": 1,
        "element_type": "remedy",
        "element": { /* element data */ },
        "days": ["tuesday", "thursday"],
        "day_names": "Tuesday, Thursday",
        "time": "10:00",
        "formatted_time": "10:00 AM",
        "is_active": true,
        "updated_at": "2025-08-15T10:00:00.000000Z"
    }
}
```

### Get Reminders

**Endpoint:** `GET /api/mobile/reminders`

**Response:**
```json
{
    "success": true,
    "message": "Reminders retrieved successfully",
    "data": [
        {
            "id": 1,
            "element_type": "remedy",
            "element": { /* element data */ },
            "days": ["monday", "wednesday", "friday"],
            "day_names": "Monday, Wednesday, Friday",
            "time": "09:00",
            "formatted_time": "9:00 AM",
            "is_active": true,
            "created_at": "2025-08-15T10:00:00.000000Z"
        }
    ],
    "pagination": { /* pagination data */ }
}
```

## Validation Rules

- `days`: Optional array of strings
- `days.*`: Must be one of: `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`, `sunday`
- `time`: Required time in `H:i` format (e.g., "09:00")

## Examples

### Single Day Reminder
```json
{
    "element_type": "remedy",
    "element_id": 1,
    "days": ["monday"],
    "time": "09:00"
}
```

### Multiple Days Reminder
```json
{
    "element_type": "remedy",
    "element_id": 1,
    "days": ["monday", "wednesday", "friday"],
    "time": "09:00"
}
```

### All Days Reminder
```json
{
    "element_type": "remedy",
    "element_id": 1,
    "days": null,
    "time": "09:00"
}
```

## Migration Notes

1. Run the database migrations:
   ```bash
   php artisan migrate
   ```

2. Existing reminders with single days will be automatically converted to the new format.

3. The `day_names` attribute provides a human-readable format of the days.

## Model Methods

### New Scopes
- `scopeForDay($query, $day)`: Filter reminders for a specific day
- `scopeForAllDays($query)`: Filter reminders for all days (null days)
- `scopeForAnyDay($query, $days)`: Filter reminders that have any of the specified days

### New Attributes
- `day_names`: Returns formatted day names (e.g., "Monday, Wednesday, Friday")
- `day_name`: Backward compatibility attribute

## Breaking Changes

- The `day` field in responses is now `days` (array)
- The `day_name` field is now `day_names` (formatted string)
- Unique constraint now allows same element/time combinations (since days can vary) 