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
define( 'DB_NAME', 'phone' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', '' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ',o%9#)Pg5ol.+%OZn~4wT!m.z2dW`p4Mej#*FU=;h(oJ:)fQ_TuT1+#eXmMK)P@?' );
define( 'SECURE_AUTH_KEY',  ']Yu]i}@qv{|: <)*B_JVRB85KF5oR%GXN8%<yy<9$ >?;9I]ByE3zr9Y kZ+A4tq' );
define( 'LOGGED_IN_KEY',    '1424;H[&S]Y~t>B&+C,3wQi$vQ35Dp8Vygqw.N&^UP?$sEn_D.yn(N`#tng1 1_!' );
define( 'NONCE_KEY',        'rc;3%oL4RsUu}8+907[FF]:88LCc$9~T$8C %.6<WJ6#C Rh3:VLWa!4@4Wg2#ZZ' );
define( 'AUTH_SALT',        '2|<oRr:n}0Vd#b|LO#UW1=`E4&e%My$mfVdaEy<J:wMtvRQY8Hm3E!:5G(f<Q+y4' );
define( 'SECURE_AUTH_SALT', 'S$-pI{M1Mp/Zl97e{7KB$3G2s.#>uK}0;iLcUe~MM3c]?m[;VF-?c((`T(>^aYCA' );
define( 'LOGGED_IN_SALT',   ' (o(RnvGG$~tw/tT)JXMuw+egOp]{X)5R}rh=PnPphDOnY7N1 Y;wg,+U^B$*)Pc' );
define( 'NONCE_SALT',       'c&0][DnZp*-E5;Yr5!4vv2[HriCRCv$?_#ZwDno%5^HTT^^d3,yB/`oaR|9)^5]g' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
