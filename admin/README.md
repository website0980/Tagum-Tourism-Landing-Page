# Tagum City Admin Module Documentation

## Overview
The Admin Module is a secure destination management system that allows administrators to add, edit, delete, and feature tourist destinations for the Tagum City promotional website.

## Features

### 1. **Authentication System**
- Secure login with username and password
- Session-based authentication
- Automatic redirect to login for unauthorized access
- Logout functionality

### 2. **Destination Management**
- **Add New Destinations**: Create new tourist locations with comprehensive information
- **Edit Destinations**: Modify existing destination details
- **Delete Destinations**: Remove destinations from the system
- **Featured Status**: Mark destinations as featured for homepage display
- **Image Upload**: Upload and manage destination images (JPG, PNG, GIF - Max 5MB)

### 3. **Form Fields**
Each destination includes:
- **Basic Information**: Name, Type, Description, Entrance Fee
- **Location Details**: Location, Accessibility, Features, Facilities, Contact
- **Travel Information**: Best Time to Visit, What to Pack, Visiting Rules
- **Media**: Featured image upload
- **Status**: Featured/Non-featured toggle

### 4. **Admin Dashboard**
- View all destinations in table format
- Quick statistics (Total destinations, Featured count)
- Easy access to add, edit, delete, and featured toggle functions
- Responsive design for mobile access

## Getting Started

### Default Credentials
```
Username: admin
Password: tagum2026
```

⚠️ **IMPORTANT**: Change these credentials immediately in production!

### Accessing the Admin Panel
1. Go to your website footer
2. Click the "Admin" link
3. Enter your credentials
4. You'll be redirected to the dashboard

### File Structure
```
Admin module/
├── config.php              # Configuration and helper functions
├── login.php               # Login page
├── dashboard.php           # Main admin dashboard
├── add-destination.php     # Add/Edit destination form
├── logout.php              # Logout handler
└── README.md               # This file

assets/
├── css/admin.css          # Admin styling
├── js/admin.js            # Admin JavaScript
├── images/destinations/    # Uploaded destination images
└── data/destinations.json  # Destination data storage
```

## How to Use

### 1. Login
- Navigate to `/Admin module/login.php`
- Enter username and password
- Click "Login" button

### 2. View Destinations
- After login, you'll see the dashboard
- All destinations are listed in a table
- Each destination shows: Image, Name, Type, Featured status, and Actions

### 3. Add a New Destination
1. Click "+ Add New Destination" button
2. Fill in all required fields:
   - Destination Name *
   - Destination Type *
   - Description *
3. Fill in optional fields for better content:
   - Location, Accessibility, Features, Facilities
   - Contact information
   - Best time to visit
   - What to pack
   - Visiting rules
4. Upload a destination image (optional)
5. Check "Mark as Featured" if needed
6. Click "✏️ Add Destination"

### 4. Edit a Destination
1. In the dashboard, click "Edit" button next to the destination
2. Modify any field
3. To change image: Upload a new image (old image will be deleted)
4. Click "Update Destination"

### 5. Toggle Featured Status
1. In the dashboard, click the "⭐ Featured" or "Not Featured" button
2. Status updates immediately

### 6. Delete a Destination
1. In the dashboard, click "Delete" button
2. Confirm the deletion
3. Destination and its image will be permanently removed

### 7. Logout
- Click "Logout" button in the top-right corner
- You'll be redirected to the login page

## Technical Details

### Image Upload
- **Accepted formats**: JPG, JPEG, PNG, GIF
- **Maximum size**: 5MB
- **Storage location**: `/assets/images/destinations/`
- **Naming**: Automatic UUID-based naming to prevent conflicts
- **Automatic cleanup**: Old images are deleted when updating

### Data Storage
- **Format**: JSON file-based storage
- **Location**: `/assets/data/destinations.json`
- **Structure**: Array of destination objects
- **Backup**: Recommended to backup destinations.json regularly

### Security
- Session-based authentication
- Password hash validation (in production, use proper hashing)
- File upload validation (type and size checks)
- HTML entity encoding for output (XSS prevention)
- CSRF protection available via forms

## Configuration

### Edit Credentials (config.php)
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'tagum2026');
```

### Max File Size (config.php)
```php
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
```

## Best Practices

1. **Backup Regularly**: Backup the `/assets/data/destinations.json` file
2. **Image Optimization**: Compress images before upload for better performance
3. **Change Default Password**: Update credentials immediately after setup
4. **Use HTTPS**: Always use HTTPS in production
5. **Content Quality**: Use clear, descriptive text and high-quality images
6. **Regular Updates**: Keep destination information current

## Troubleshooting

### Can't Login?
- Check username and password in `/Admin module/config.php`
- Ensure PHP sessions are enabled
- Clear browser cookies and try again

### Image Upload Fails?
- Check file size (max 5MB)
- Verify file format (JPG, PNG, GIF only)
- Ensure `/assets/images/destinations/` folder is writable
- Check directory permissions (chmod 755)

### Data Not Saving?
- Verify `/assets/data/` folder exists
- Check folder permissions (chmod 755)
- Ensure JSON file is writable
- Check disk space on server

### Styling Issues?
- Clear browser cache (Ctrl+Shift+Del)
- Check if `admin.css` is loaded
- Verify CSS file path is correct

## Browser Compatibility
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- IE 11 (limited support)

## Future Enhancements

Potential improvements for future versions:
- User management system
- Role-based access control (Admin, Editor, Viewer)
- Bulk operations (delete multiple, export)
- Destination categories/filtering
- Search functionality
- Database integration (MySQL, PostgreSQL)
- API endpoints for mobile apps
- Analytics dashboard
- Email notifications
- Version history/changelog

## Support

For technical issues or questions, contact your website administrator.

---

Last Updated: 2026
Version: 1.0
