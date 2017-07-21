<?php
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

require_once WTT2T_DIR_INC . 'class-wootheme-testimonials-to-testimonials-settings.php';
require_once WTT2T_DIR_LIB_ALT . 'aihrus-framework/includes/class-aihrus-common.php';

if ( class_exists( 'Wootheme_Testimonials_to_Testimonials' ) )
	return;


class Wootheme_Testimonials_to_Testimonials extends Aihrus_Common {
	const BASE    = WTT2T_BASE;
	const ID      = 'wootheme-testimonials-to-testimonials';
	const SLUG    = 'wtt2t_';
	const VERSION = WTT2T_VERSION;

	const WTT_PT = 'testimonial';

	private static $post_types;

	public static $class = __CLASS__;
	public static $menu_id;
	public static $notice_key;
	public static $plugin_assets;
	public static $scripts = array();
	public static $settings_link;
	public static $styles        = array();
	public static $styles_called = false;

	public static $post_id;


	public function __construct() {
		parent::__construct();

		self::$plugin_assets = plugins_url( '/assets/', dirname( __FILE__ ) );
		self::$plugin_assets = self::strip_protocol( self::$plugin_assets );

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
	}


	public static function admin_init() {
		self::update();

		add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_row_meta', array( __CLASS__, 'plugin_row_meta' ), 10, 2 );

		self::$settings_link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . Testimonials_Widget::PT . '&page=' . Wootheme_Testimonials_to_Testimonials_Settings::ID . '">' . __( 'Settings', 'wootheme-testimonials-to-testimonials' ) . '</a>';
	}


	public static function admin_menu() {
		self::$menu_id = add_submenu_page( 'edit.php?post_type=' . Testimonials_Widget::PT, esc_html__( 'WooTheme Testimonials to Testimonials Migrator', 'wootheme-testimonials-to-testimonials' ), esc_html__( 'WTT Migrator', 'wootheme-testimonials-to-testimonials' ), 'manage_options', self::ID, array( __CLASS__, 'user_interface' ) );

		add_action( 'admin_print_scripts-' . self::$menu_id, array( __CLASS__, 'scripts' ) );
		add_action( 'admin_print_styles-' . self::$menu_id, array( __CLASS__, 'styles' ) );

		add_screen_meta_link(
			'wtt2t_settings_link',
			esc_html__( 'WooTheme Testimonials to Testimonials Settings', 'wootheme-testimonials-to-testimonials' ),
			admin_url( 'edit.php?post_type=' . Testimonials_Widget::PT . '&page=' . Wootheme_Testimonials_to_Testimonials_Settings::ID ),
			self::$menu_id,
			array( 'style' => 'font-weight: bold;' )
		);
	}


	public static function init() {
		load_plugin_textdomain( self::ID, false, 'wootheme-testimonials-to-testimonials/languages' );

		add_action( 'wp_ajax_ajax_process_post', array( __CLASS__, 'ajax_process_post' ) );

		self::set_post_types();
		self::styles();
	}


	public static function plugin_action_links( $links, $file ) {
		if ( self::BASE == $file ) {
			array_unshift( $links, self::$settings_link );

			$link = '<a href="' . get_admin_url() . 'edit.php?post_type=' . Testimonials_Widget::PT . '&page=' . self::ID . '">' . esc_html__( 'Migrate', 'wootheme-testimonials-to-testimonials' ) . '</a>';
			array_unshift( $links, $link );
		}

		return $links;
	}


	public static function activation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;
	}


	public static function uninstall() {
		if ( ! current_user_can( 'activate_plugins' ) )
			return;

		global $wpdb;

		require_once WTT2T_DIR_INC . 'class-wootheme-testimonials-to-testimonials-settings.php';

		$delete_data = wtt2t_get_option( 'delete_data', false );
		if ( $delete_data ) {
			delete_option( Wootheme_Testimonials_to_Testimonials_Settings::ID );
			$wpdb->query( 'OPTIMIZE TABLE `' . $wpdb->options . '`' );
		}
	}


	public static function plugin_row_meta( $input, $file ) {
		if ( self::BASE != $file )
			return $input;

		$disable_donate = tw_get_option( 'disable_donate' );
		if ( $disable_donate )
			return $input;

		$links = array(
			self::$donate_link,
		);

		global $TW_Premium;
		if ( ! isset( $TW_Premium ) )
			$links[] = TW_PREMIUM_LINK;

		$input = array_merge( $input, $links );

		return $input;
	}


	public static function set_post_types() {
		self::$post_types   = array();
		self::$post_types[] = self::WTT_PT;
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @codingStandardsIgnoreStart
	 */
	public static function user_interface() {
		// Capability check
		if ( ! current_user_can( 'manage_options' ) )
			wp_die( self::$post_id, esc_html__( "Your user account doesn't have permission to access this.", 'wootheme-testimonials-to-testimonials' ) );

?>

<div id="message" class="updated fade" style="display:none"></div>

<div class="wrap wpsposts">
	<div class="icon32" id="icon-tools"></div>
	<h2><?php _e( 'WooTheme Testimonials to Testimonials Migrator', 'wootheme-testimonials-to-testimonials' ); ?></h2>

<?php
		// If the button was clicked
		if ( ! empty( $_POST[ self::ID ] ) || ! empty( $_REQUEST['posts'] ) ) {
			// Form nonce check
			check_admin_referer( self::ID );

			// Create the list of image IDs
			if ( ! empty( $_REQUEST['posts'] ) ) {
				$posts = explode( ',', trim( $_REQUEST['posts'], ',' ) );
				$posts = array_map( 'intval', $posts );
			} else {
				$posts = self::get_posts_to_process();
			}

			$count = count( $posts );
			if ( ! $count ) {
				echo '	<p>' . _e( 'All done. No posts needing processing found.', 'wootheme-testimonials-to-testimonials' ) . '</p></div>';
				return;
			}

			$posts = implode( ',', $posts );
			self::show_status( $count, $posts );
		} else {
			// No button click? Display the form.
			self::show_greeting();
		}
?>
	</div>
<?php
	}
	// @codingStandardsIgnoreEnd


	public static function get_posts_to_process() {
		global $wpdb;

		$query = array(
			'post_type' => self::$post_types,
			'orderby' => 'post_modified',
			'order' => 'DESC',
		);

		$include_ids = wtt2t_get_option( 'posts_to_import' );
		if ( $include_ids ) {
			$query['post__in'] = str_getcsv( $include_ids );
		} else {
			$query['posts_per_page'] = 1;
			$query['meta_query']     = array(
				array(
					'key' => 'TBD',
					'value' => '',
					'compare' => '!=',
				),
			);
			unset( $query['meta_query'] );
		}

		$skip_ids = wtt2t_get_option( 'skip_importing_post_ids' );
		if ( $skip_ids )
			$query[ 'post__not_in' ] = str_getcsv( $skip_ids );

		$results  = new WP_Query( $query );
		$query_wp = $results->request;

		$limit = wtt2t_get_option( 'limit' );
		if ( $limit )
			$query_wp = preg_replace( '#\bLIMIT 0,.*#', 'LIMIT 0,' . $limit, $query_wp );
		else
			$query_wp = preg_replace( '#\bLIMIT 0,.*#', '', $query_wp );

		$posts = $wpdb->get_col( $query_wp );

		return $posts;
	}


	/**
	 * @codingStandardsIgnoreStart
	 */
	public static function show_greeting() {
?>
	<form method="post" action="">
<?php wp_nonce_field( self::ID ); ?>

	<p><?php _e( 'Use this tool for migrating WooTheme Testimonials entries to Testimonials custom post types.', 'wootheme-testimonials-to-testimonials' ); ?></p>

	<p><?php _e( 'This migration is not reversible. Backup your database beforehand or be prepared to delete each migrated testimonial manually.', 'wootheme-testimonials-to-testimonials' ); ?></p>

	<p><?php printf( esc_html__( 'Please review your %s before proceeding.', 'wootheme-testimonials-to-testimonials' ), self::$settings_link ); ?></p>

	<p><?php _e( 'To begin, just press the button below.', 'wootheme-testimonials-to-testimonials' ); ?></p>

	<p><input type="submit" class="button hide-if-no-js" name="<?php echo self::ID; ?>" id="<?php echo self::ID; ?>" value="<?php _e( 'Migrate WooTheme Testimonials to Testimonials', 'wootheme-testimonials-to-testimonials' ) ?>" /></p>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'wootheme-testimonials-to-testimonials' ) ?></em></p></noscript>

	</form>
<?php
	}
	// @codingStandardsIgnoreEnd


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @codingStandardsIgnoreStart
	 */
	public static function show_status( $count, $posts ) {
		echo '<p>' . esc_html__( 'Please be patient while this script run. This can take a while, up to a minute per post. Do not navigate away from this page until this script is done or the import will not be completed. You will be notified via this page when the import is completed.', 'wootheme-testimonials-to-testimonials' ) . '</p>';

		echo '<p>' . sprintf( esc_html__( 'Estimated time required to import is %1$s minutes.', 'wootheme-testimonials-to-testimonials' ), ( $count * 1 ) ) . '</p>';

		$text_goback = ( ! empty( $_GET['goback'] ) ) ? sprintf( __( 'To go back to the previous page, <a href="%s">click here</a>.', 'wootheme-testimonials-to-testimonials' ), 'javascript:history.go(-1)' ) : '';

		$text_failures = sprintf( __( 'All done! %1$s posts were successfully processed in %2$s seconds and there were %3$s failures. To try importing the failed posts again, <a href="%4$s">click here</a>. %5$s', 'wootheme-testimonials-to-testimonials' ), "' + rt_successes + '", "' + rt_totaltime + '", "' + rt_errors + '", esc_url( wp_nonce_url( admin_url( 'tools.php?page=' . self::ID . '&goback=1' ) ) . '&posts=' ) . "' + rt_failedlist + '", $text_goback );

		$text_nofailures = sprintf( esc_html__( 'All done! %1$s posts were successfully processed in %2$s seconds and there were no failures. %3$s', 'wootheme-testimonials-to-testimonials' ), "' + rt_successes + '", "' + rt_totaltime + '", $text_goback );
?>

	<noscript><p><em><?php _e( 'You must enable Javascript in order to proceed!', 'wootheme-testimonials-to-testimonials' ) ?></em></p></noscript>

	<div id="wpsposts-bar" style="position:relative;height:25px;">
		<div id="wpsposts-bar-percent" style="position:absolute;left:50%;top:50%;width:300px;margin-left:-150px;height:25px;margin-top:-9px;font-weight:bold;text-align:center;"></div>
	</div>

	<p><input type="button" class="button hide-if-no-js" name="wpsposts-stop" id="wpsposts-stop" value="<?php _e( 'Abort Migrating Posts', 'wootheme-testimonials-to-testimonials' ) ?>" /></p>

	<h3 class="title"><?php _e( 'Status', 'wootheme-testimonials-to-testimonials' ) ?></h3>

	<p>
		<?php printf( esc_html__( 'Total Testimonials: %s', 'wootheme-testimonials-to-testimonials' ), $count ); ?><br />
		<?php printf( esc_html__( 'Testimonials Migrated: %s', 'wootheme-testimonials-to-testimonials' ), '<span id="wpsposts-debug-successcount">0</span>' ); ?><br />
		<?php printf( esc_html__( 'Migration Failures: %s', 'wootheme-testimonials-to-testimonials' ), '<span id="wpsposts-debug-failurecount">0</span>' ); ?>
	</p>

	<ol id="wpsposts-debuglist">
		<li style="display:none"></li>
	</ol>

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function($){
			var i;
			var rt_posts = [<?php echo esc_attr( $posts ); ?>];
			var rt_total = rt_posts.length;
			var rt_count = 1;
			var rt_percent = 0;
			var rt_successes = 0;
			var rt_errors = 0;
			var rt_failedlist = '';
			var rt_resulttext = '';
			var rt_timestart = new Date().getTime();
			var rt_timeend = 0;
			var rt_totaltime = 0;
			var rt_continue = true;

			// Create the progress bar
			$( "#wpsposts-bar" ).progressbar();
			$( "#wpsposts-bar-percent" ).html( "0%" );

			// Stop button
			$( "#wpsposts-stop" ).click(function() {
				rt_continue = false;
				$( '#wpsposts-stop' ).val( "<?php echo esc_html__( 'Stopping, please wait a moment.', 'wootheme-testimonials-to-testimonials' ); ?>" );
			});

			// Clear out the empty list element that's there for HTML validation purposes
			$( "#wpsposts-debuglist li" ).remove();

			// Called after each import. Updates debug information and the progress bar.
			function WPSPostsUpdateStatus( id, success, response ) {
				$( "#wpsposts-bar" ).progressbar( "value", ( rt_count / rt_total ) * 100 );
				$( "#wpsposts-bar-percent" ).html( Math.round( ( rt_count / rt_total ) * 1000 ) / 10 + "%" );
				rt_count = rt_count + 1;

				if ( success ) {
					rt_successes = rt_successes + 1;
					$( "#wpsposts-debug-successcount" ).html(rt_successes);
					$( "#wpsposts-debuglist" ).append( "<li>" + response.success + "</li>" );
				}
				else {
					rt_errors = rt_errors + 1;
					rt_failedlist = rt_failedlist + ',' + id;
					$( "#wpsposts-debug-failurecount" ).html(rt_errors);
					$( "#wpsposts-debuglist" ).append( "<li>" + response.error + "</li>" );
				}
			}

			// Called when all posts have been processed. Shows the results and cleans up.
			function WPSPostsFinishUp() {
				rt_timeend = new Date().getTime();
				rt_totaltime = Math.round( ( rt_timeend - rt_timestart ) / 1000 );

				$( '#wpsposts-stop' ).hide();

				if ( rt_errors > 0 ) {
					rt_resulttext = '<?php echo $text_failures; ?>';
				} else {
					rt_resulttext = '<?php echo $text_nofailures; ?>';
				}

				$( "#message" ).html( "<p><strong>" + rt_resulttext + "</strong></p>" );
				$( "#message" ).show();
			}

			// Regenerate a specified image via AJAX
			function WPSPosts( id ) {
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: "ajax_process_post",
						id: id
					},
					success: function( response ) {
						if ( response.success ) {
							WPSPostsUpdateStatus( id, true, response );
						}
						else {
							WPSPostsUpdateStatus( id, false, response );
						}

						if ( rt_posts.length && rt_continue ) {
							WPSPosts( rt_posts.shift() );
						}
						else {
							WPSPostsFinishUp();
						}
					},
					error: function( response ) {
						WPSPostsUpdateStatus( id, false, response );

						if ( rt_posts.length && rt_continue ) {
							WPSPosts( rt_posts.shift() );
						}
						else {
							WPSPostsFinishUp();
						}
					}
				});
			}

			WPSPosts( rt_posts.shift() );
		});
	// ]]>
	</script>
<?php
	}
	// @codingStandardsIgnoreEnd


	/**
	 * Migrate a single post ID (this is an AJAX handler)
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function ajax_process_post() {
		error_reporting( 0 ); // Don't break the JSON result
		header( 'Content-type: application/json' );
		self::$post_id = intval( $_REQUEST['id'] );

		$post = get_post( self::$post_id, ARRAY_A );
		if ( ! $post || ! in_array( $post['post_type'], self::$post_types )  )
			die( json_encode( array( 'error' => sprintf( esc_html__( 'Failed Migration: %s is incorrect post type.', 'wootheme-testimonials-to-testimonials' ), esc_html( self::$post_id ) ) ) ) );

		$result = self::migrate_item( self::$post_id, $post );
		if ( is_numeric( $result ) )
			die( json_encode( array( 'success' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; WooTheme Testimonial ID %3$s was successfully migrated to Testimonials %6$s &quot;<a href="%4$s" target="_blank">%5$s</a>&quot;.', 'wootheme-testimonials-to-testimonials' ), get_permalink( self::$post_id ), esc_html( get_the_title( self::$post_id ) ), self::$post_id, get_permalink( $result ), esc_html( get_the_title( $result ) ), $result ) ) ) );
		else
			die( json_encode( array( 'error' => sprintf( __( '&quot;<a href="%1$s" target="_blank">%2$s</a>&quot; Unable to be migrated.', 'wootheme-testimonials-to-testimonials' ), get_permalink( self::$post_id ), esc_html( get_the_title( self::$post_id ) ) ) ) ) );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
	 */
	public static function migrate_item( $post_id, $post ) {
		$migrated_key  = '_' . Testimonials_Widget::PT;
		$migrated_args = array(
			'post_type' => Testimonials_Widget::PT,
			'meta_query' => array(
				array(
					'key' => $migrated_key,
					'value' => $post_id,
					'type' => 'NUMERIC',
				),
			)
		);

		$migrated = new WP_Query( $migrated_args );
		if ( $migrated->have_posts() ) {
			$migrated->the_post();

			return get_the_ID();
		}

		unset( $post['ID'] );
		unset( $post['guid'] );
		$post['post_type'] = Testimonials_Widget::PT;

		$new_post_id = wp_insert_post( $post, true );
		$post_meta   = get_post_custom( $post_id );

		$fields = array(
			'_byline' => 'testimonials-widget-title',
			'_gravatar_email' => 'testimonials-widget-email',
			'_url' => 'testimonials-widget-url',
		);
		foreach ( $fields as $field => $target ) {
			if ( isset( $post_meta[ $field ][ 0 ] ) )
				add_post_meta( $new_post_id, $target, $post_meta[ $field ][ 0 ] );
		}

		$categories = wp_get_object_terms( $post_id, 'testimonial-category' );
		if ( ! empty( $categories ) ) {
			$use_cpt_taxonomy = tw_get_option( 'use_cpt_taxonomy', false );
			if ( ! $use_cpt_taxonomy )
				$tax_cat = 'category';
			else
				$tax_cat = Testimonials_Widget::$cpt_category;

			foreach ( $categories as $category ) {
				$term = term_exists( $category->name, $tax_cat );
				if ( ! is_array( $term ) )
					$term = wp_insert_term( $category->name, $tax_cat );

				if ( ! is_array( $term ) )
					continue;

				$term_id = intval( $term['term_id'] );
				if ( $term_id )
					wp_set_object_terms( $new_post_id, $term_id, $tax_cat, true );
			}
		}	

		$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
		if ( $thumbnail_id ) {
			$src      = wp_get_attachment_url( $thumbnail_id );
			$image_id = self::add_media( $new_post_id, $src );
		}

		add_post_meta( $new_post_id, $migrated_key, $post_id );

		return $new_post_id;
	}


	public static function notice_1_0_0() {
		$text = sprintf( __( 'If your Migrate WooTheme Testimonials to Testimonials display has gone to funky town, please <a href="%s">read the FAQ</a> about possible CSS fixes.', 'wootheme-testimonials-to-testimonials' ), 'https://nodedesk.zendesk.com/hc/en-us/articles/202244392-Major-Changes-Since-2-10-0' );

		aihr_notice_updated( $text );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public static function notice_donate( $disable_donate = null, $item_name = null ) {
		$disable_donate = tw_get_option( 'disable_donate' );

		parent::notice_donate( $disable_donate, WTT2T_NAME );
	}


	public static function update() {
		$prior_version = wtt2t_get_option( 'admin_notices' );
		if ( $prior_version ) {
			if ( $prior_version < '1.0.0' )
				add_action( 'admin_notices', array( __CLASS__, 'notice_1_0_0' ) );

			if ( $prior_version < self::VERSION ) {
				do_action( 'wtt2t_update' );
			}

			wtt2t_set_option( 'admin_notices' );
		}

		// display donate on major/minor version release
		$donate_version = wtt2t_get_option( 'donate_version', false );
		if ( ! $donate_version || ( $donate_version != self::VERSION && preg_match( '#\.0$#', self::VERSION ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'notice_donate' ) );
			wtt2t_set_option( 'donate_version', self::VERSION );
		}
	}


	public static function scripts( $atts = array() ) {
		if ( is_admin() ) {
			wp_enqueue_script( 'jquery' );

			wp_register_script( 'jquery-ui-progressbar', self::$plugin_assets . 'js/jquery.ui.progressbar.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget' ), '1.10.3' );
			wp_enqueue_script( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'Wootheme_Testimonials_to_Testimonials', 'get_scripts' ) );
		}

		do_action( 'wtt2t_scripts', $atts );
	}


	public static function styles() {
		if ( is_admin() ) {
			wp_register_style( 'jquery-ui-progressbar', self::$plugin_assets . 'css/redmond/jquery-ui-1.10.3.custom.min.css', false, '1.10.3' );
			wp_enqueue_style( 'jquery-ui-progressbar' );

			add_action( 'admin_footer', array( 'Wootheme_Testimonials_to_Testimonials', 'get_styles' ) );
		}

		do_action( 'wtt2t_styles' );
	}


	public static function version_check() {
		$valid_version = true;
		if ( ! $valid_version ) {
			$deactivate_reason = esc_html__( 'Failed version check', 'wootheme-testimonials-to-testimonials' );
			aihr_deactivate_plugin( self::BASE, WTT2T_NAME, $deactivate_reason );
			self::check_notices();
		}

		return $valid_version;
	}


	public static function call_scripts_styles( $atts ) {
		self::scripts( $atts );
	}


	/**
	 *
	 *
	 * @SuppressWarnings(PHPMD.Superglobals)
	 */
	public static function do_load() {
		$do_load = false;
		if ( ! empty( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'edit.php', 'options.php', 'plugins.php' ) ) ) {
			$do_load = true;
		} elseif ( ! empty( $_REQUEST['page'] ) && Wootheme_Testimonials_to_Testimonials_Settings::ID == $_REQUEST['page'] ) {
			$do_load = true;
		} elseif ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$do_load = true;
		}

		return $do_load;
	}


	public static function get_defaults( $single_view = false ) {
		if ( empty( $single_view ) )
			return apply_filters( 'wtt2t_defaults', wtt2t_get_options() );
		else
			return apply_filters( 'wtt2t_defaults_single', wtt2t_get_options() );
	}


}

?>
