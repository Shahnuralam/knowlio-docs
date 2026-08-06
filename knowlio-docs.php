<?php
/**
 * Plugin Name:       Knowlio Docs
 * Plugin URI:        https://github.com/Shahnuralam/knowlio-docs
 * Description:       A self-contained knowledge base and documentation plugin. Manage documentation articles and categories in wp-admin with a full rich-text editor, then publish a fast, professional docs site on the front end via the [knowlio] shortcode.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Shahnur Alam
 * Author URI:        https://shahnuralam.github.io/
 * Text Domain:       minidocs
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package KnowlioDocs
 */

/*
 * Knowlio Docs is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Knowlio Docs is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Knowlio Docs. If not, see <https://www.gnu.org/licenses/gpl-3.0.html>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'KnowlioDocs' ) ) :

	/**
	 * Main plugin class.
	 *
	 * Bootstrap order: define constants -> include files -> register hooks. The
	 * schema check runs on `init`, once translations are available, so a seeded
	 * install never loads a text domain too early. Everything else is reached
	 * through the router.
	 */
	final class KnowlioDocs {

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
		 * @var KnowlioDocs|null
		 */
		protected static $instance = null;

		/**
		 * Get the singleton instance.
		 *
		 * @return KnowlioDocs
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
		}

		/**
		 * Define a constant if it is not defined yet.
		 *
		 * @param string $name  Constant name.
		 * @param mixed  $value Constant value.
		 */
		public function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound -- Every caller passes a literal KNOWLIO_* name; see define_constants().
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

			$this->define( 'KNOWLIO_VERSION', $this->version );
			$this->define( 'KNOWLIO_DB_VERSION', $this->db_version );
			$this->define( 'KNOWLIO_BRAND_NAME', 'Knowlio Docs' );

			// Paths.
			$this->define( 'KNOWLIO_ABSPATH', self::plugin_path() );
			$this->define( 'KNOWLIO_LIB_ABSPATH', KNOWLIO_ABSPATH . 'lib/' );
			$this->define( 'KNOWLIO_VIEWS_ABSPATH', KNOWLIO_LIB_ABSPATH . 'views/' );
			$this->define( 'KNOWLIO_VIEWS_LAYOUTS_ABSPATH', KNOWLIO_VIEWS_ABSPATH . 'layouts/' );
			$this->define( 'KNOWLIO_VIEWS_PARTIALS_ABSPATH', KNOWLIO_VIEWS_ABSPATH . 'partials/' );

			// URLs.
			$this->define( 'KNOWLIO_PLUGIN_URL', self::plugin_url() );
			$this->define( 'KNOWLIO_STYLESHEETS_URL', KNOWLIO_PLUGIN_URL . 'public/stylesheets/' );
			$this->define( 'KNOWLIO_JAVASCRIPTS_URL', KNOWLIO_PLUGIN_URL . 'public/javascripts/' );

			// Database tables.
			$this->define( 'KNOWLIO_TABLE_ARTICLES', $wpdb->prefix . 'knowlio_articles' );
			$this->define( 'KNOWLIO_TABLE_CATEGORIES', $wpdb->prefix . 'knowlio_categories' );
			$this->define( 'KNOWLIO_TABLE_SETTINGS', $wpdb->prefix . 'knowlio_settings' );

			// Response statuses returned to the JS layer.
			$this->define( 'KNOWLIO_STATUS_SUCCESS', 'success' );
			$this->define( 'KNOWLIO_STATUS_ERROR', 'error' );

			// Article statuses.
			$this->define( 'KNOWLIO_ARTICLE_STATUS_DRAFT', 'draft' );
			$this->define( 'KNOWLIO_ARTICLE_STATUS_PUBLISHED', 'published' );

			// Misc.
			$this->define( 'KNOWLIO_DATETIME_DB_FORMAT', 'Y-m-d H:i:s' );
			$this->define( 'KNOWLIO_DEFAULT_PER_PAGE', 20 );
			$this->define( 'KNOWLIO_ROUTE_ACTION', 'knowlio_route_call' );
			$this->define( 'KNOWLIO_ADMIN_PAGE_SLUG', 'knowlio-docs' );
		}

		/**
		 * Include every class the plugin needs.
		 *
		 * Load order matters: helpers first (controllers use helper constants in
		 * property defaults), then the base model/controller, then concrete classes.
		 */
		public function includes() {
			// HELPERS.
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/util_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/params_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/router_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/form_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/settings_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/roles_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/database_helper.php';

			require_once KNOWLIO_LIB_ABSPATH . 'helpers/menu_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/articles_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/categories_helper.php';
			require_once KNOWLIO_LIB_ABSPATH . 'helpers/shortcodes_helper.php';

			// MODELS.
			require_once KNOWLIO_LIB_ABSPATH . 'models/model.php';
			require_once KNOWLIO_LIB_ABSPATH . 'models/setting_model.php';
			require_once KNOWLIO_LIB_ABSPATH . 'models/category_model.php';
			require_once KNOWLIO_LIB_ABSPATH . 'models/article_model.php';

			// CONTROLLERS.
			require_once KNOWLIO_LIB_ABSPATH . 'controllers/controller.php';
			require_once KNOWLIO_LIB_ABSPATH . 'controllers/dashboard_controller.php';
			require_once KNOWLIO_LIB_ABSPATH . 'controllers/articles_controller.php';
			require_once KNOWLIO_LIB_ABSPATH . 'controllers/categories_controller.php';
			require_once KNOWLIO_LIB_ABSPATH . 'controllers/settings_controller.php';

			/**
			 * Fires after the core classes are loaded. Addons include their own
			 * models/controllers/helpers here so that they can extend core classes.
			 *
			 * @since 1.0.0
			 * @hook knowlio_includes
			 */
			do_action( 'knowlio_includes' );
		}

		/**
		 * Register WordPress hooks.
		 */
		public function init_hooks() {
			register_activation_hook( __FILE__, array( $this, 'on_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'on_deactivate' ) );

			add_action( 'init', array( $this, 'init' ), 0 );
			add_action( 'admin_menu', array( $this, 'init_menus' ) );

			/*
			 * Single entry point for every admin-ajax request, and one for non-ajax
			 * POSTs and file downloads (CSV export).
			 *
			 * The `nopriv` twins are deliberately not registered: every route is
			 * behind a capability, so an anonymous caller could only ever be told
			 * "Not Authorized". Leaving them off keeps that surface closed.
			 */
			add_action( 'wp_ajax_' . KNOWLIO_ROUTE_ACTION, array( $this, 'route_call' ) );
			add_action( 'admin_post_' . KNOWLIO_ROUTE_ACTION, array( $this, 'route_call' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts_and_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_front_scripts_and_styles' ) );

			// Category article counts are batched per request; a write invalidates them.
			add_action( 'knowlio_model_save', array( 'KnowlioCategoriesHelper', 'flush_count_cache' ) );
			add_action( 'knowlio_model_deleted', array( 'KnowlioCategoriesHelper', 'flush_count_cache' ) );

			// Full-screen takeover: the body class the stylesheet keys off.
			add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 40 );

			// Keep a way back into WordPress once its own menu is hidden.
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_link' ), 999 );

			/**
			 * Fires while the core registers its hooks. Addons register their own
			 * hooks here, guaranteeing the core classes already exist.
			 *
			 * @since 1.0.0
			 * @hook knowlio_init_hooks
			 */
			do_action( 'knowlio_init_hooks' );
		}

		/**
		 * Runs on `init`.
		 */
		public function init() {
			/*
			 * Activation hooks do not fire on plugin update, so the schema version is
			 * verified here instead. It runs after the text domain is loaded because
			 * a first install seeds translated starter content, and translating
			 * before `init` triggers WordPress' just-in-time loading notice.
			 */
			KnowlioDatabaseHelper::check_db_version();

			KnowlioShortcodesHelper::register();
		}

		/**
		 * Activation hook: install tables and seed demo data.
		 */
		public function on_activate() {
			// Activation runs after `init`, so translating the seeded starter
			// content here is safe: WordPress loads the text domain on demand.
			KnowlioDatabaseHelper::install_database();
		}

		/**
		 * Deactivation hook.
		 */
		public function on_deactivate() {
			/**
			 * Fires when the core plugin is deactivated.
			 *
			 * @since 1.0.0
			 * @hook knowlio_on_deactivate
			 */
			do_action( 'knowlio_on_deactivate' );
		}

		/**
		 * Register the single admin menu page. Every admin screen is a route
		 * rendered by this one callback.
		 *
		 * WordPress can only gate a menu on a real capability, and the plugin's
		 * permissions are its own. Rather than show the menu to everyone who can
		 * `read` and then greet half of them with "Not Authorized", the item is
		 * only registered for users who can actually open it.
		 */
		public function init_menus() {
			if ( ! KnowlioRolesHelper::current_user_can( array( 'article__view' ) ) ) {
				return;
			}

			add_menu_page(
				KnowlioSettingsHelper::get_brand_name(),
				KnowlioSettingsHelper::get_brand_name(),
				'read',
				KNOWLIO_ADMIN_PAGE_SLUG,
				array( $this, 'route_call' ),
				'dashicons-media-document',
				58
			);
		}

		/**
		 * Dispatch the current request to a controller action.
		 */
		public function route_call() {
			$route_name    = KnowlioRouterHelper::get_request_param( 'route_name', 'dashboard__index' );
			$return_format = KnowlioRouterHelper::get_request_param( 'return_format', 'html' );

			KnowlioRouterHelper::call_by_route_name( $route_name, $return_format );

			$script = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';

			if ( wp_doing_ajax() || 'admin-post.php' === basename( $script ) ) {
				exit;
			}
		}

		/**
		 * Mark our own screen so the stylesheet can give the plugin the full
		 * width of the window. The WordPress admin bar is deliberately left in
		 * place: it is the user's way back out of the app.
		 *
		 * @param string $classes Space separated body classes.
		 *
		 * @return string
		 */
		public function add_admin_body_class( $classes ) {
			if ( $this->is_knowlio_screen() ) {
				$classes .= ' knowlio-admin knowlio-full-page';
			}

			return $classes;
		}

		/**
		 * Is the current request one of our admin screens?
		 *
		 * @return bool
		 */
		private function is_knowlio_screen(): bool {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return is_admin() && isset( $_GET['page'] ) && KNOWLIO_ADMIN_PAGE_SLUG === sanitize_key( wp_unslash( $_GET['page'] ) );
		}

		/**
		 * Add a Knowlio Docs entry to the WordPress admin bar.
		 *
		 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
		 */
		public function add_admin_bar_link( $wp_admin_bar ) {
			if ( ! KnowlioRolesHelper::current_user_can( array( 'article__view' ) ) ) {
				return;
			}

			$wp_admin_bar->add_node(
				array(
					'id'    => 'knowlio_top_link',
					'title' => esc_html( KnowlioSettingsHelper::get_brand_name() ),
					'href'  => KnowlioRouterHelper::build_link( array( 'dashboard', 'index' ) ),
				)
			);
		}

		/**
		 * Enqueue admin assets, but only on our own screen.
		 *
		 * @param string $hook Current admin page hook.
		 */
		public function load_admin_scripts_and_styles( $hook ) {
			if ( false === strpos( (string) $hook, KNOWLIO_ADMIN_PAGE_SLUG ) ) {
				return;
			}

			wp_enqueue_style( 'knowlio-admin', KNOWLIO_STYLESHEETS_URL . 'admin.css', array(), KNOWLIO_VERSION );

			// No jQuery dependency: the bundle is plain DOM APIs and fetch().
			wp_enqueue_script( 'knowlio-admin', KNOWLIO_JAVASCRIPTS_URL . 'admin.js', array(), KNOWLIO_VERSION, true );

			// The article editor is a real TinyMCE instance built by JS after the
			// side panel is injected. wp_enqueue_editor() ships the wp.editor API
			// and TinyMCE; wp_enqueue_media() powers the Add Media button and the
			// drag-and-drop / paste image upload.
			wp_enqueue_editor();
			wp_enqueue_media();

			$vars = array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'route_action' => KNOWLIO_ROUTE_ACTION,
				'status'       => array(
					'success' => KNOWLIO_STATUS_SUCCESS,
					'error'   => KNOWLIO_STATUS_ERROR,
				),
				'templates'    => KnowlioArticlesHelper::get_content_templates(),
				'i18n'         => array(
					'confirm_delete'   => __( 'Are you sure you want to delete this record?', 'minidocs' ),
					'saving'           => __( 'Saving...', 'minidocs' ),
					'error'            => __( 'Something went wrong. Please try again.', 'minidocs' ),
					'copied'           => __( 'Copied', 'minidocs' ),
					'confirm_template' => __( 'Replace the current content with this template?', 'minidocs' ),
				),
			);

			/**
			 * Filters the variables localized for the admin JS bundle.
			 *
			 * @since 1.0.0
			 * @hook knowlio_localized_vars_admin
			 *
			 * @param array $vars Localized variables.
			 */
			$vars = apply_filters( 'knowlio_localized_vars_admin', $vars );
			wp_localize_script( 'knowlio-admin', 'knowlio_helper', $vars );

			/**
			 * Fires after the core admin assets are enqueued, so addons can enqueue theirs.
			 *
			 * @since 1.0.0
			 * @hook knowlio_admin_enqueue_scripts
			 */
			do_action( 'knowlio_admin_enqueue_scripts' );
		}

		/**
		 * Enqueue frontend assets. Registered always, printed only when a
		 * Knowlio Docs shortcode is rendered (see KnowlioShortcodesHelper).
		 */
		public function load_front_scripts_and_styles() {
			// Depend on dashicons: unlike the admin, the frontend does not load it automatically, and the category icons are dashicons.
			wp_register_style( 'knowlio-front', KNOWLIO_STYLESHEETS_URL . 'front.css', array( 'dashicons' ), KNOWLIO_VERSION );
			wp_register_script( 'knowlio-front', KNOWLIO_JAVASCRIPTS_URL . 'front.js', array(), KNOWLIO_VERSION, true );

			$vars = array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'route_action' => KNOWLIO_ROUTE_ACTION,
				'i18n'         => array(
					'copied' => __( 'Copied', 'minidocs' ),
					'copy'   => __( 'Copy', 'minidocs' ),
				),
			);

			/**
			 * Filters the variables localized for the frontend JS bundle.
			 *
			 * @since 1.0.0
			 * @hook knowlio_localized_vars_front
			 *
			 * @param array $vars Localized variables.
			 */
			$vars = apply_filters( 'knowlio_localized_vars_front', $vars );
			wp_localize_script( 'knowlio-front', 'knowlio_helper', $vars );

			/**
			 * Fires after the core frontend assets are registered.
			 *
			 * @since 1.0.0
			 * @hook knowlio_wp_enqueue_scripts
			 */
			do_action( 'knowlio_wp_enqueue_scripts' );
		}
	}

endif;

/**
 * Boot the plugin.
 *
 * @return KnowlioDocs
 */
function knowlio_docs() {
	return KnowlioDocs::instance();
}

knowlio_docs();
