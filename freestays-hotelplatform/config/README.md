# Freestays Hotel Booking Platform Configuration
Dit is de test vanuit dev via github naar aws

This README file provides an overview of the configuration settings and environment variables required for the Freestays Hotel Booking Platform.

## Configuration Overview

The Freestays Hotel Booking Platform is built on WordPress and requires specific configurations to function correctly. This includes database settings, API credentials, and other environment variables.

### Environment Variables

To set up the platform, create a `.env` file in the `config` directory based on the `sample.env` file. The following environment variables should be defined:

- **DB_HOST**: The hostname of your database server (e.g., `localhost`).
- **DB_PORT**: The port number for the database connection (default is `3306`).
- **DB_NAME**: The name of the database used by the application.
- **DB_USER**: The username for accessing the database.
- **DB_PASS**: The password for the database user.
- **API_USER**: The username for the external API (e.g., Sunhotels).
- **API_PASS**: The password for the external API.
- **API_URL**: The endpoint URL for the external API.

### Installation Instructions

1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Copy the `sample.env` file to `.env` and fill in the required values.
4. Install the necessary WordPress plugins and themes.
5. Activate the Freestays Booking plugin through the WordPress admin panel.
6. Configure the plugin settings as needed.

### Additional Notes

- Ensure that your server meets the minimum requirements for running WordPress and the Freestays plugin.
- Regularly check for updates to the plugin and theme to maintain security and functionality.

For further assistance, please refer to the documentation provided in the plugin and theme directories.