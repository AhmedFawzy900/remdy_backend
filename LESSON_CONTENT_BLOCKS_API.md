# 🚀 Lesson Content Blocks API Documentation

## 📋 Overview

This API provides a **simplified and efficient** content management system for lessons. You can now create a complete lesson with all its content blocks in a **single API call**! The system automatically manages lesson ordering while giving you full control over content block positioning.

## ✨ Key Features

- **🎯 Single API Call**: Create lesson + all content blocks in one request
- **📝 Simplified Content**: Just title, description, and image/video URL
- **🔄 Dynamic Lesson Order**: Lessons automatically ordered by creation date
- **📱 Explicit Content Block Order**: You control exactly where each block appears
- **🎨 Rich Content Types**: Support for video, text, image, remedy, ingredients, tips, and instructions
- **⚡ Performance**: Optimized queries with proper indexing

## 🗄️ Database Structure

### Lessons Table
```sql
lessons
├── id (primary key)
├── course_id (foreign key)
├── title
├── description
├── image (nullable)
├── status (active/inactive)
├── created_at
└── updated_at
```

### Lesson Content Blocks Table
```sql
lesson_content_blocks
├── id (primary key)
├── lesson_id (foreign key)
├── type (string: video, text, image, etc.)
├── title (string)
├── description (text)
├── image_url (nullable)
├── video_url (nullable)
├── content (JSON - for complex data like arrays)
├── order (integer - REQUIRED for positioning)
├── is_active (boolean)
├── created_at
└── updated_at
```

**Note**: Lesson order is now **dynamic** (based on creation date), but content block order is **explicit** (you specify the exact position).

## 🎯 Content Block Types & Simplified Structure

### 1. Video Block
```json
{
  "type": "video",
  "title": "Video Title",
  "description": "Video description",
  "video_url": "https://example.com/video.mp4",
  "image_url": "https://example.com/thumbnail.jpg",
  "order": 0
}
```

### 2. Text Block
```json
{
  "type": "text",
  "title": "Section Title",
  "description": "Your text content here",
  "image_url": "https://example.com/optional-image.jpg",
  "order": 1
}
```

### 3. Image Block
```json
{
  "type": "image",
  "title": "Image Title",
  "description": "Image description",
  "image_url": "https://example.com/image.jpg",
  "order": 2
}
```

### 4. Remedy Block
```json
{
  "type": "remedy",
  "title": "Remedy Name",
  "description": "Remedy description",
  "image_url": "https://example.com/remedy.jpg",
  "order": 3
}
```

### 5. Ingredients Block
```json
{
  "type": "ingredients",
  "title": "Required Materials",
  "description": "List of ingredients needed",
  "image_url": "https://example.com/ingredients-overview.jpg",
  "order": 4,
  "content": {
    "items": [
      {
        "title": "Ingredient Name",
        "image_url": "https://example.com/ingredient.jpg"
      },
      {
        "title": "Another Ingredient",
        "image_url": "https://example.com/another.jpg"
      }
    ]
  }
}
```

### 6. Tips Block
```json
{
  "type": "tips",
  "title": "Pro Tips",
  "description": "Essential tips for success",
  "image_url": "https://example.com/tips-overview.jpg",
  "order": 5,
  "content": {
    "items": [
      {
        "title": "Tip Title",
        "image_url": "https://example.com/tip.jpg"
      }
    ]
  }
}
```

### 7. Instructions Block
```json
{
  "type": "instructions",
  "title": "Step-by-Step Instructions",
  "description": "Follow these steps carefully",
  "image_url": "https://example.com/instructions-overview.jpg",
  "order": 6,
  "content": {
    "steps": [
      {
        "title": "Step Title",
        "image_url": "https://example.com/step.jpg"
      }
    ]
  }
}
```

## 🔌 API Endpoints

### **🎯 MAIN ENDPOINT: Create Lesson with Content Blocks**

```http
POST /api/lessons
```

**Request Body:**
```json
{
  "course_id": 1,
  "title": "Complete Natural Medicine Lesson",
  "description": "Learn everything about natural healing",
  "image": "https://example.com/lesson-image.jpg",
  "status": "active",
  "content_blocks": [
    {
      "type": "video",
      "title": "Introduction Video",
      "description": "Watch this video to get started",
      "video_url": "https://example.com/intro.mp4",
      "image_url": "https://example.com/thumbnail.jpg",
      "order": 0
    },
    {
      "type": "text",
      "title": "What You'll Learn",
      "description": "In this lesson, you will discover the fundamentals of natural medicine...",
      "order": 1
    },
    {
      "type": "image",
      "title": "Natural Medicine Chart",
      "description": "This diagram shows the hierarchy of natural healing approaches",
      "image_url": "https://example.com/chart.jpg",
      "order": 2
    },
    {
      "type": "ingredients",
      "title": "Required Materials",
      "description": "Gather these items to follow along",
      "image_url": "https://example.com/materials.jpg",
      "order": 3,
      "content": {
        "items": [
          {
            "title": "Lavender Oil",
            "image_url": "https://example.com/lavender.jpg"
          },
          {
            "title": "Carrier Oil",
            "image_url": "https://example.com/carrier.jpg"
          }
        ]
      }
    },
    {
      "type": "instructions",
      "title": "How to Create Your Remedy",
      "description": "Follow these steps carefully",
      "image_url": "https://example.com/steps.jpg",
      "order": 4,
      "content": {
        "steps": [
          {
            "title": "Prepare Workspace",
            "image_url": "https://example.com/step1.jpg"
          },
          {
            "title": "Mix Ingredients",
            "image_url": "https://example.com/step2.jpg"
          }
        ]
      }
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course_id": 1,
    "title": "Complete Natural Medicine Lesson",
    "description": "Learn everything about natural healing",
    "image": "https://example.com/lesson-image.jpg",
    "status": "active",
    "content_blocks": [
      {
        "id": 1,
        "type": "video",
        "title": "Introduction Video",
        "description": "Watch this video to get started",
        "video_url": "https://example.com/intro.mp4",
        "image_url": "https://example.com/thumbnail.jpg",
        "order": 0,
        "is_active": true
      }
      // ... more blocks
    ]
  },
  "message": "Lesson created successfully with content blocks"
}
```

---

### **Other Endpoints (for managing existing lessons):**

### 1. List Content Blocks
```http
GET /api/lessons/{lessonId}/content-blocks
```

### 2. Get Content Block
```http
GET /api/lessons/{lessonId}/content-blocks/{blockId}
```

### 3. Update Content Block
```http
PUT /api/lessons/{lessonId}/content-blocks/{blockId}
```

### 4. Delete Content Block
```http
DELETE /api/lessons/{lessonId}/content-blocks/{blockId}
```

### 5. Reorder Content Blocks
```http
PATCH /api/lessons/{lessonId}/content-blocks/reorder
```

### 6. Toggle Block Status
```http
PATCH /api/lessons/{lessonId}/content-blocks/{blockId}/toggle-status
```

### 7. Get Available Content Types
```http
GET /api/lessons/{lessonId}/content-blocks/types
```

## 🚀 Usage Examples

### **Complete Lesson Creation in One API Call**

```bash
curl -X POST "https://your-domain.com/api/lessons" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": 1,
    "title": "Natural Healing Basics",
    "description": "Complete guide to natural medicine",
    "image": "https://example.com/lesson.jpg",
    "status": "active",
    "content_blocks": [
      {
        "type": "video",
        "title": "Welcome Video",
        "description": "Introduction to natural healing",
        "video_url": "https://example.com/welcome.mp4",
        "order": 0
      },
      {
        "type": "text",
        "title": "Overview",
        "description": "What you will learn in this lesson",
        "order": 1
      },
      {
        "type": "ingredients",
        "title": "Materials Needed",
        "description": "Gather these items",
        "order": 2,
        "content": {
          "items": [
            {"title": "Lavender Oil", "image_url": "https://example.com/lavender.jpg"},
            {"title": "Carrier Oil", "image_url": "https://example.com/carrier.jpg"}
          ]
        }
      }
    ]
  }'
```

## 📱 Postman Collection

### **Environment Variables:**
```
base_url: https://your-domain.com
your_token: Bearer YOUR_ACTUAL_TOKEN
```

### **Single Request - Create Complete Lesson:**
```
POST {{base_url}}/api/lessons
Headers:
  Authorization: {{your_token}}
  Content-Type: application/json

Body:
{
  "course_id": 1,
  "title": "Your Lesson Title",
  "description": "Your lesson description",
  "image": "https://example.com/image.jpg",
  "status": "active",
  "content_blocks": [
    {
      "type": "video",
      "title": "Video Title",
      "description": "Video description",
      "video_url": "https://example.com/video.mp4",
      "order": 0
    },
    {
      "type": "text",
      "title": "Text Title",
      "description": "Text content",
      "order": 1
    }
  ]
}
```

## 🎯 Key Benefits

### ✅ **Single API Call**
- Create lesson + all content blocks in one request
- No need for multiple API calls
- Atomic operation - all or nothing

### ✅ **Simplified Content Structure**
- Just title, description, and image/video URL
- Clean and easy to understand
- Minimal data entry required

### ✅ **Smart Array Handling**
- Ingredients and instructions arrays automatically simplified
- Only title and image_url kept in arrays
- Complex data stored in content JSON field

### ✅ **Automatic Ordering**
- Content blocks created in the exact order you specify
- No conflicts or manual reordering needed
- Perfect for building lesson flow

## 🔧 Migration Steps

1. **Run the new migration** to add simplified fields:
   ```bash
   php artisan migrate
   ```

2. **Update your frontend** to use the new single API structure

3. **Test with a simple lesson** creation

## 🎉 Summary

The new system provides:
- **Single API call** for complete lesson creation
- **Simplified content structure** (title, description, image/video URL)
- **Automatic content block creation** with proper ordering
- **Clean array handling** for ingredients and instructions
- **Maximum efficiency** with minimal API calls

This is the **goddest way** to create lessons with dynamic content! 🚀 