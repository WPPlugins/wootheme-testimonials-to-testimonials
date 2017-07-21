<?php
/**
 * Plugin Name: Testimonials Widget – WooTheme Testimonials Migrator
 * Plugin URI: http://wordpress.org/plugins/wootheme-testimonials-to-testimonials/
 * Description: Migrate WooTheme Testimonials entries to Testimonials custom post types.
 * Version: 1.2.1
 * Author: Axelerant
 * Author URI: https://axelerant.com/
 * License: GPLv2 or later
 * Text Domain: wootheme-testimonials-to-testimonials
 * Domain Path: /languages
 */


/**
 * Copyright 2015 Axelerant
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

define( 'WTT2T_BASE', plugin_basename( __FILE__ ) );
define( 'WTT2T_DIR', plugin_dir_path( __FILE__ ) );
define( 'WTT2T_DIR_INC', WTT2T_DIR . 'includes/' );
define( 'WTT2T_DIR_LIB', WTT2T_DIR_INC . 'libraries/' );
define( 'WTT2T_NAME', 'WooTheme Testimonials to Testimonials' );
define( 'WTT2T_REQ_BASE', 'testimonials-widget/testimonials-widget.php' );
define( 'WTT2T_REQ_BASE_PREM', 'testimonials-widget-premium/testimonials-widget-premium.php' );
define( 'WTT2T_REQ_NAME', 'Testimonials Widget' );
define( 'WTT2T_REQ_SLUG', 'testimonials-widget' );
define( 'WTT2T_REQ_VERSION', '3.2.0' );
define( 'WTT2T_VERSION', '1.2.1' );

if ( defined( 'TW_DIR_LIB' ) ) {
	define( 'WTT2T_DIR_LIB_ALT', TW_DIR_LIB );
} else {
	define( 'WTT2T_DIR_LIB_ALT', WP_PLUGIN_DIR . '/testimonials-widget/includes/libraries/' );
}

require_once WTT2T_DIR_INC . 'requirements.php';

if ( ! wtt2t_requirements_check() ) {
	return false;
}

require_once WTT2T_DIR_INC . 'class-wootheme-testimonials-to-testimonials.php';


add_action( 'plugins_loaded', 'wootheme_testimonials_to_testimonials_init', 99 );


/**
 *
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
if ( ! function_exists( 'wootheme_testimonials_to_testimonials_init' ) ) {
	function wootheme_testimonials_to_testimonials_init() {
		if ( ! is_admin() )
			return;

		if ( ! function_exists( 'add_screen_meta_link' ) )
			require_once WTT2T_DIR_LIB . 'screen-meta-links.php';

		if ( Wootheme_Testimonials_to_Testimonials::version_check() ) {
			global $Wootheme_Testimonials_to_Testimonials;
			if ( is_null( $Wootheme_Testimonials_to_Testimonials ) )
				$Wootheme_Testimonials_to_Testimonials = new Wootheme_Testimonials_to_Testimonials();

			global $Wootheme_Testimonials_to_Testimonials_Settings;
			if ( is_null( $Wootheme_Testimonials_to_Testimonials_Settings ) )
				$Wootheme_Testimonials_to_Testimonials_Settings = new Wootheme_Testimonials_to_Testimonials_Settings();
		}
	}
}


register_activation_hook( __FILE__, array( 'Wootheme_Testimonials_to_Testimonials', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'Wootheme_Testimonials_to_Testimonials', 'deactivation' ) );
register_uninstall_hook( __FILE__, array( 'Wootheme_Testimonials_to_Testimonials', 'uninstall' ) );


?>
