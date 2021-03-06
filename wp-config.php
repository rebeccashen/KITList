<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'kitlist');

/** MySQL database username */
define('DB_USER', 'root');

/** MySQL database password */
define('DB_PASSWORD', 'root');

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
define('AUTH_KEY',         'z4l*0/;)HY-bvp-}3O&hdDp]urHdDHib*!-Jyc$-oEF|i>k1Vx,0O)UF:o(72u](');
define('SECURE_AUTH_KEY',  '>_iD=W%_BPx8n-MOrsJ@WX@SJLP!~%0y}!upql?+of)iQ0>foP)VV TG)Dk]P|VG');
define('LOGGED_IN_KEY',    'qS3+AuJ#GQR0+E-Tdw3nF0eP$TL5?]I;IYk,7Kj~aQ{|@dtp%>xa|EPR0=h{8yw8');
define('NONCE_KEY',        'N9E0E<X+z}PAE$0,5BsYAxqb2y-0G4K?kCuK8zi&iM9&`t%r|g|ymY2-ueq(r8|]');
define('AUTH_SALT',        'Wzih{8YSO7V:!w>yDtlfinq|x2{b!kTr%bt;i:9kso4Qkk4{{+0,6Pj:}42#`3+v');
define('SECURE_AUTH_SALT', 'W!nn+*L6G[*-m)(6*$C-wS$O<DVqEs)0-O;18;K/|#+CSJBM[Q_B-u{ %71[-BYA');
define('LOGGED_IN_SALT',   'drE:Fo-;%,#eLH|z4ioJOBx[]~?xrbOjg3SAY9Md5`9f|K|-f*Xqhv[F7:PTe1|)');
define('NONCE_SALT',       ';~6 bpy)6F-/z*l-6vE?pa*l/;kqa}ALuTEMv-H{ezvxANO|<{7({KO^XsxYNrSh');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

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
