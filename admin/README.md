# Tagum City Admin Module Documentation

## Overview
The Admin Module is a secure destination management system that allows administrators to add, edit, delete, and feature tourist destinations for the Tagum City promotional website.

## Features

### 1. **Authentication System**
- Secure login with username and password
- Session-based authentication
- Automatic redirect to login for unauthorized access
- Logout functionality
- Super Admin-managed accounts and custom module permissions
- Single-use, expiring password-reset links by email

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

### First Super Admin
On the first admin login, the system creates the built-in Super Admin account from `ADMIN_USERNAME` and `ADMIN_PASSWORD_HASH` in `config.php`. Sign in with the existing admin credentials, open **Accounts & Roles**, set a verified email address, and change the initial password immediately. The built-in Super Admin role cannot be removed or restricted.

To populate the initial account email automatically, set `TAGUM_SUPER_ADMIN_EMAIL` in the PHP server environment before the account tables are first created. Existing accounts can have their email changed in **Accounts & Roles**.

### Accounts, Roles, and Permissions
- Super Admins can manage all admin accounts and roles.
- The default `User Manager` role can manage tourism content modules and create, update, deactivate, and reset regular admin accounts. Its own account details are visible read-only.
- User Managers cannot edit roles, manage their own account, manage other User Managers, or manage Super Admins.
- To delegate this access, a Super Admin assigns `User Manager` to the trusted account from **Accounts & Roles**. The account then sees **Admin Accounts** and the data-management modules in the dashboard.
- Only a Super Admin can create or edit roles, or assign Super Admin and User Manager access.
- The default `Admin` role can manage all content modules but cannot manage accounts or roles.
- The default `Editor` role can manage content modules and view certification, feedback, and reports.
- The default `Viewer` role has read-only access to all modules.
- Custom roles have `None`, `View`, or `Manage` permissions per admin module. `Manage` includes viewing.
- Permissions are checked on the server for direct page requests and dashboard form submissions.
- The last active Super Admin cannot be deactivated or demoted.
- New accounts and password resets require passwords of at least 12 characters.

### Super Admin Support Chat
- Every signed-in admin can open **Support Chat** from the dashboard and message the Super Admin.
- The Super Admin sees active admin accounts in an inbox, can reply to each account, and sees unread-message counts.
- Conversations are private to the account and Super Admin, and are stored in the existing SQLite database.

### Forgot Password Requests
- From the login page, an admin submits their username or email to request a password change.
- The app queues the request in SQLite and shows the same confirmation whether or not the account matches.
- Super Admin sees the pending request count on the dashboard and reviews requests under **Accounts & Roles**.
- Super Admin sets a new password of at least 12 characters. The password is stored as a hash, and the request closes in the same transaction.
- The Super Admin gives the new password to the account owner through a verified secure channel. Passwords are not sent by email or displayed again after saving.
- Existing sessions for the account are invalidated when the password changes. If the Super Admin account itself is locked out, recovery requires server/database operator assistance.

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
- Passwords are stored using PHP's password hashing API.
- Password-change requests do not disclose whether a username or email belongs to an account.
- Only Super Admin can approve a request; password update and request completion happen in one transaction.
- Role permissions are enforced server-side, not only through hidden navigation.
- CSRF protection is used on login, password-request, account, and role forms.
- Use HTTPS and share temporary passwords through a verified secure channel.

## Configuration

### Database
Admin users, roles, permissions, and password-change requests are stored in the SQLite database returned by `appDatabasePath()`. The auth tables are created automatically without removing existing tourism data.

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
