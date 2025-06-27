# TMSM Appointment Cancelation

A WordPress plugin that allows users to cancel appointments made by phone through the Aquos API integration.

## Description

The TMSM Appointment Cancelation plugin provides a secure and user-friendly way for customers to cancel their appointments. It integrates with the Aquos booking system API to handle appointment cancellations seamlessly within your WordPress website.

## Features

- **Secure Appointment Cancellation**: Allows customers to cancel appointments through a secure, token-based system
- **Aquos API Integration**: Seamless integration with the Aquos booking system
- **Admin Dashboard**: Dedicated admin page for managing appointments and viewing cancellation requests
- **Email Notifications**: Automatic email notifications to both customers and administrators
- **Multi-language Support**: Built-in internationalization support
- **Security Features**: Token-based authentication and nonce verification
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Active Aquos API credentials
- WordPress admin access

## Installation

### Method 1: Manual Installation (Recommended)

1. Download the plugin files
2. Upload the `tmsm-appointment-cancelation` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin settings (see Configuration section below)

### Method 2: FTP Upload

1. Extract the plugin files to your local computer
2. Upload the `tmsm-appointment-cancelation` folder to `/wp-content/plugins/` via FTP
3. Activate the plugin in WordPress admin
4. Configure the plugin settings

## Configuration

### 1. Plugin Settings

Navigate to **WordPress Admin → TMSM → Appointment Cancelation** to configure:

- **Aquos API Credentials**: Enter your Aquos API endpoint and authentication details
- **Email Settings**: Configure notification email templates and recipients
- **Security Settings**: Set token expiration times and security parameters
- **Display Options**: Customize the appearance of cancellation forms

### 2. Required API Parameters

The plugin requires the following parameters for proper functionality:

- **Functional ID**: Unique identifier for the appointment
- **Appointment Date**: Date and time of the appointment
- **Security Token**: Authentication token for secure access

### 3. Page Setup

1. Create a new page in WordPress
2. The cancellation form will be automatically displayed when accessed with valid parameters
3. The page is only accessible via secure links sent to customers

## Usage

### For Customers

1. Customers receive a secure link via email or SMS
2. Clicking the link opens the cancellation page with pre-filled appointment details
3. Customers can review appointment information and confirm cancellation
4. Confirmation email is sent to the customer
5. Admin notification is sent to staff

### For Administrators

1. Access appointment management through **WordPress Admin → TMSM → Appointment Cancelation**
2. View all cancellation requests and their status
3. Monitor API integration status
4. Configure email templates and settings

## API Integration

### Aquos API Requirements

The plugin integrates with the Aquos booking system API. Ensure you have:

- Valid API endpoint URL
- Authentication credentials
- Proper API permissions for cancellation operations

### API Endpoints Used

- `GET /appointments/{id}` - Retrieve appointment details
- `POST /appointments/{id}/cancel` - Cancel appointment
- `GET /appointments/validate-token` - Validate security token

## Security Features

- **Token-based Authentication**: All cancellation links require valid security tokens
- **Nonce Verification**: WordPress nonce verification for form submissions
- **Input Sanitization**: All user inputs are properly sanitized
- **Access Control**: Pages are only accessible via secure links
- **Rate Limiting**: Built-in protection against abuse

## Troubleshooting

### Common Issues

**Plugin not activating**
- Check PHP version compatibility (requires 7.4+)
- Verify WordPress version (requires 5.0+)
- Check file permissions on plugin directory

**API connection errors**
- Verify Aquos API credentials in plugin settings
- Check API endpoint URL format
- Ensure API service is accessible from your server

**Email notifications not sending**
- Check WordPress email configuration
- Verify SMTP settings if using custom email provider
- Check spam/junk folders

**Cancellation links not working**
- Verify token expiration settings
- Check URL format and parameters
- Ensure proper page setup

### Debug Mode

Enable WordPress debug mode to view detailed error messages:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log Files

Plugin logs are stored in:
- WordPress debug log: `/wp-content/debug.log`
- Plugin-specific logs: Check plugin admin panel

## Development

### File Structure

```
tmsm-appointment-cancelation/
├── admin/                          # Admin interface files
├── includes/                       # Core plugin classes
├── languages/                      # Translation files
├── public/                         # Frontend files
├── templates/                      # Template files
├── tmsm-appointment-cancelation.php # Main plugin file
└── README.md                       # This file
```

### Hooks and Filters

The plugin provides several hooks for customization:

```php
// Customize cancellation form
add_filter('tmsm_cancellation_form_fields', 'custom_form_fields');

// Modify email templates
add_filter('tmsm_cancellation_email_content', 'custom_email_content');

// Add custom validation
add_action('tmsm_before_cancellation', 'custom_validation');
```

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Changelog

### Version 1.0.0
- Initial release
- Basic appointment cancellation functionality
- Aquos API integration
- Email notifications
- Admin dashboard

## Support

### Documentation
- [Plugin Documentation](https://github.com/ArnaudFlament35/tmsm-appointment-cancelation/wiki)
- [API Documentation](https://aquos-api-docs.example.com)

### Contact
- **Developer**: Arnaud Flament
- **Email**: aflament.dev@gmail.com
- **GitHub**: [@ArnaudFlament35](https://github.com/ArnaudFlament35)

### Bug Reports
Please report bugs and issues on the [GitHub Issues page](https://github.com/ArnaudFlament35/tmsm-appointment-cancelation/issues).

## License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Arnaud Flament

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## Credits

- **Developer**: Arnaud Flament
- **WordPress Plugin Boilerplate**: Based on the WordPress Plugin Boilerplate
- **Aquos Integration**: Custom integration with Aquos booking system API