<?php
/**
 *
 * Plugin Name:       Local Fonts Uploader
 * Description:       Easily upload and host fonts locally. Avoid external requests to enhance security, privacy, speed, and GDPR compliance.
 * Plugin URI:        https://localfonts.themeruby.com/
 * Author:            ThemeRuby
 * Tags:              custom fonts, google fonts, local fonts, upload fonts, GDPR compliant
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Version:           1.2.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author URI:        https://themeruby.com/
 * Text Domain:       local-fonts-uploader
 * Domain Path:       /languages
 *
 * @package           local-fonts-uploader
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or any later version.
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

defined( 'ABSPATH' ) || exit;

define( 'LOCAL_FONTS_UPLOADER_VERSION', '1.2.0' );
define( 'LOCAL_FONTS_UPLOADER_PATH', plugin_dir_path( __FILE__ ) );
define( 'LOCAL_FONTS_UPLOADER_URL', plugin_dir_url( __FILE__ ) );

require_once LOCAL_FONTS_UPLOADER_PATH . 'includes/migration.php';

if ( ! class_exists( 'Local_Fonts_Uploader', false ) ) {
	class Local_Fonts_Uploader {

		private static $instance;

		/**
		 * Disable object cloning.
		 *
		 * @return void
		 */
		public function __clone() {
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @return void
		 */
		public function __wakeup() {
		}

		public static function get_instance() {

			if ( self::$instance === null ) {
				return new self();
			}

			return self::$instance;
		}

		public function __construct() {

			self::$instance = $this;

			// activation hooks
			register_activation_hook( __FILE__, [ $this, 'activation' ] );
			register_deactivation_hook( __FILE__, [ $this, 'deactivation' ] );

			add_action( 'plugins_loaded', [ $this, 'load_files' ], 1 );
		}


		/**
		 * Handles plugin activation for both single and multisite setups.
		 *
		 * @param bool $network Whether this is a network-wide activation (for multisite).
		 *
		 * @return void
		 */
		public function activation( $network ) {

			if ( is_multisite() && $network ) {

				$sites = get_sites();
				foreach ( $sites as $site ) {

					// change to another site
					switch_to_blog( (int) $site->blog_id );

					// activation process
					local_fonts_uploader_make_db();
					restore_current_blog();
				}
			} else {
				local_fonts_uploader_make_db();
			}
		}

		/**
		 * Handles plugin deactivation by performing necessary cleanup tasks.
		 *
		 * This includes removing stored options, such as clearing the CSS cache.
		 *
		 * @return void
		 */
		public function deactivation() {

			// Remove stored CSS cache option
			delete_option( 'local_fonts_uploader_css' );
		}


		/**
		 * Loads the necessary plugin files based on the context (admin or frontend).
		 *
		 * @return void
		 */
		public function load_files() {

			if ( is_admin() ) {
				include_once LOCAL_FONTS_UPLOADER_PATH . 'admin/data.php';
				include_once LOCAL_FONTS_UPLOADER_PATH . 'admin/description-strings.php';
				include_once LOCAL_FONTS_UPLOADER_PATH . 'admin/backup.php';
				include_once LOCAL_FONTS_UPLOADER_PATH . 'admin/admin-menu.php';
				require_once LOCAL_FONTS_UPLOADER_PATH . 'admin/ajax-handler.php';
			} else {
				require_once LOCAL_FONTS_UPLOADER_PATH . 'includes/client.php';
			}

		}
	}
}

/** LOAD */
Local_Fonts_Uploader::get_instance();