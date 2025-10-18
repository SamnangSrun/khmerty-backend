# Local Image Storage Setup

## Overview

Changed from Cloudinary to local file storage for all image uploads.

## What Was Changed

### 1. Controllers Updated

-   ✅ **NewsCategoryController.php** - Category icons now stored locally
-   ✅ **NewsPostController.php** - News post images now stored locally
-   ⚠️ **BookController.php** - Still uses Cloudinary (update if needed)

### 2. Storage Configuration

-   Storage link created: `public/storage` → `storage/app/public`
-   Directories created:
    -   `storage/app/public/news_categories/` - For category icons
    -   `storage/app/public/news_posts/` - For news post images

### 3. Image URLs

Images are now accessible via:

```
http://localhost/book-store/Khmerty/news-api/public/storage/news_categories/{filename}
http://localhost/book-store/Khmerty/news-api/public/storage/news_posts/{filename}
```

## File Storage Format

### Category Icons

-   Location: `storage/app/public/news_categories/`
-   Filename format: `{timestamp}_{random10chars}.{ext}`
-   Example: `1729089234_aBcDeFgHiJ.png`

### News Post Images

-   Location: `storage/app/public/news_posts/`
-   Filename format: `{timestamp}_{random10chars}.{ext}`
-   Example: `1729089456_xYzWqRsTuV.jpg`

## Code Changes

### NewsCategoryController.php

```php
// Before (Cloudinary)
$uploadedFile = Cloudinary::upload($request->file('icon')->getRealPath(), [...]);
$validated['icon'] = $uploadedFile->getSecurePath();

// After (Local Storage)
$file = $request->file('icon');
$filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
$path = $file->storeAs('news_categories', $filename, 'public');
$validated['icon'] = Storage::url($path);
```

### NewsPostController.php

```php
// Before (Cloudinary)
$uploadedFile = Cloudinary::upload($image->getRealPath(), [...]);
$imageUrls[] = $uploadedFile->getSecurePath();

// After (Local Storage)
$filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
$path = $image->storeAs('news_posts', $filename, 'public');
$imageUrls[] = Storage::url($path);
```

## Benefits of Local Storage

✅ **No external service dependencies** - No need for Cloudinary account
✅ **Faster uploads** - No network latency
✅ **No API limits** - No upload quotas or rate limits
✅ **Complete control** - All files on your server
✅ **Free** - No monthly costs

## Maintenance

### Check Storage Usage

```bash
# Check storage directory size
du -sh storage/app/public/news_*
```

### Clean Old Files (if needed)

```php
// Delete old images when updating/deleting records
if ($category->icon) {
    $oldPath = str_replace('/storage/', '', parse_url($category->icon, PHP_URL_PATH));
    Storage::disk('public')->delete($oldPath);
}
```

## Frontend Changes Needed

The frontend URLs will now be:

```javascript
// Category icon URL
const iconUrl = category.icon; // Already contains /storage/news_categories/...

// News post images
const imageUrl = post.images[0]; // Already contains /storage/news_posts/...
```

No changes needed in frontend - URLs are returned correctly from API!

## Backup Recommendations

Since images are now stored locally, make sure to:

1. Include `storage/app/public/news_*` in your backup strategy
2. Add `.gitignore` for uploaded files (already configured)
3. Consider periodic backups of the storage directory

## Rollback to Cloudinary (if needed)

To revert back to Cloudinary:

1. Restore the original controller files
2. Update imports from `Storage` back to `Cloudinary`
3. Change filesystem disk in `.env` back to `cloudinary`

---

**Status**: ✅ Local storage successfully configured and tested
**Date**: October 16, 2025
