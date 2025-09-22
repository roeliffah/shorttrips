# Freestays Hotel Booking Platform

Welcome to the Freestays Hotel Booking Platform project! This project is designed to provide a comprehensive hotel booking solution using WordPress. Below you will find an overview of the project structure, functionalities, and installation instructions.

## Project Structure

The project is organized as follows:

```
freestays-hotelplatform
├── wp-content
│   ├── plugins
│   │   └── freestays-booking
│   │       ├── freestays-booking.php          # Main plugin file
│   │       ├── includes
│   │       │   ├── api
│   │       │   │   ├── class-freestays-api.php  # API integration class
│   │       │   │   └── class-sunhotels-client.php # Sunhotels API client class
│   │       │   ├── class-booking-handler.php    # Booking process handler
│   │       │   ├── class-shortcodes.php         # Shortcodes for displaying hotel info
│   │       │   ├── class-admin-settings.php      # Admin settings management
│   │       │   └── helpers.php                   # Helper functions
│   │       ├── templates
│   │       │   ├── search-form.php               # Search form template
│   │       │   ├── hotel-list.php                # Hotel list template
│   │       │   ├── hotel-detail.php              # Hotel detail template
│   │       │   └── booking-form.php              # Booking form template
│   │       ├── assets
│   │       │   ├── css
│   │       │   │   └── freestays.css             # CSS styles
│   │       │   └── js
│   │       │       └── freestays.js              # JavaScript functionality
│   │       └── README.md                          # Plugin documentation
│   └── themes
│       └── freestays-theme
│           ├── style.css                          # Theme styles
│           ├── functions.php                      # Theme functions
│           ├── header.php                         # Theme header structure
│           ├── footer.php                         # Theme footer structure
│           ├── page-templates
│           │   ├── template-search.php            # Search page template
│           │   └── template-hotel.php            # Hotel page template
│           └── README.md                          # Theme documentation
├── config
│   ├── sample.env                                 # Sample environment variables
│   └── README.md                                  # Configuration documentation
├── .gitignore                                     # Git ignore file
└── README.md                                      # General project documentation
```

## Features

- **Hotel Search**: Users can search for hotels based on various criteria.
- **Hotel Listings**: Display a list of available hotels based on search results.
- **Hotel Details**: Show detailed information about each hotel, including images and availability.
- **Booking Functionality**: Users can book hotels directly through the platform.
- **Admin Settings**: Manage plugin settings from the WordPress admin area.
- **Shortcodes**: Use shortcodes to display hotel information and booking forms anywhere on the site.

## Installation Instructions

1. **Clone the Repository**: Clone this repository to your local machine.
2. **Set Up WordPress**: Ensure you have a working WordPress installation.
3. **Configure Environment**: Copy the `config/sample.env` file to `.env` and configure the required variables:
   - Database settings (DB_HOST, DB_NAME, DB_USER, DB_PASS)
   - API credentials (API_URL, API_USER, API_PASS)
4. **Install the Plugin**: Copy the `freestays-booking` folder into the `wp-content/plugins` directory of your WordPress installation.
5. **Install the Theme**: Copy the `freestays-theme` folder into the `wp-content/themes` directory of your WordPress installation.
6. **Activate the Plugin**: Go to the WordPress admin area, navigate to Plugins, and activate the Freestays Booking plugin.
7. **Activate the Theme**: Go to Appearance > Themes and activate the Freestays theme.
8. **Configure Settings**: Access the plugin settings to configure API credentials and other options.
9. **Validate Installation**: Run the installation validator: `php installation-validator.php`
10. **Use Shortcodes**: Utilize the provided shortcodes to display hotel search forms and booking options on your pages.

### Installation Validation

A comprehensive installation validator is included to check all components:

```bash
cd freestays-hotelplatform
php installation-validator.php
```

This will check:
- Directory structure
- Required files
- PHP syntax
- WordPress compatibility
- Environment configuration

## Contributing

Contributions are welcome! Please feel free to submit issues or pull requests to enhance the functionality of the Freestays Hotel Booking Platform.

## License

This project is licensed under the MIT License. See the LICENSE file for more details.