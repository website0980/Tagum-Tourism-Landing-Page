# Media CRUD System - Tagum DMS

## Setup Instructions (Windows)

### 1. Create Media Directory
```
cd "c:\Users\Acer\Promotional Website\Admin Module"
mkdir assets\media
```

### 2. Create MySQL Database
```
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe" -u root -p
```
Enter password, then:
```
CREATE DATABASE tagum_media;
USE tagum_media;
```

### 3. Test Media Manager
```
start "Admin Module/media-crud.php"
```
Login (admin/tagum2026) → Upload files → See dashboard/list

## Features Implemented
✅ **Upload** (image/audio/video) → MySQL + file system  
✅ **Display** dashboard with preview  
✅ **Edit**: Hidden field keeps existing file path  
✅ **No new file** → existing unchanged  
✅ **New file** → replace old file  
✅ **Delete** file + database record  

## Backend Logic
```php
// Edit - keeps existing if no new upload
if (empty($_FILES['image_file']['name'])) {
    $item['image'] = $_POST['current_image']; // from hidden field
} else {
    $item['image'] = $new_path; // uploaded
}
saveData($data);
```

## Integrate with DMS
Copy pattern to add-destination.php etc:
```
<input type="hidden" name="current_image" value="<?php echo $item['image']; ?>">
<input type="file" name="image_file">
```

**Ready for production!** 🚀
