<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'school');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', '1149tony');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8mb4');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'cO&V1S>CN0DeJbZg3p?$ {/B9?8V/y7XCM3gMgXk)n:aHX@L`sb2RPYjgfe7-:f_');
define('SECURE_AUTH_KEY',  '2QjyE^<,UPVDiN~XhG_{z/HeyU[a8 Z47eJ_[q0w0Vv86A5?%[{=02L,>GeX/:G ');
define('LOGGED_IN_KEY',    'u*s4>||YRJnI<.KhFxA/rNCwN>PIolK-O/I$LaedYyPZZL)ga!RMv/UVCd4b?ZZP');
define('NONCE_KEY',        '9f>aQoGfSX>[R=CukC.(TQKrme^/d![9y;RU}7Kv$BycL(x2 B65*z{|+u##$BD{');
define('AUTH_SALT',        'Qa{0#^<GAi|Z8wItE:KTp!jvM*;yjFUm,5a 00chJ.mAz T/xa}}?R3.-V{yT@{b');
define('SECURE_AUTH_SALT', 's?$%1teHDNDH_i?PBlI(iJc3OaqM7ZS1O~(<b4bz.eCg~Y<XH=x58]F}AnCc>$!-');
define('LOGGED_IN_SALT',   '>60}+=*K_[,.(rh%<,f !E<f{.8s/iSe1Tn3rE2c}XYss+UPE,,H0Q/TMQyH 5!h');
define('NONCE_SALT',       '5G+3[_ADi6}C8AW,+=X}.St6v4oV]&2&`|!1m@.&fE%{ER>5g@vkdKLz=tJ#Eby%');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
