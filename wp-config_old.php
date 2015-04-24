<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'kitlist_wp');

/** MySQL database username */
define('DB_USER', 'kitlist');

/** MySQL database password */
define('DB_PASSWORD', 'kitsuesg1');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

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
define('AUTH_KEY',         'zRNRgU}S6rf@q`p_|VWzOr,@w-&Cp#U|e@|.sV_@k|I]l=?SN{m|w9F;7|Ldu@=}');
define('SECURE_AUTH_KEY',  ')/!0p?5=B1Fr7N~2+m-X8!=3cn8]e==$sS1M?gpB/GwyTAJVv;*uu`btN2&R}2}0');
define('LOGGED_IN_KEY',    '!4,4>[@m-HA{s> KG~}WPNjF<W1.x(! e:B@#7; >~f4cvIZ>4IYD3{YFwgn~GAv');
define('NONCE_KEY',        'Ks1_{j,QYE&p$sxQ8sbUKPn-R5tn<Vmb53%d3d+|GGW0*n[?:;=n{2|iJo^#Q8eS');
define('AUTH_SALT',        '=Z|@m %}/@KYx-rg:SL4qGsW){fK{WI~p9]NsXUqsrO&,^S winR)v4Q,NEGJSEG');
define('SECURE_AUTH_SALT', 'vn;XYWQN91)Fl|dp`,V` $#:-J)^aT1Kh)UyxpTgVd&Lt|(:uyh@xYFfruEXWY=|');
define('LOGGED_IN_SALT',   '>z6!z74HqmgnG|BMw|fbcK.CKoo[spr-|U{:!r  &{<3{~^uVl9sddrY#0+jzGX:');
define('NONCE_SALT',       'ILz 88#kf]h?zJ$%1]T$lravhM,|=ViV?tbf?fKjTe6b+iH%0?3ZF0O4}y?@fxla');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_kit_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
