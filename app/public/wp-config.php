<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

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
define( 'AUTH_KEY',          'h@Mh`-bWPCx@}<3YDHpFz` )q:}#b+/1VW9d}Q:(mm+o6R&1`0-lNS;nI<^l,A-a' );
define( 'SECURE_AUTH_KEY',   '^<#0*[Pn4<o=<cz027S,zd?2ncxj6pxjO}pvl):}##L9N._jK]tO-[,pwg?wJjYf' );
define( 'LOGGED_IN_KEY',     'u[2P8pLvX?|{V8%r`.Qdo5Ro4s*soQOTQ6x-=&Ctif1td`?6=>3!j0[Os6zYFGCN' );
define( 'NONCE_KEY',         '/99%Pj?bulp}G>bSZ!z:(X~ +gS(Wg~Ca`*F#|<OkpY>6zqO>$@@okyX?>?k,@zb' );
define( 'AUTH_SALT',         ';G5$$Cyq1JyrM}K*b^09vDFQe%lW;yoKp?cuXc2zPKW(@-1Y0P+8ZEp&qc9wM@h;' );
define( 'SECURE_AUTH_SALT',  'x0 eSW%,3@^K:;1w-):hMgOKEfT6e1]}S3-VS,A0KYMfG&DbML59<cQ3yIr@-*xV' );
define( 'LOGGED_IN_SALT',    'RM+N8w2t,RJf$B~$eEquvcj%Bs%}PN@N,lx}J(5*t>D2PA?/BO[*s_e0%Nx9A>i5' );
define( 'NONCE_SALT',        '*9jfV-80jm0wdjUqo8jCl+~~4^$?6z%Ujp>1LM5:W%~FNP[KXGcZ&b-(Q<uYVPz-' );
define( 'WP_CACHE_KEY_SALT', '{ia_H6:!LxegIH&OvH8u`Hn]rOM-+E6EL>xtB#;4!8#)NC?S1FFk}wt(>7A(4Sus' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
