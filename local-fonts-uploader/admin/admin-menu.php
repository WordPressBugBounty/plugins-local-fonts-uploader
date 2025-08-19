<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Local_Fonts_Uploader_Admin_Menu', false ) ) {
	class Local_Fonts_Uploader_Admin_Menu {

		private static $instance;

		public static function get_instance() {

			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function __construct() {

			self::$instance = $this;

			add_action( 'admin_menu', [ $this, 'register_page_panel' ], 2900 );
			add_filter( 'ruby_dashboard_menu', [ $this, 'submenu' ], 10, 1 );
			add_filter( 'wp_check_filetype_and_ext', [ $this, 'filter_mime_types' ], 10, 4 );
			add_filter( 'plugin_action_links', [ $this, 'add_plugin_setting_link' ], 10, 2 );
		}

		/**
		 * Enqueues the necessary admin scripts and styles for the Local Fonts Uploader plugin.
		 * @return void
		 */
		public function admin_enqueue() {

			wp_register_style( 'lfontsup-vendor-style', LOCAL_FONTS_UPLOADER_URL . 'admin/assets/vendor.min.css', [], LOCAL_FONTS_UPLOADER_VERSION );
			wp_register_style( 'lfontsup-style', LOCAL_FONTS_UPLOADER_URL . 'admin/assets/main.min.css', [ 'lfontsup-vendor-style' ], LOCAL_FONTS_UPLOADER_VERSION );
			wp_register_script( 'lfontsup-vendor', LOCAL_FONTS_UPLOADER_URL . 'admin/assets/vendor.bundle.js', [], LOCAL_FONTS_UPLOADER_VERSION, true );
			wp_register_script( 'lfontsup-admin', LOCAL_FONTS_UPLOADER_URL . 'admin/assets/main.bundle.js', [ 'lfontsup-vendor' ], LOCAL_FONTS_UPLOADER_VERSION, true );

			wp_localize_script( 'lfontsup-admin', 'lfontsupAdminConfig',
				[
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'local-fonts-uploader' ),
					'translate'     => local_fonts_uploader_strings(),
					'uploadedFonts' => Local_Fonts_Uploader_Data::get_fonts(),
					'isRtl'         => is_rtl(),
				]
			);

			wp_enqueue_media();

			wp_enqueue_style( 'lfontsup-style' );
			wp_enqueue_script( 'lfontsup-admin' );
		}

		/**
		 * Registers the menu page and submenu page for the Local Fonts Uploader plugin.
		 *
		 * This function checks if the `foxiz-core` plugin is active. If it is, it adds the submenu page under the `foxiz-admin` menu.
		 * Otherwise, it adds the menu page to the WordPress admin sidebar. It also hooks the `load-assets` action to load necessary assets.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function register_page_panel() {

			if ( is_plugin_active( 'foxiz-core/foxiz-core.php' ) ) {
				$panel_hook_suffix = add_submenu_page(
					'foxiz-admin',
					esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
					esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
					'manage_options',
					'local-fonts-uploader',
					[ $this, 'render_menu_page' ],
					101
				);
			} else {
				$panel_hook_suffix = add_menu_page(
					esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
					esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
					'manage_options',
					'local-fonts-uploader',
					[ $this, 'render_menu_page' ],
					'data:image/svg+xml;base64,' . $this->get_plugin_icon(),
					99
				);
			}

			add_action( 'load-' . $panel_hook_suffix, [ $this, 'load_assets' ], 10 );
		}

		/**
		 * Adds a settings link to the plugin action links in the WordPress plugins list.
		 *
		 * @param array $links Existing plugin action links.
		 * @param string $file The plugin file path.
		 *
		 * @return array Modified plugin action links.
		 */
		function add_plugin_setting_link( $links, $file ) {

			if ( $file === 'local-fonts-uploader/local-fonts-uploader.php' && current_user_can( 'manage_options' ) ) {
				$links[] = '<a href="admin.php?page=local-fonts-uploader">' . esc_html__( 'Settings', 'local-fonts-uploader' ) . '</a>';
			}

			return $links;
		}

		/**
		 * Hooks the asset loading function to the `admin_enqueue_scripts` action.
		 *
		 * This function registers the `admin_enqueue` method to load the necessary assets (scripts, styles) for the admin pages of the plugin.
		 *
		 * @return void
		 * @since 1.0.0
		 *
		 */
		public function load_assets() {

			// Allow uploading font files in WordPress Media Library
			add_filter( 'upload_mimes', [ $this, 'extend_allowed_font_mimes' ] );

			// Enqueue admin scripts and styles
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue' ] );

		}

		/**
		 * Extend the list of allowed MIME types to include font file formats.
		 *
		 * @param array $mimes List of currently allowed MIME types.
		 *
		 * @return array Modified list of allowed MIME types.
		 */
		public function extend_allowed_font_mimes( $mimes ) {

			$mimes['woff']  = 'application/x-font-woff';
			$mimes['woff2'] = 'application/x-font-woff2';
			$mimes['ttf']   = 'application/x-font-ttf';
			$mimes['otf']   = 'application/x-font-otf';
			$mimes['eot']   = 'application/vnd.ms-fontobject';

			return $mimes;
		}

		/**
		 * Returns the plugin's SVG icon.
		 *
		 * @return string Base64 encoded SVG icon.
		 */
		function get_plugin_icon() {
			return 'PHN2ZyB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBmaWxsPSIjYTdhYWFkIj48cGF0aCBkPSJtMzYyIDBoLTIxMmExNTAgMTUwIDAgMCAwIC0xNTAgMTUwdjIxMmExNTAgMTUwIDAgMCAwIDE1MCAxNTBoMjEyYTE1MCAxNTAgMCAwIDAgMTUwLTE1MHYtMjEyYTE1MCAxNTAgMCAwIDAgLTE1MC0xNTB6bS0yNSAzNzIuMThoLTUxLjkzdi0xMzAuODlhMTc0IDE3NCAwIDAgMCAtMjYuMzItOC44NCAxNzEuNTIgMTcxLjUyIDAgMCAwIC0yMy4wOC0zLjc4cS00LjMyIDE2LjU5LTcuNTcgMzMuNzF0LTUuNDEgMzMuOXEtMi4xNiAxNi43NS0zLjI1IDMyLjYzdC0xLjA4IDI5LjIxYTIwMC43NiAyMDAuNzYgMCAwIDAgMS4yNSAyNC44OCA4Ni43MyA4Ni43MyAwIDAgMCAzLjIgMTUuNjggMzQuOTEgMzQuOTEgMCAwIDAgNC4yNiA5LjJxMi4zMSAzLjI0IDQuMDkgNS40YTE4My44MyAxODMuODMgMCAwIDEgLTE5LjIzLTEuMDggNDEuMjggNDEuMjggMCAwIDEgLTE5LjA1LTdxLTguODEtNS45NC0xNC41Ni0xOC4yMXQtNS43NS0zNWEyMTQuMTIgMjE0LjEyIDAgMCAxIDEuOC0yNi4zM3ExLjgtMTQuNDIgNS0zMC40N3Q4LjE2LTMzLjE5cTQuODgtMTcuMTEgMTEtMzQuMDctMTcuMzEgMS4wOC0yNy43NiA1LjR0LTEyLjI2IDEzLjcxcTEuOCAwIDQuNjggNC41YTE2LjQxIDE2LjQxIDAgMCAxIDIuODkgOC44NHEwIDUuNC01LjA1IDguNjVhMjIuOTMgMjIuOTMgMCAwIDEgLTEyLjYyIDMuMjUgMzAuMzkgMzAuMzkgMCAwIDEgLTE4LjkzLTYuNDlxLTguNDgtNi40Ny04LjQ4LTE5LjA5IDAtMTcuMzEgMTcuNjQtMjguNDl0NDYuNDQtMTEuMjFxOC42NCAwIDIxLjI0IDEuMDhhMzUwLjQyIDM1MC40MiAwIDAgMSAyMC4xNi00MC45MiAyMDYuNzMgMjA2LjczIDAgMCAxIDI0LjExLTMzLjkgMTE0LjkzIDExNC45MyAwIDAgMSAyNy40MS0yMy4wNCA1OC43OCA1OC43OCAwIDAgMSAzMC4yNS04LjQ4IDEwNS40NyAxMDUuNDcgMCAwIDEgMTMuNDkgMS4wOCA2NC4xNyA2NC4xNyAwIDAgMSAxNS4zIDR6Ii8+PHBhdGggZD0ibTI1OS41NSAxNTkuOHEtMTEuNTUgMjMuMDctMTkuODMgNTMuMzYgMTEuMTcgMi4xNiAyMi41NCA1LjIzdDIyLjUzIDdsLjI4LTk3LjcycS0xMy45OCA5LjA2LTI1LjUyIDMyLjEzeiIvPjwvc3ZnPg0KDQo=';
		}

		/**
		 * Adds "Local Fonts Uploader" menu item to the dashboard menu.
		 *
		 * This function checks if the "more" menu exists and, if so, adds a new submenu item for the "Local Fonts Uploader" plugin.
		 * The new item will be linked to the plugin's settings page in the admin panel, with an associated icon and title.
		 *
		 * @param array $menu The existing menu array.
		 *
		 * @return array The modified menu array with the added "Local Fonts Uploader" submenu item.
		 * @since 1.0.0
		 *
		 */
		public function submenu( $menu ) {

			if ( isset( $menu['more'] ) ) {
				$menu['more']['sub_items']['lfontsup'] = [
					'title' => esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
					'icon'  => 'rbi-dash rbi-dash-font-o',
					'url'   => admin_url( 'admin.php?page=local-fonts-uploader' ),
				];
			}

			return $menu;
		}

		/**
		 * Renders the "Local Fonts Uploader" menu page in the WordPress admin panel supported for RubyTheme themes
		 *
		 * This function checks if the `RB_ADMIN_CORE` class is available. If so, it calls the `header_template()` method to render the page header.
		 * Then, it includes the dashboard template file for the plugin, which contains the HTML structure for the admin page.
		 *
		 * @since 1.0.0
		 */
		public function render_menu_page() {

			if ( class_exists( 'RB_ADMIN_CORE' ) ) {
				RB_ADMIN_CORE::get_instance()->header_template();
			}

			$this->dashboard_template();
		}

		/**
		 * Renders the dashboard template for the Local Fonts Uploader plugin.
		 *
		 * This function outputs a placeholder `<div>` element with an ID of `local-fonts-uploader-app`,
		 * which is intended to be used as a mounting point for a JavaScript-based UI.
		 *
		 * @return void
		 */
		public function dashboard_template() {
			echo '<div id="local-fonts-uploader-app"></div>';
		}

		/**
		 * Updates the MIME types for specific font file extensions.
		 *
		 * @param array $defaults Default MIME type and extension.
		 * @param string $file The file path.
		 * @param string $filename The file name.
		 *
		 * @return array Modified MIME type and extension.
		 */
		public function filter_mime_types( $defaults, $file, $filename ) {

			$ext = pathinfo( $filename, PATHINFO_EXTENSION );

			$mime_types = [
				'woff'  => 'application/x-font-woff',
				'woff2' => 'application/x-font-woff2',
				'ttf'   => 'application/x-font-ttf',
				'otf'   => 'application/x-font-otf',
				'eot'   => 'application/vnd.ms-fontobject',
			];

			if ( isset( $mime_types[ $ext ] ) ) {
				$defaults['type'] = $mime_types[ $ext ];
				$defaults['ext']  = $ext;
			}

			return $defaults;
		}
	}
}


// init Local_Fonts_Uploader_Admin_Menu
Local_Fonts_Uploader_Admin_Menu::get_instance();
