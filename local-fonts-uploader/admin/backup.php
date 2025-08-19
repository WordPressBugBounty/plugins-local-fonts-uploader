<?php
// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Local Fonts Uploader - Backup & Restore
 *
 * This class handles the backup and restore functionality for local fonts.
 */
if ( ! class_exists( 'Local_Fonts_Uploader_Backup_Restore', false ) ) {

	/**
	 * Class Local_Fonts_Uploader_Backup_Restore
	 *
	 * Implements a singleton pattern to manage backup and restore operations.
	 */
	class Local_Fonts_Uploader_Backup_Restore {

		/**
		 * Holds the single instance of the class.
		 *
		 * @var Local_Fonts_Uploader_Backup_Restore|null
		 */
		private static $instance = null;

		/**
		 * Retrieves the singleton instance of this class.
		 *
		 * @return Local_Fonts_Uploader_Backup_Restore The class instance.
		 */
		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Class constructor.
		 *
		 * Initializes the singleton instance.
		 */
		public function __construct() {
			self::$instance = $this;
		}

		/**
		 * Restores font data from backup.
		 *
		 * This function checks if the current user has the required permissions
		 * and processes the provided backup data. If the user lacks permissions,
		 * it returns an error.
		 *
		 * @param array $data The backup data to be restored.
		 *
		 * @return true|false|WP_Error Returns true on success, or WP_Error on failure.
		 */
		static function restore( $data ) {

			// Check if the user has the required permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error( 'unauthorized', esc_html__( 'You do not have permission to access this resource.', 'local-fonts-uploader' ) );
			}

			// TODO: Implement restore logic here
			if ( empty( $data['fonts'] ) || ! is_array( $data['fonts'] ) ) {
				return false;
			}

			$all_variants = ! empty( $data['variants'] ) ? $data['variants'] : [];

			foreach ( $data['fonts'] as $font ) {
				if ( empty( $font['name'] ) ) {
					continue;
				}
				$name      = $font['name'];
				$amount    = isset( $font['amount'] ) ? $font['amount'] : 0;
				$font_data = isset( $font['font_data'] ) ? $font['font_data'] : null;

				// Skip if the font already exists
				if ( Local_Fonts_Uploader_Data::font_exists( $name ) ) {
					continue;
				}

				$result = Local_Fonts_Uploader_Data::insert_font( $name, $amount, $font_data );
				if ( $result ) {
					self::restore_variants( $name, $all_variants );
				}
			}

			return true;
		}

		/**
		 * Restores font variants by downloading and storing font files in the WordPress media library.
		 *
		 * This function iterates through a list of font variants associated with a given font name.
		 * If a variant's font file is already hosted on the current site, it skips downloading it.
		 * Otherwise, it downloads the file, validates it as a font, stores it in the WordPress media library,
		 * and retrieves its new URL and file ID. The restored variant data is then saved to the database.
		 *
		 * @param string $font_name The name of the font to restore.
		 * @param array $all_variants An array of font variant data containing file URLs, variants, and other metadata.
		 *
		 * @return void
		 */
		static function restore_variants( $font_name, $all_variants = [] ) {

			if ( ! function_exists( 'media_handle_sideload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			$site_url                = get_site_url(); // Get the base URL of the current site
			$allowed_font_extensions = [ 'ttf', 'otf', 'woff', 'woff2', 'otf' ];
			$allowed_mime_types      = [
				'font/ttf',                // TrueType Font (TTF)
				'font/otf',                // OpenType Font (OTF)
				'font/woff',               // Web Open Font Format (WOFF)
				'font/woff2',              // Web Open Font Format 2 (WOFF2)
				'font/sfnt',               // SFNT-based fonts (generic type for TTF and OTF)
				'application/font-woff',    // Alternative MIME type for WOFF
				'application/font-woff2',   // Alternative MIME type for WOFF2
				'application/x-font-ttf',   // Older MIME type for TTF
				'application/x-font-otf',   // Older MIME type for OTF
				'application/x-font-woff',  // Older MIME type for WOFF
				'application/x-font-woff2', // Older MIME type for WOFF2
				'application/vnd.ms-fontobject', // Embedded OpenType (EOT) fonts (used by older IE versions)
				'application/font-sfnt'     // Alternative generic type for SFNT-based fonts
			];
			foreach ( $all_variants as $variant ) {

				// Ensure the variant belongs to the specified font name
				if ( $variant['font_name'] !== $font_name || empty( $variant['variant'] ) || empty( $variant['file_url'] ) ) {
					continue;
				}

				$file_url = esc_url_raw( $variant['file_url'] );

				// Check if the file is already stored locally
				if ( strpos( $file_url, $site_url ) === 0 ) {

					// File is already uploaded to this site, reuse existing file details
					$updated_variant = [
						'variant'   => $variant['variant'],
						'font_name' => $font_name,
						'file_url'  => $file_url,
						'file_id'   => isset( $variant['file_id'] ) ? intval( $variant['file_id'] ) : 0,
						'assign_to' => isset( $variant['assign_to'] ) ? $variant['assign_to'] : '',
					];

				} else {

					// Validate file extension
					$file_extension = pathinfo( wp_parse_url( $file_url, PHP_URL_PATH ), PATHINFO_EXTENSION );
					if ( ! in_array( strtolower( $file_extension ), $allowed_font_extensions, true ) ) {
						continue; // Skip invalid file types
					}

					// Download the font file from the external URL
					$tmp_file = download_url( $file_url );

					if ( is_wp_error( $tmp_file ) ) {
						continue; // Skip if download fails
					}

					// Check MIME type to ensure it's a font file
					$file_mime_type = mime_content_type( $tmp_file );

					if ( ! in_array( $file_mime_type, $allowed_mime_types, true ) ) {
						wp_delete_file( $tmp_file ); // Delete invalid file
						continue;
					}

					// Prepare the file array for uploading to WordPress media library
					$file = [
						'name'     => basename( $file_url ),
						'tmp_name' => $tmp_file,
					];

					$file_id = media_handle_sideload( $file, 0 );

					// If the upload failed, remove the temporary file and skip processing
					if ( is_wp_error( $file_id ) ) {
						wp_delete_file( $tmp_file );
						continue;
					}

					// Retrieve the new file URL from the media library
					$new_file_url = wp_get_attachment_url( $file_id );

					// Prepare updated variant data
					$updated_variant = [
						'variant'   => $variant['variant'],
						'font_name' => $font_name,
						'file_url'  => $new_file_url,
						'file_id'   => $file_id,
						'assign_to' => isset( $variant['assign_to'] ) ? $variant['assign_to'] : '',
					];
				}

				// Save the updated variant information to the database
				Local_Fonts_Uploader_Data::insert_variant( $updated_variant );
			}
		}
	}
}
