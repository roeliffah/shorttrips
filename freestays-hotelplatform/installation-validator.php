<?php
/**
 * Freestays Hotel Platform Installation Validator
 * 
 * This script checks all installation requirements and components
 */

// Prevent direct access if running in WordPress context
if (defined('ABSPATH')) {
    // Running in WordPress - use WordPress functions
    $is_wordpress = true;
} else {
    // Running standalone
    $is_wordpress = false;
    // Define basic path constants for standalone execution
    define('FREESTAYS_BASE_PATH', dirname(__FILE__));
}

class FreestaysInstallationValidator {
    
    private $errors = [];
    private $warnings = [];
    private $success = [];
    private $is_wordpress;
    
    public function __construct($is_wordpress = false) {
        $this->is_wordpress = $is_wordpress;
    }
    
    public function validate() {
        echo "<h1>Freestays Hotel Platform Installation Validation</h1>\n";
        echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px;'>\n";
        
        $this->checkDirectoryStructure();
        $this->checkConfigurationFiles();
        $this->checkPluginFiles();
        $this->checkThemeFiles();
        $this->checkAssetFiles();
        $this->checkPHPSyntax();
        $this->checkWordPressRequirements();
        $this->checkEnvironmentVariables();
        
        $this->displayResults();
        echo "</div>\n";
    }
    
    private function checkDirectoryStructure() {
        echo "<h2>📁 Directory Structure Check</h2>\n";
        
        $required_dirs = [
            'wp-content',
            'wp-content/plugins',
            'wp-content/plugins/freestays-booking',
            'wp-content/plugins/freestays-booking/includes',
            'wp-content/plugins/freestays-booking/includes/api',
            'wp-content/plugins/freestays-booking/templates',
            'wp-content/plugins/freestays-booking/assets',
            'wp-content/plugins/freestays-booking/assets/css',
            'wp-content/plugins/freestays-booking/assets/js',
            'wp-content/themes',
            'wp-content/themes/freestays-theme',
            'wp-content/themes/freestays-theme/assets',
            'wp-content/themes/freestays-theme/assets/js',
            'wp-content/themes/freestays-theme/page-templates',
            'config'
        ];
        
        foreach ($required_dirs as $dir) {
            if (is_dir($dir)) {
                $this->success[] = "✅ Directory exists: $dir";
            } else {
                $this->errors[] = "❌ Missing directory: $dir";
            }
        }
    }
    
    private function checkConfigurationFiles() {
        echo "<h2>⚙️ Configuration Files Check</h2>\n";
        
        $config_files = [
            'config/sample.env' => 'Sample environment file',
            'config/README.md' => 'Configuration documentation',
            'README.md' => 'Main project documentation'
        ];
        
        foreach ($config_files as $file => $description) {
            if (file_exists($file)) {
                $this->success[] = "✅ $description exists: $file";
            } else {
                $this->warnings[] = "⚠️ Missing $description: $file";
            }
        }
    }
    
    private function checkPluginFiles() {
        echo "<h2>🔌 Plugin Files Check</h2>\n";
        
        $plugin_files = [
            'wp-content/plugins/freestays-booking/freestays-booking.php' => 'Main plugin file',
            'wp-content/plugins/freestays-booking/includes/class-admin-settings.php' => 'Admin settings class',
            'wp-content/plugins/freestays-booking/includes/class-shortcodes.php' => 'Shortcodes class',
            'wp-content/plugins/freestays-booking/includes/class-booking-handler.php' => 'Booking handler class',
            'wp-content/plugins/freestays-booking/includes/class-toaster.php' => 'Toaster notification class',
            'wp-content/plugins/freestays-booking/includes/api/class-freestays-api.php' => 'Freestays API class',
            'wp-content/plugins/freestays-booking/includes/api/class-sunhotels-client.php' => 'Sunhotels API client',
            'wp-content/plugins/freestays-booking/includes/helpers.php' => 'Helper functions',
            'wp-content/plugins/freestays-booking/README.md' => 'Plugin documentation'
        ];
        
        foreach ($plugin_files as $file => $description) {
            if (file_exists($file)) {
                $this->success[] = "✅ $description exists: $file";
            } else {
                $this->errors[] = "❌ Missing $description: $file";
            }
        }
    }
    
    private function checkThemeFiles() {
        echo "<h2>🎨 Theme Files Check</h2>\n";
        
        $theme_files = [
            'wp-content/themes/freestays-theme/functions.php' => 'Theme functions',
            'wp-content/themes/freestays-theme/style.css' => 'Theme styles',
            'wp-content/themes/freestays-theme/header.php' => 'Theme header',
            'wp-content/themes/freestays-theme/footer.php' => 'Theme footer',
            'wp-content/themes/freestays-theme/assets/js/freestays.js' => 'Theme JavaScript',
            'wp-content/themes/freestays-theme/page-templates/template-search.php' => 'Search page template',
            'wp-content/themes/freestays-theme/page-templates/template-hotel.php' => 'Hotel page template',
            'wp-content/themes/freestays-theme/README.md' => 'Theme documentation'
        ];
        
        foreach ($theme_files as $file => $description) {
            if (file_exists($file)) {
                $this->success[] = "✅ $description exists: $file";
            } else {
                $this->errors[] = "❌ Missing $description: $file";
            }
        }
    }
    
    private function checkAssetFiles() {
        echo "<h2>📦 Asset Files Check</h2>\n";
        
        $asset_files = [
            'wp-content/plugins/freestays-booking/assets/css/freestays.css' => 'Plugin CSS',
            'wp-content/plugins/freestays-booking/assets/js/freestays.js' => 'Plugin JavaScript'
        ];
        
        foreach ($asset_files as $file => $description) {
            if (file_exists($file)) {
                $this->success[] = "✅ $description exists: $file";
            } else {
                $this->warnings[] = "⚠️ Missing $description: $file";
            }
        }
    }
    
    private function checkPHPSyntax() {
        echo "<h2>🔍 PHP Syntax Check</h2>\n";
        
        $php_files = glob('wp-content/**/*.php', GLOB_BRACE);
        $syntax_errors = 0;
        
        foreach ($php_files as $file) {
            $output = [];
            $return_code = 0;
            exec("php -l \"$file\" 2>&1", $output, $return_code);
            
            if ($return_code !== 0) {
                $this->errors[] = "❌ PHP syntax error in: $file";
                $syntax_errors++;
            }
        }
        
        if ($syntax_errors === 0) {
            $this->success[] = "✅ All PHP files have valid syntax";
        }
    }
    
    private function checkWordPressRequirements() {
        echo "<h2>🌐 WordPress Requirements Check</h2>\n";
        
        if ($this->is_wordpress) {
            // Check WordPress version
            global $wp_version;
            if (version_compare($wp_version, '5.0', '>=')) {
                $this->success[] = "✅ WordPress version is compatible ($wp_version)";
            } else {
                $this->warnings[] = "⚠️ WordPress version may be too old ($wp_version)";
            }
            
            // Check if required functions exist
            $required_functions = ['add_action', 'add_shortcode', 'wp_enqueue_script', 'wp_enqueue_style'];
            foreach ($required_functions as $func) {
                if (function_exists($func)) {
                    $this->success[] = "✅ WordPress function available: $func";
                } else {
                    $this->errors[] = "❌ Missing WordPress function: $func";
                }
            }
        } else {
            $this->warnings[] = "⚠️ Not running in WordPress context - some checks skipped";
        }
    }
    
    private function checkEnvironmentVariables() {
        echo "<h2>🔧 Environment Variables Check</h2>\n";
        
        $env_vars = ['API_URL', 'API_USER', 'API_PASS', 'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        
        foreach ($env_vars as $var) {
            if (getenv($var) || (isset($_ENV[$var]) && !empty($_ENV[$var]))) {
                $this->success[] = "✅ Environment variable set: $var";
            } else {
                $this->warnings[] = "⚠️ Environment variable not set: $var";
            }
        }
    }
    
    private function displayResults() {
        echo "<h2>📊 Validation Results</h2>\n";
        
        echo "<h3 style='color: green;'>✅ Success (" . count($this->success) . ")</h3>\n";
        foreach ($this->success as $message) {
            echo "<p style='color: green; margin: 5px 0;'>$message</p>\n";
        }
        
        if (!empty($this->warnings)) {
            echo "<h3 style='color: orange;'>⚠️ Warnings (" . count($this->warnings) . ")</h3>\n";
            foreach ($this->warnings as $message) {
                echo "<p style='color: orange; margin: 5px 0;'>$message</p>\n";
            }
        }
        
        if (!empty($this->errors)) {
            echo "<h3 style='color: red;'>❌ Errors (" . count($this->errors) . ")</h3>\n";
            foreach ($this->errors as $message) {
                echo "<p style='color: red; margin: 5px 0;'>$message</p>\n";
            }
        }
        
        // Overall status
        if (empty($this->errors)) {
            if (empty($this->warnings)) {
                echo "<h3 style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;'>🎉 Installation is COMPLETE and VALID!</h3>\n";
            } else {
                echo "<h3 style='color: orange; background: #fff3cd; padding: 10px; border-radius: 5px;'>✨ Installation is mostly complete with some minor issues</h3>\n";
            }
        } else {
            echo "<h3 style='color: red; background: #f8d7da; padding: 10px; border-radius: 5px;'>🚨 Installation has CRITICAL ERRORS that need to be fixed</h3>\n";
        }
    }
}

// Run the validation
$validator = new FreestaysInstallationValidator(defined('ABSPATH'));
$validator->validate();
?>