# Lesson Content Blocks API Documentation

## Overview

The Lesson Content Blocks API allows you to manage different types of content blocks within lessons. Each content block has a specific type and structure based on the content it contains.

## Available Content Block Types

### 1. Content (List of image + title)
- **Type**: `content`
- **Description**: Displays a list of items, each with an image and title
- **Structure**:
```json
{
  "type": "content",
  "title": "Content List Title",
  "description": "Optional description",
  "content": {
    "items": [
      {
        "title": "Item 1",
        "image_url": "https://example.com/image1.jpg"
      },
      {
        "title": "Item 2", 
        "image_url": "https://example.com/image2.jpg"
      }
    ]
  },
  "order": 1,
  "is_active": true
}
```

### 2. Text (Rich HTML text)
- **Type**: `text`
- **Description**: Rich text content with HTML support (bold, font size, color, etc.)
- **Structure**:
```json
{
  "type": "text",
  "title": "Text Block Title",
  "description": "Optional description",
  "content": {
    "html_content": "<p><strong>Bold text</strong> with <span style=\"color: red;\">colored text</span> and <span style=\"font-size: 18px;\">larger font</span></p>"
  },
  "order": 2,
  "is_active": true
}
```

### 3. Video (Video link + title)
- **Type**: `video`
- **Description**: Video content with title and video URL
- **Structure**:
```json
{
  "type": "video",
  "title": "Video Title",
  "description": "Video description",
  "video_url": "https://youtube.com/watch?v=example",
  "content": {
    "video_url": "https://youtube.com/watch?v=example",
    "title": "Video Title",
    "description": "Video description"
  },
  "order": 3,
  "is_active": true
}
```

### 4. Remedy (Remedy model)
- **Type**: `remedy`
- **Description**: Links to a specific remedy with full remedy details
- **Structure**:
```json
{
  "type": "remedy",
  "title": "Remedy Block Title",
  "description": "Optional description",
  "remedy_id": 1,
  "content": {
    "remedy_id": 1
  },
  "order": 4,
  "is_active": true
}
```

### 5. Tip (Image + rich text)
- **Type**: `tip`
- **Description**: Tip content with image and rich text
- **Structure**:
```json
{
  "type": "tip",
  "title": "Tip Title",
  "description": "Optional description",
  "image_url": "https://example.com/tip-image.jpg",
  "content": {
    "image_url": "https://example.com/tip-image.jpg",
    "html_content": "<p>This is a helpful tip with <strong>bold text</strong> and <span style=\"color: blue;\">colored text</span></p>"
  },
  "order": 5,
  "is_active": true
}
```

### 6. Image (Image + optional URL)
- **Type**: `image`
- **Description**: Single image with optional link URL
- **Structure**:
```json
{
  "type": "image",
  "title": "Image Title",
  "description": "Optional description",
  "image_url": "https://example.com/image.jpg",
  "content": {
    "image_url": "https://example.com/image.jpg",
    "link_url": "https://example.com/link",
    "alt_text": "Image description"
  },
  "order": 6,
  "is_active": true
}
```

### 7. PDF (PDF file + title)
- **Type**: `pdf`
- **Description**: PDF document with title and PDF URL
- **Structure**:
```json
{
  "type": "pdf",
  "title": "PDF Document Title",
  "description": "PDF description",
  "pdf_url": "https://example.com/document.pdf",
  "content": {
    "pdf_url": "https://example.com/document.pdf",
    "title": "PDF Document Title",
    "description": "PDF description"
  },
  "order": 7,
  "is_active": true
}
```

## API Endpoints

### Get Content Block Types
```
GET /api/lessons/{lessonId}/content-blocks/types
```
Returns available content block types and their structure examples.

### List Content Blocks
```
GET /api/lessons/{lessonId}/content-blocks
```
Returns all content blocks for a lesson.

**Query Parameters:**
- `type` - Filter by content block type
- `active` - Filter by active status (true/false)

### Create Content Block
```
POST /api/lessons/{lessonId}/content-blocks
```

**Required Fields:**
- `type` - One of the available content block types
- `title` - Block title (max 255 characters)
- `order` - Display order (integer, min 0)

**Optional Fields:**
- `description` - Block description
- `image_url` - Image URL
- `video_url` - Video URL
- `pdf_url` - PDF URL
- `content` - Content structure based on type
- `is_active` - Active status (boolean, default true)
- `remedy_id` - Remedy ID (for remedy type)

### Get Single Content Block
```
GET /api/lessons/{lessonId}/content-blocks/{blockId}
```

### Update Content Block
```
PUT /api/lessons/{lessonId}/content-blocks/{blockId}
```

### Delete Content Block
```
DELETE /api/lessons/{lessonId}/content-blocks/{blockId}
```

### Reorder Content Blocks
```
POST /api/lessons/{lessonId}/content-blocks/reorder
```

**Request Body:**
```json
{
  "blocks": [
    {"id": 1, "order": 0},
    {"id": 2, "order": 1},
    {"id": 3, "order": 2}
  ]
}
```

### Toggle Content Block Status
```
POST /api/lessons/{lessonId}/content-blocks/{blockId}/toggle-status
```

## Creating Lessons with Content Blocks

You can create lessons with content blocks in a single request:

```
POST /api/lessons
```

**Request Body:**
```json
{
  "course_id": 1,
  "title": "Lesson Title",
  "description": "Lesson description",
  "image": "https://example.com/lesson-image.jpg",
  "status": "active",
  "content_blocks": [
    {
      "type": "text",
      "title": "Introduction",
      "content": {
        "html_content": "<p>Welcome to this lesson!</p>"
      },
      "order": 0,
      "is_active": true
    },
    {
      "type": "video",
      "title": "Lesson Video",
      "video_url": "https://youtube.com/watch?v=example",
      "content": {
        "video_url": "https://youtube.com/watch?v=example",
        "title": "Lesson Video",
        "description": "Watch this video to learn more"
      },
      "order": 1,
      "is_active": true
    },
    {
      "type": "content",
      "title": "Key Points",
      "content": {
        "items": [
          {
            "title": "Point 1",
            "image_url": "https://example.com/point1.jpg"
          },
          {
            "title": "Point 2",
            "image_url": "https://example.com/point2.jpg"
          }
        ]
      },
      "order": 2,
      "is_active": true
    }
  ]
}
```

## Response Format

All API responses follow this format:

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Success message"
}
```

## Error Responses

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    // Validation errors
  }
}
```

## Validation Rules

### Content Block Validation
- `type` must be one of the available types
- `title` is required and max 255 characters
- `order` is required and must be >= 0
- `content` structure must match the type requirements
- `remedy_id` must exist in remedies table (for remedy type)

### Content Type-Specific Validation
- **content**: Must have `items` array with `title` and `image_url` for each item
- **text**: Must have `html_content` field
- **video**: Must have `video_url` field
- **remedy**: Must have `remedy_id` field
- **tip**: Must have both `image_url` and `html_content` fields
- **image**: Must have `image_url` field
- **pdf**: Must have `pdf_url` field

## Database Schema

The `lesson_content_blocks` table includes:
- `id` - Primary key
- `lesson_id` - Foreign key to lessons table
- `type` - Content block type
- `title` - Block title
- `description` - Block description
- `image_url` - Image URL
- `video_url` - Video URL
- `pdf_url` - PDF URL
- `content` - JSON content structure
- `order` - Display order
- `is_active` - Active status
- `remedy_id` - Foreign key to remedies table
- `created_at` - Creation timestamp
- `updated_at` - Update timestamp 