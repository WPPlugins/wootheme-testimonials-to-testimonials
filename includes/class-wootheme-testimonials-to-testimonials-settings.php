<?php
/*
	Copyright 2015 Axelerant

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * WooTheme Testimonials to Testimonials settings class
 *
 * Based upon http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 */

require_once WTT2T_DIR_LIB_ALT . 'aihrus-framework/includes/class-aihrus-settings.php';

if ( class_exists( 'Wootheme_Testimonials_to_Testimonials_Settings' ) )
	return;


class Wootheme_Testimonials_to_Testimonials_Settings extends Aihrus_Settings {
	const ID   = 'wootheme-testimonials-to-testimonials-settings';
	const NAME = 'WooTheme Testimonials to Testimonials Settings';

	public static $admin_page;
	public static $class    = __CLASS__;
	public static $defaults = array();
	public static $plugin_assets;
	public static $plugin_url = 'http://wordpress.org/plugins/wootheme-testimonials-to-testimonials/';
	public static $sections   = array();
	public static $settings   = array();
	public static $version;


	public function __construct() {
		parent::__construct();

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
	}


	public static function admin_init() {
		$version       = wtt2t_get_option( 'version' );
		self::$version = Wootheme_Testimonials_to_Testimonials::VERSION;
		self::$version = apply_filters( 'wtt2t_version', self::$version );

		if ( $version != self::$version )
			self::initialize_settings();

		if ( ! Wootheme_Testimonials_to_Testimonials::do_load() )
			return;

		self::load_options();
		self::register_settings();
	}


	public static function admin_menu() {
		self::$admin_page = add_submenu_page( 'edit.php?post_type=' . Testimonials_Widget::PT, esc_html__( 'WooTheme Testimonials to Testimonials Settings', 'wootheme-testimonials-to-testimonials' ), esc_html__( 'WTT Settings', 'wootheme-testimonials-to-testimonials' ), 'manage_options', self::ID, array( __CLASS__, 'display_page' ) );

		add_action( 'admin_print_scripts-' . self::$admin_page, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$admin_page, array( __CLASS__, 'styles' ) );
		add_action( 'load-' . self::$admin_page, array( __CLASS__, 'settings_add_help_tabs' ) );

		add_screen_meta_link(
			'wsp_importer_link',
			esc_html__( 'WooTheme Testimonials to Testimonials Migrator', 'wootheme-testimonials-to-testimonials' ),
			admin_url( 'edit.php?post_type=' . Testimonials_Widget::PT . '&page=' . Wootheme_Testimonials_to_Testimonials::ID ),
			self::$admin_page,
			array( 'style' => 'font-weight: bold;' )
		);
	}


	public static function init() {
		load_plugin_textdomain( 'wootheme-testimonials-to-testimonials', false, '/wootheme-testimonials-to-testimonials/languages/' );

		self::$plugin_assets = Wootheme_Testimonials_to_Testimonials::$plugin_assets;
	}


	public static function sections() {
		// self::$sections['general'] = esc_html__( 'General', 'wootheme-testimonials-to-testimonials' );
		self::$sections['testing'] = esc_html__( 'Testing', 'wootheme-testimonials-to-testimonials' );

		parent::sections();

		self::$sections = apply_filters( 'wtt2t_sections', self::$sections );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function settings() {
		// Testing
		self::$settings['posts_to_import'] = array(
			'title' => esc_html__( 'WooTheme Testimonials to Import', 'wootheme-testimonials-to-testimonials' ),
			'desc' => esc_html__( "A CSV list of post ids to import, like '1,2,3'.", 'wootheme-testimonials-to-testimonials' ),
			'std' => '',
			'type' => 'text',
			'section' => 'testing',
			'validate' => 'ids',
		);

		self::$settings['skip_importing_post_ids'] = array(
			'title' => esc_html__( 'Skip Importing WooTheme Testimonials', 'wootheme-testimonials-to-testimonials' ),
			'desc' => esc_html__( "A CSV list of post ids to not import, like '1,2,3'.", 'wootheme-testimonials-to-testimonials' ),
			'std' => '',
			'type' => 'text',
			'section' => 'testing',
			'validate' => 'ids',
		);

		self::$settings['limit'] = array(
			'title' => esc_html__( 'Import Limit', 'wootheme-testimonials-to-testimonials' ),
			'desc' => esc_html__( 'Useful for testing import on a limited amount of posts. 0 or blank means unlimited.', 'wootheme-testimonials-to-testimonials' ),
			'std' => '',
			'type' => 'text',
			'section' => 'testing',
			'validate' => 'intval',
		);

		parent::settings();

		self::$settings = apply_filters( 'wtt2t_settings', self::$settings );

		foreach ( self::$settings as $id => $parts )
			self::$settings[ $id ] = wp_parse_args( $parts, self::$default );
	}


	public static function get_defaults( $mode = null, $old_version = null ) {
		$old_version = wtt2t_get_option( 'version' );

		return parent::get_defaults( $mode, $old_version );
	}


	public static function display_page( $disable_donate = false ) {
		$disable_donate = tw_get_option( 'disable_donate' );

		parent::display_page( $disable_donate );
	}


	public static function initialize_settings( $version = null ) {
		$version = wtt2t_get_option( 'version', self::$version );

		parent::initialize_settings( $version );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function validate_settings( $input, $options = null, $do_errors = false ) {
		$validated = parent::validate_settings( $input, $options, $do_errors );

		if ( empty( $do_errors ) )
			$input = $validated;
		else {
			$input  = $validated['input'];
			$errors = $validated['errors'];
		}

		$input['version']        = self::$version;
		$input['donate_version'] = Wootheme_Testimonials_to_Testimonials::VERSION;

		$input = apply_filters( 'wtt2t_validate_settings', $input, $errors );
		if ( empty( $do_errors ) )
			$validated = $input;
		else {
			$validated = array(
				'input' => $input,
				'errors' => $errors,
			);
		}

		return $validated;
	}


	public static function settings_add_help_tabs() {
		$screen = get_current_screen();
		if ( self::$admin_page != $screen->id )
			return;

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'wootheme-testimonials-to-testimonials' ) . '</strong></p><p>' .
			esc_html__( 'These WooTheme Testimonials to Testimonials Settings establish the default option values for migration.', 'wootheme-testimonials-to-testimonials' ) .
			'</p><p>' .
			sprintf(
				__( 'View the <a href="%s">WooTheme Testimonials to Testimonials documentation</a>.', 'wootheme-testimonials-to-testimonials' ),
				esc_url( self::$plugin_url )
			) .
			'</p>'
		);

		$screen->add_help_tab(
			array(
				'id'     => 'tw-general',
				'title'     => esc_html__( 'General', 'wootheme-testimonials-to-testimonials' ),
				'content' => '<p>' . esc_html__( 'Show or hide optional fields.', 'wootheme-testimonials-to-testimonials' ) . '</p>'
			)
		);

		do_action( 'wtt2t_settings_add_help_tabs', $screen );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public static function display_setting( $args = array(), $do_echo = true, $input = null ) {
		$content = apply_filters( 'wtt2t_display_setting', '', $args, $input );
		if ( empty( $content ) )
			$content = parent::display_setting( $args, false, $input );

		if ( ! $do_echo )
			return $content;

		echo $content;
	}


}


function wtt2t_get_options() {
	$options = get_option( Wootheme_Testimonials_to_Testimonials_Settings::ID );

	if ( false === $options ) {
		$options = Wootheme_Testimonials_to_Testimonials_Settings::get_defaults();
		update_option( Wootheme_Testimonials_to_Testimonials_Settings::ID, $options );
	}

	return $options;
}


function wtt2t_get_option( $option, $default = null ) {
	$options = get_option( Wootheme_Testimonials_to_Testimonials_Settings::ID, null );

	if ( isset( $options[ $option ] ) )
		return $options[ $option ];
	else
		return $default;
}


function wtt2t_set_option( $option, $value = null ) {
	$options = get_option( Wootheme_Testimonials_to_Testimonials_Settings::ID );

	if ( ! is_array( $options ) )
		$options = array();

	$options[ $option ] = $value;
	update_option( Wootheme_Testimonials_to_Testimonials_Settings::ID, $options );
}


?>
