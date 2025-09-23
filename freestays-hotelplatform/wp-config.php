<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'shorttrips' );

/** Database username */
define( 'DB_USER', 'shorttripsnew' );

/** Database password */
define( 'DB_PASSWORD', 'Barneveld2025!@' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         '!-1L:bA9SEo,.$(3=Mh5cpGr6s]6=,S+T)bJ+9sb|j|_*lh*7y|Q+3RCyI)3]bl?');
define('SECURE_AUTH_KEY',  '%IUy-Z#FC*aaubP ]wG;6ah7O<CSq}jWBuXEfD&?-6O)$lL|vc}Au$36N/BZmKe:');
define('LOGGED_IN_KEY',    'Rt9NR0$q=y3}bzq9PfiKH+-f+4g+3z. 9J5[,aA+9AG7VqzK8@Ah_e(^^nJ:!CKM');
define('NONCE_KEY',        '|hV%JUzM8;)) x}a~f8H||BX1>z}R(&W;`<FiT~Ll?_-TgF2Oh~TA+1WE!k,eROf');
define('AUTH_SALT',        '|R8`f+x6+;/+*pSM|30V](;q!n1H6{0Jx0^w@+E25BNj-+fCsH3nQ:2RaQa6r6yt');
define('SECURE_AUTH_SALT', 'i_H2nR.XY%:60V!g0-02gF=19c-d/*^>KJZNRU )dn/T-|T1AtrYNJ~hn=)aj6Fk');
define('LOGGED_IN_SALT',   '-CVD&[1{l<,$Y4FM29hCrzK13?@$KSF>VB.,<x?O*QRIdl(:tIyY%6Kr/G[Uwo=W');
define('NONCE_SALT',       'Kmr?:)gX6CBfu-9_NB0yewI3ug0lsgl,OCFj4O*t+ldV@6vX#=m-[+2P*V5b:ri5');

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG', true );

/* Add any custom values between this line and the "stop editing" line. */
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $allowed = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'https://shorttrips.eu',
        'https://www.shorttrips.eu'
    ];
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
