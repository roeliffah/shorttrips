<?php

class Admin_Settings {
    private $options;

    public function __construct() {
        // Load the options
        $this->options = get_option('freestays_options');

        // Add settings page
        add_action('admin_menu', [$this, 'add_settings_page']);
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_options_page(
            'Freestays Settings',
            'Freestays',
            'manage_options',
            'freestays-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('freestays_options_group', 'freestays_options');
        
        add_settings_section(
            'freestays_main_section',
            'Main Settings',
            [$this, 'main_section_text'],
            'freestays-settings'
        );

        add_settings_field(
            'api_key',
            'API Key',
            [$this, 'api_key_input'],
            'freestays-settings',
            'freestays_main_section'
        );

        add_settings_field(
            'api_url',
            'API URL',
            [$this, 'api_url_input'],
            'freestays-settings',
            'freestays_main_section'
        );
    }

    public function main_section_text() {
        echo '<p>Configure the settings for the Freestays booking platform.</p>';
    }

    public function api_key_input() {
        $value = isset($this->options['api_key']) ? esc_attr($this->options['api_key']) : '';
        echo '<input type="text" id="api_key" name="freestays_options[api_key]" value="' . $value . '" />';
    }

    public function api_url_input() {
        $value = isset($this->options['api_url']) ? esc_attr($this->options['api_url']) : '';
        echo '<input type="text" id="api_url" name="freestays_options[api_url]" value="' . $value . '" />';
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Freestays Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('freestays_options_group');
                do_settings_sections('freestays-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}