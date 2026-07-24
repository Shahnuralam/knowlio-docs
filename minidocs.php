<?php
/**
 * Plugin Name:       MiniDocs
 * Plugin URI:        https://github.com/Shahnuralam/minidocs
 * Description:       A self-contained knowledge base and documentation plugin. Manage documentation articles and categories in wp-admin with a full rich-text editor, then publish a fast, professional docs site on the front end via the [minidocs] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Shahnur Alam
 * Author URI:        https://shahnur.online
 * Text Domain:       minidocs
 * Domain Path:       /languages
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package MiniDocs
 */

/*
 * MiniDocs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * MiniDocs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with MiniDocs. If not, see <https://www.gnu.org/licenses/gpl-3.0.html>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'MiniDocs' ) ) :

	/**
	 * Main plugin class.
	 *
	 * Mirrors LatePoint's bootstrap: define constants -> include files -> register hooks
	 * -> verify the database schema. Everything else is reached through the router.
	 */
	final class MiniDocs {

		/**
		 * Plugin version.
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Schema version. Bumping this re-runs dbDelta on the next request.
		 *
		 * @var string
		 */
		public $db_version = '1.0.0';

		/**
		 * Singleton instance.
		 *
		 * @var MiniDocs|null
		 */
		protected static $instance = null;

		/**
		 * Get the singleton instance.
		 *
		 * @return MiniDocs
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->define_constants();
			$this->includes();
			$this->init_hooks();

			MdDatabaseHelper::check_db_version();
		}

		/**
		 * Define a constant if it is not defined yet.
		 *
		 * @param string $name  Constant name.
		 * @param mixed  $value Constant value.
		 */
		public function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Plugin directory path, with trailing slash.
		 *
		 * @return string
		 */
		public static function plugin_path() {
			return plugin_dir_path( __FILE__ );
		}

		/**
		 * Plugin directory URL, with trailing slash.
		 *
		 * @return string
		 */
		public static function plugin_url() {
			return plugin_dir_url( __FILE__ );
		}

		/**
		 * Define all plugin constants.
		 */
		public function define_constants() {
			global $wpdb;

			$this->define( 'MINIDOCS_VERSION', $this->version );
			$this->define( 'MINIDOCS_DB_VERSION', $this->db_version );
			$this->define( 'MINIDOCS_BRAND_NAME', 'MiniDocs' );
			$this->define( 'MINIDOCS_TEXT_DOMAIN', 'minidocs' );

			// Paths.
			$this->define( 'MINIDOCS_ABSPATH', self::plugin_path() );
			$this->define( 'MINIDOCS_LIB_ABSPATH', MINIDOCS_ABSPATH . 'lib/' );
			$this->define( 'MINIDOCS_VIEWS_ABSPATH', MINIDOCS_LIB_ABSPATH . 'views/' );
			$this->define( 'MINIDOCS_VIEWS_LAYOUTS_ABSPATH', MINIDOCS_VIEWS_ABSPATH . 'layouts/' );
			$this->define( 'MINIDOCS_VIEWS_PARTIALS_ABSPATH', MINIDOCS_VIEWS_ABSPATH . 'partials/' );

			// URLs.
			$this->define( 'MINIDOCS_PLUGIN_URL', self::plugin_url() );
			$this->define( 'MINIDOCS_STYLESHEETS_URL', MINIDOCS_PLUGIN_URL . 'public/stylesheets/' );
			$this->define( 'MINIDOCS_JAVASCRIPTS_URL', MINIDOCS_PLUGIN_URL . 'public/javascripts/' );

			// Database tables.
			$this->define( 'MINIDOCS_TABLE_ARTICLES', $wpdb->prefix . 'minidocs_articles' );
			$this->define( 'MINIDOCS_TABLE_CATEGORIES', $wpdb->prefix . 'minidocs_categories' );
			$this->define( 'MINIDOCS_TABLE_SETTINGS', $wpdb->prefix . 'minidocs_settings' );

			// Response statuses returned to the JS layer.
			$this->define( 'MINIDOCS_STATUS_SUCCESS', 'success' );
			$this->define( 'MINIDOCS_STATUS_ERROR', 'error' );

			// Article statuses.
			$this->define( 'MINIDOCS_ARTICLE_STATUS_DRAFT', 'draft' );
			$this->define( 'MINIDOCS_ARTICLE_STATUS_PUBLISHED', 'published' );

			// Misc.
			$this->define( 'MINIDOCS_DATETIME_DB_FORMAT', 'Y-m-d H:i:s' );
			$this->define( 'MINIDOCS_DATE_DB_FORMAT', 'Y-m-d' );
			$this->define( 'MINIDOCS_DEFAULT_PER_PAGE', 20 );
			$this->define( 'MINIDOCS_ROUTE_ACTION', 'minidocs_route_call' );
			$this->define( 'MINIDOCS_ADMIN_PAGE_SLUG', 'minidocs' );
		}

		/**
		 * Include every class the plugin needs.
		 *
		 * Load order matters: helpers first (controllers use helper constants in
		 * property defaults), then the base model/controller, then concrete classes.
		 */
		public function includes() {
			// HELPERS.
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/util_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/params_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/router_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/form_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/settings_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/roles_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/database_helper.php';

			require_once MINIDOCS_LIB_ABSPATH . 'helpers/menu_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/articles_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/categories_helper.php';
			require_once MINIDOCS_LIB_ABSPATH . 'helpers/shortcodes_helper.php';

			// MODELS.
			require_once MINIDOCS_LIB_ABSPATH . 'models/model.php';
			require_once MINIDOCS_LIB_ABSPATH . 'models/setting_model.php';
			require_once MINIDOCS_LIB_ABSPATH . 'models/category_model.php';
			require_once MINIDOCS_LIB_ABSPATH . 'models/article_model.php';

			// CONTROLLERS.
			require_once MINIDOCS_LIB_ABSPATH . 'controllers/controller.php';
			require_once MINIDOCS_LIB_ABSPATH . 'controllers/dashboard_controller.php';
			require_once MINIDOCS_LIB_ABSPATH . 'controllers/articles_controller.php';
			require_once MINIDOCS_LIB_ABSPATH . 'controllers/categories_controller.php';
			require_once MINIDOCS_LIB_ABSPATH . 'controllers/settings_controller.php';

			/**
			 * Fires after the core classes are loaded. Addons include their own
			 * models/controllers/helpers here so that they can extend core classes.
			 *
			 * @since 1.0.0
			 * @hook minidocs_includes
			 */
			do_action( 'minidocs_includes' );
		}

		/**
		 * Register WordPress hooks.
		 */
		public function init_hooks() {
			register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'on_deactivate' ) );

			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'admin_menu', array( $this, 'init_menus' ) );

			// Single entry point for every admin-ajax request.
			add_action( 'wp_ajax_' . MINIDOCS_ROUTE_ACTION, array( $this, 'route_call' ) );
			add_action( 'wp_ajax_nopriv_' . MINIDOCS_ROUTE_ACTION, array( $this, 'route_call' ) );

			// Single entry point for non-ajax POSTs and file downloads (CSV export).
			add_action( 'admin_post_' . MINIDOCS_ROUTE_ACTION, array( $this, 'route_call' ) );
			add_action( 'admin_post_nopriv_' . MINIDOCS_ROUTE_ACTION, array( $this, 'route_call' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts_and_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_front_scripts_and_styles' ) );

			// Full-screen takeover: the body class the stylesheet keys off.
			add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 40 );

			// Keep a way back into WordPress once its own menu is hidden.
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 999 );

			/**
			 * Fires while the core registers its hooks. Addons register their own
			 * hooks here, guaranteeing the core classes already exist.
			 *
			 * @since 1.0.0
			 * @hook minidocs_init_hooks
			 */
			do_action( 'minidocs_init_hooks' );
		}

		/**
		 * Runs on `init`.
		 */
		public function init() {
			load_plugin_textdomain( 'minidocs', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
			MdShortcodesHelper::register();
		}

		/**
		 * Activation hook: install tables and seed demo data.
		 */
		public function on_activate() {
			MdDatabaseHelper::install_database();
		}

		/**
		 * Deactivation hook.
		 */
		public function on_deactivate() {
			/**
			 * Fires when the core plugin is deactivated.
			 *
			 * @since 1.0.0
			 * @hook minidocs_on_deactivate
			 */
			do_action( 'minidocs_on_deactivate' );
		}

		/**
		 * Register the single admin menu page. Every admin screen is a route
		 * rendered by this one callback -- exactly like LatePoint.
		 */
		public function init_menus() {
			add_menu_page(
				MdSettingsHelper::get_brand_name(),
				MdSettingsHelper::get_brand_name(),
				'read',
				MINIDOCS_ADMIN_PAGE_SLUG,
				array( $this, 'route_call' ),
				'dashicons-media-document',
				58
			);
		}

		/**
		 * Dispatch the current request to a controller action.
		 */
		public function route_call() {
			$route_name    = MdRouterHelper::get_request_param( 'route_name', 'dashboard__index' );
			$return_format = MdRouterHelper::get_request_param( 'return_format', 'html' );

			MdRouterHelper::call_by_route_name( $route_name, $return_format );

			if ( wp_doing_ajax() || 'admin-post.php' === basename( wp_unslash( $_SERVER['SCRIPT_NAME'] ?? '' ) ) ) {
				exit;
			}
		}

		/**
		 * Mark our own screen so the stylesheet can hide the WordPress chrome
		 * and run full-screen, the way LatePoint does.
		 *
		 * @param string $classes Space separated body classes.
		 *
		 * @return string
		 */
		public function add_admin_body_class( $classes ) {
			if ( $this->is_minidocs_screen() ) {
				$classes .= ' minidocs-admin minidocs-full-page';
			}

			return $classes;
		}

		/**
		 * Is the current request one of our admin screens?
		 *
		 * @return bool
		 */
		private function is_minidocs_screen(): bool {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return is_admin() && isset( $_GET['page'] ) && MINIDOCS_ADMIN_PAGE_SLUG === sanitize_key( wp_unslash( $_GET['page'] ) );
		}

		/**
		 * Add a MiniDocs entry to the WordPress admin bar.
		 *
		 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
		 */
		public function add_admin_bar_link( $wp_admin_bar ) {
			if ( ! MdRolesHelper::current_user_can( array( 'article__view' ) ) ) {
				return;
			}

			$wp_admin_bar->add_node(
				array(
					'id'    => 'minidocs_top_link',
					'title' => esc_html( MdSettingsHelper::get_brand_name() ),
					'href'  => MdRouterHelper::build_link( array( 'dashboard', 'index' ) ),
				)
			);
		}

		/**
		 * Enqueue admin assets, but only on our own screen.
		 *
		 * @param string $hook Current admin page hook.
		 */
		public function load_admin_scripts_and_styles( $hook ) {
			if ( false === strpos( (string) $hook, MINIDOCS_ADMIN_PAGE_SLUG ) ) {
				return;
			}

			wp_enqueue_style( 'minidocs-admin', MINIDOCS_STYLESHEETS_URL . 'admin.css', array(), MINIDOCS_VERSION );
			wp_enqueue_script( 'minidocs-admin', MINIDOCS_JAVASCRIPTS_URL . 'admin.js', array( 'jquery' ), MINIDOCS_VERSION, true );

			// The article editor is a real TinyMCE instance built by JS after the
			// side panel is injected. wp_enqueue_editor() ships the wp.editor API
			// and TinyMCE; wp_enqueue_media() powers the Add Media button and the
			// drag-and-drop / paste image upload.
			wp_enqueue_editor();
			wp_enqueue_media();

			$vars = array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'route_action' => MINIDOCS_ROUTE_ACTION,
				'status'       => array(
					'success' => MINIDOCS_STATUS_SUCCESS,
					'error'   => MINIDOCS_STATUS_ERROR,
				),
				'templates'    => MdArticlesHelper::get_content_templates(),
				'i18n'         => array(
					'confirm_delete'   => __( 'Are you sure you want to delete this record?', 'minidocs' ),
					'saving'           => __( 'Saving...', 'minidocs' ),
					'error'            => __( 'Something went wrong. Please try again.', 'minidocs' ),
					'copied'           => __( 'Copied', 'minidocs' ),
					'copy'             => __( 'Copy', 'minidocs' ),
					'no_results'       => __( 'No matching pages', 'minidocs' ),
					'confirm_template' => __( 'Replace the current content with this template?', 'minidocs' ),
				),
			);

			/**
			 * Filters the variables localized for the admin JS bundle.
			 *
			 * @since 1.0.0
			 * @hook minidocs_localized_vars_admin
			 *
			 * @param array $vars Localized variables.
			 */
			$vars = apply_filters( 'minidocs_localized_vars_admin', $vars );
			wp_localize_script( 'minidocs-admin', 'minidocs_helper', $vars );

			/**
			 * Fires after the core admin assets are enqueued, so addons can enqueue theirs.
			 *
			 * @since 1.0.0
			 * @hook minidocs_admin_enqueue_scripts
			 */
			do_action( 'minidocs_admin_enqueue_scripts' );
		}

		/**
		 * Enqueue frontend assets. Registered always, printed only when a
		 * MiniDocs shortcode is rendered (see MdShortcodesHelper).
		 */
		public function load_front_scripts_and_styles() {
			// Depend on dashicons: unlike the admin, the frontend does not load it automatically, and the category icons are dashicons.
			wp_register_style( 'minidocs-front', MINIDOCS_STYLESHEETS_URL . 'front.css', array( 'dashicons' ), MINIDOCS_VERSION );
			wp_register_script( 'minidocs-front', MINIDOCS_JAVASCRIPTS_URL . 'front.js', array(), MINIDOCS_VERSION, true );

			$vars = array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'route_action' => MINIDOCS_ROUTE_ACTION,
				'i18n'         => array(
					'copied'     => __( 'Copied', 'minidocs' ),
					'copy'       => __( 'Copy', 'minidocs' ),
					'no_results' => __( 'No matching pages', 'minidocs' ),
				),
			);

			/**
			 * Filters the variables localized for the frontend JS bundle.
			 *
			 * @since 1.0.0
			 * @hook minidocs_localized_vars_front
			 *
			 * @param array $vars Localized variables.
			 */
			$vars = apply_filters( 'minidocs_localized_vars_front', $vars );
			wp_localize_script( 'minidocs-front', 'minidocs_helper', $vars );

			/**
			 * Fires after the core frontend assets are registered.
			 *
			 * @since 1.0.0
			 * @hook minidocs_wp_enqueue_scripts
			 */
			do_action( 'minidocs_wp_enqueue_scripts' );
		}
	}

endif;

/**
 * Boot the plugin.
 *
 * @return MiniDocs
 */
function minidocs() {
	return MiniDocs::instance();
}

minidocs();
