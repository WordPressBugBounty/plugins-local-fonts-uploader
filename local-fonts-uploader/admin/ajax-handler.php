<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Local_Fonts_Uploader_Ajax_Handler', false ) ) {
	class Local_Fonts_Uploader_Ajax_Handler {

		private static $instance;
		private static $nonce = 'local-fonts-uploader';

		public static function get_instance() {

			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Class constructor to initialize AJAX actions for font management.
		 *
		 * This constructor sets up the necessary AJAX actions to handle font-related
		 * operations such as retrieving, creating, deleting fonts and variants,
		 * assigning font variants to HTML elements, and managing backup and restore processes.
		 */
		public function __construct() {

			self::$instance = $this;

			// AJAX: Retrieve all uploaded fonts.
			add_action( 'wp_ajax_lfontsupGetAllFonts', [ $this, 'ajax_uploaded_fonts' ] );

			// AJAX: Create a new font entry.
			add_action( 'wp_ajax_lfontsupCreateFont', [ $this, 'ajax_create_font' ] );

			// AJAX: Delete an existing font.
			add_action( 'wp_ajax_lfontsupRemoveFont', [ $this, 'ajax_remove_font' ] );

			// AJAX: Retrieve all variants of a specific font.
			add_action( 'wp_ajax_lfontsupGetVariants', [ $this, 'ajax_get_variants' ] );

			// AJAX: Add a new font variant.
			add_action( 'wp_ajax_lfontsupAddVariant', [ $this, 'ajax_create_variant' ] );

			// AJAX: Remove a specific font variant.
			add_action( 'wp_ajax_lfontsupDeleteVariant', [ $this, 'ajax_remove_variant' ] );

			// AJAX: Assign a font variant to a specific HTML class.
			add_action( 'wp_ajax_lfontsupVariantAssign', [ $this, 'ajax_assign_variant' ] );

			// AJAX: Fetch a backup of all stored font data.
			add_action( 'wp_ajax_lfontsupFetchBackup', [ $this, 'ajax_get_backup' ] );

			// AJAX: Restore font data from a backup.
			add_action( 'wp_ajax_lfontsupRestoreData', [ $this, 'ajax_restore_data' ] );
		}


		/**
		 * Safely decodes JSON data from a POST request.
		 *
		 * This function retrieves and sanitizes a JSON-encoded string from the provided key in the $_POST array.
		 * It ensures proper sanitization, validation, and security checks before returning the decoded data.
		 *
		 * @param string $key The key in the $_POST array that contains the JSON string.
		 *
		 * @return array|WP_Error Decoded JSON data as an associative array, or WP_Error if an error occurs.
		 *
		 * @since 1.0.0
		 */
		private function get_sanitized_post_data( $key ) {

			// Verify nonce for security.
			if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), self::$nonce ) ) {
				return new WP_Error( 'invalid_nonce', esc_html__( 'Invalid nonce.', 'local-fonts-uploader' ) );
			}

			// Check user capabilities.
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error( 'unauthorized', esc_html__( 'You do not have permission to access this resource.', 'local-fonts-uploader' ) );
			}

			// Ensure data exists in POST request.
			if ( ! isset( $_POST[ $key ] ) ) {
				return new WP_Error( 'missing_data', esc_html__( 'No data received or data format is invalid.', 'local-fonts-uploader' ) );
			}

			// Sanitize and decode JSON data.
			$sanitized_json = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
			$decoded_data   = json_decode( $sanitized_json, true );

			// Handle JSON decoding errors.
			if ( ! is_array( $decoded_data ) ) {
				return new WP_Error( 'invalid_json', esc_html__( 'Invalid JSON format.', 'local-fonts-uploader' ) );
			}

			return $decoded_data;
		}

		/**
		 * Sanitizes a font name by allowing only alphabets, numbers, dashes (-), and underscores (_).
		 * All other characters, including spaces and special symbols, will be removed.
		 *
		 * @param string $name The input font name to be sanitized.
		 *
		 * @return string The sanitized font name containing only allowed characters.
		 */
		private function get_sanitize_font_name( $name ) {
			// Remove all characters except letters, numbers, spaces, dashes, and underscores
			return preg_replace( '/[^a-zA-Z0-9\-_]/', '', $name );
		}

		/**
		 * Checks if the given font variant is valid.
		 *
		 * This function verifies whether the provided font variant exists in the predefined list
		 * of acceptable font variants.
		 *
		 * @param string $variant The font variant to validate (e.g., "500italic").
		 *
		 * @return bool Returns true if the input is a valid font variant; otherwise, returns false.
		 */
		private function is_valid_variant( $variant ) {
			$valid_variants = [
				"100", "100italic", "200", "200italic", "300", "300italic",
				"400", "400italic", "500", "500italic", "600", "600italic",
				"700", "700italic", "800", "800italic", "900", "900italic",
			];

			return in_array( $variant, $valid_variants, true );
		}


		/**
		 * Retrieves the uploaded fonts from the database.
		 *
		 * This function handles an AJAX request to fetch the uploaded fonts and their variant counts.
		 * It verifies the nonce for security, checks the user's permission to access the data,
		 * and retrieves font information from the database. If there are any errors during the process,
		 * appropriate error messages are returned.
		 *
		 * @return void Outputs a JSON response containing the success or error message.
		 *               On success, returns an array of fonts with their variant counts.
		 *               On failure, returns an error message and terminates the script.
		 *
		 * @since 1.0.0
		 */
		public function ajax_uploaded_fonts() {

			if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), self::$nonce ) ) {
				wp_send_json_error( esc_html__( 'Invalid nonce.', 'local-fonts-uploader' ) );
				wp_die();
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to access this resource.', 'local-fonts-uploader' ) ] );
				wp_die();
			}

			// Retrieves the list of fonts along with the number of associated variants.
			$all_fonts = Local_Fonts_Uploader_Data::get_fonts();

			if ( empty( $all_fonts ) ) {
				wp_send_json_error( esc_html__( 'Failed to retrieve fonts from the database.', 'local-fonts-uploader' ) );
			} else {
				wp_send_json_success( $all_fonts );
			}

			wp_die();
		}

		/**
		 * Creates a new font in the database.
		 *
		 * This function handles an AJAX request to create a new font name. It verifies the nonce for security,
		 * checks the user's permission to access the data, and inserts the new font name into the database.
		 * If there are any errors during the process, appropriate error messages are returned.
		 *
		 * @return void Outputs a JSON response containing the success or error message.
		 *               On success, returns the list of fonts with their variant counts.
		 *               On failure, returns an error message and terminates the script.
		 *
		 * @since 1.0.0
		 */
		public function ajax_create_font() {

			$data = $this->get_sanitized_post_data( 'data' );

			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			$font_name = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';

			if ( empty( $font_name ) ) {
				wp_send_json_error( esc_html__( 'Font family cannot include specific charset or be empty.', 'local-fonts-uploader' ) );
				wp_die();
			}

			$font_name = $this->get_sanitize_font_name( $font_name );

			$result = Local_Fonts_Uploader_Data::insert_font( $font_name );

			if ( empty( $result ) ) {
				wp_send_json_error( esc_html__( 'Could not add the font to the database. Please try disabling and re-enabling the plugin to see if that fixes the problem.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Retrieves the list of fonts along with the number of associated variants.
			$all_fonts = Local_Fonts_Uploader_Data::get_fonts();

			if ( empty( $all_fonts ) ) {
				wp_send_json_error( esc_html__( 'Failed to reload fonts. Please try refreshing your browser.', 'local-fonts-uploader' ) );
			} else {
				wp_send_json_success( $all_fonts );
			}

			wp_die();

		}

		/**
		 * Handles the deletion of a font from the database.
		 *
		 * This function verifies the nonce for security, checks user permissions,
		 * validates the received data, and attempts to remove the specified font
		 * from the `lfontsup_fonts` table.
		 *
		 * @return void Outputs a JSON response indicating success or failure.
		 */
		public function ajax_remove_font() {

			$data = $this->get_sanitized_post_data( 'data' );

			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			$font_name = isset( $data['font_name'] ) ? sanitize_text_field( $data['font_name'] ) : '';

			if ( empty( $font_name ) ) {
				wp_send_json_error( esc_html__( 'Font name is empty.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Delete font, including its variant data and files
			Local_Fonts_Uploader_Data::delete_font( $font_name );

			// Retrieves the list of fonts along with the number of associated variants.
			wp_send_json_success( Local_Fonts_Uploader_Data::get_fonts() );

			wp_die();
		}


		/**
		 * Handles an AJAX request to retrieve font variants by font ID.
		 *
		 * This function processes an AJAX request, validates the input, and fetches font variants
		 * from the database. It returns the variants as a JSON response or an error message if
		 * the request is invalid or the font ID is not found.
		 *
		 * @return void Outputs a JSON response and terminates the script using wp_die().
		 *
		 * @uses Local_Fonts_Uploader_Data::get_variants() Retrieves font variants from the database.
		 * @uses wp_send_json_error() Sends a JSON error response if the request fails.
		 * @uses wp_send_json_success() Sends a JSON success response with font variant data.
		 *
		 */
		public function ajax_get_variants() {

			$data = $this->get_sanitized_post_data( 'data' );

			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			$font_name = isset( $data['font_name'] ) ? sanitize_text_field( $data['font_name'] ) : '';

			if ( empty( $font_name ) ) {
				wp_send_json_error( esc_html__( 'Font name not found.', 'local-fonts-uploader' ) );
				wp_die();
			}

			$all_variants = Local_Fonts_Uploader_Data::get_variants( $font_name );
			wp_send_json_success( $all_variants );

			wp_die();
		}

		/**
		 * Handles the AJAX request to create a new font variant.
		 *
		 * This function retrieves and sanitizes POST data, validates required fields,
		 * checks for duplicate variants, and inserts the new variant into the database.
		 *
		 * @return void Outputs a JSON response and terminates execution.
		 */
		public function ajax_create_variant() {

			// Retrieve and sanitize the submitted data
			$data = $this->get_sanitized_post_data( 'data' );

			// Check for errors in the data retrieval
			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			// Validate required fields
			if ( empty( $data['variant'] ) || empty( $data['font_name'] ) || empty( $data['file_url'] ) || empty( $data['file_id'] ) ) {
				wp_send_json_error( esc_html__( 'Invalid form data', 'local-fonts-uploader' ) );
			}

			// Validates the provided font variant before proceeding
			if ( ! $this->is_valid_variant( $data['variant'] ) ) {
				wp_send_json_error( esc_html__( 'This font variant is invalid.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Check if the variant already exists for the given font ID
			if ( Local_Fonts_Uploader_Data::variant_exists( $data['font_name'], $data['variant'] ) ) {
				wp_send_json_error( esc_html__( 'This font variant already exists.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Check if the font exists before proceeding with variant addition
			if ( ! Local_Fonts_Uploader_Data::font_exists( $data['font_name'] ) ) {
				wp_send_json_error( esc_html__( 'Font not found. Please check the Font Overview Panel.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Insert the new font variant
			$result = Local_Fonts_Uploader_Data::insert_variant( $data );

			if ( ! $result ) {
				wp_send_json_error( esc_html__( 'Could not add the font variant to the database.', 'local-fonts-uploader' ) );
				wp_die();
			}

			Local_Fonts_Uploader_Data::sync_variant_count( $data['font_name'] );

			$all_variants = Local_Fonts_Uploader_Data::get_variants( $data['font_name'] );

			if ( empty( $all_variants ) ) {
				wp_send_json_error( esc_html__( 'Failed to reload variants. Please try refreshing the page.', 'local-fonts-uploader' ) );
			} else {
				wp_send_json_success( $all_variants );
			}

			wp_die();

		}

		/**
		 * Handles AJAX request to remove a font variant.
		 *
		 * This function retrieves and validates the submitted data, calls the `delete_variant`
		 * method to remove the variant, and returns the updated list of variants if successful.
		 * If an error occurs, an appropriate JSON error response is sent.
		 *
		 * @return void Sends a JSON response (`wp_send_json_success` or `wp_send_json_error`).
		 */
		public function ajax_remove_variant() {

			// Retrieve and sanitize the submitted data
			$data = $this->get_sanitized_post_data( 'data' );

			// Check for errors in the data retrieval
			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			// Validate required fields
			if ( empty( $data['variant_id'] ) ) {
				wp_send_json_error( esc_html__( 'Invalid form data', 'local-fonts-uploader' ) );
			}

			$font_name = Local_Fonts_Uploader_Data::delete_variant( $data['variant_id'] );

			if ( empty( $font_name ) ) {
				wp_send_json_error( esc_html__( 'Could not remove the font variant from the database.', 'local-fonts-uploader' ) );
				wp_die();
			}

			Local_Fonts_Uploader_Data::sync_variant_count( $font_name );

			// Retrieves the list of variants of current font.
			wp_send_json_success( Local_Fonts_Uploader_Data::get_variants( $font_name ) );

			wp_die();

		}


		/**
		 * Handles the AJAX request to assign an HTML class to a font variant.
		 *
		 * This function retrieves and validates user input, then updates the database
		 * to assign the specified HTML class to the given font variant.
		 *
		 * @return void Outputs a JSON response and terminates execution.
		 */
		public function ajax_assign_variant() {

			// Retrieve and sanitize the submitted data
			$data = $this->get_sanitized_post_data( 'data' );

			// Check for errors in the data retrieval
			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			// Validate required fields
			if ( empty( $data['variant_id'] ) ) {
				wp_send_json_error( esc_html__( 'Invalid form data.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Assign the font variant
			$result = Local_Fonts_Uploader_Data::assign_variant( $data['variant_id'], rtrim( trim( $data['assign_to'] ), ',' ) );

			if ( $result !== false ) {
				wp_send_json_success( [
					'message' => esc_html__( 'Font variant assigned successfully.', 'local-fonts-uploader' ),
				] );
			} else {
				wp_send_json_error( esc_html__( 'Could not assign the font variant.', 'local-fonts-uploader' ) );
			}

			wp_die();
		}

		/**
		 * Handles the AJAX request to retrieve backup font data.
		 *
		 * This function checks for a valid nonce and user permissions before fetching
		 * font and variant data. It returns the data as a JSON response.
		 *
		 * @return void Outputs JSON response and terminates execution.
		 */
		public function ajax_get_backup() {
			// Verify nonce for security
			if ( ! isset( $_POST['_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), self::$nonce ) ) {
				wp_send_json_error( esc_html__( 'Invalid nonce.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Check if the user has the required permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'You do not have permission to access this resource.', 'local-fonts-uploader' ) ] );
				wp_die();
			}

			// Send JSON response with font and variant data
			wp_send_json_success( [
				'fonts'    => (array) Local_Fonts_Uploader_Data::get_fonts(),
				'variants' => (array) Local_Fonts_Uploader_Data::get_all_variants(),
			] );

			wp_die();
		}


		/**
		 * Handles the AJAX request to restore font data from a backup.
		 *
		 * This function retrieves and sanitizes the submitted backup data,
		 * validates required fields, and restores font data.
		 *
		 * @return void Sends a JSON response indicating success or failure.
		 */
		public function ajax_restore_data() {

			// Retrieve and sanitize the submitted data
			$data = $this->get_sanitized_post_data( 'data' );

			// Check for errors in the data retrieval
			if ( is_wp_error( $data ) ) {
				wp_send_json_error( $data->get_error_message() );
				wp_die();
			}

			// Validate required fields
			if ( empty( $data['fonts'] ) || empty( $data['variants'] ) ) {
				wp_send_json_error( esc_html__( 'Invalid backup data.', 'local-fonts-uploader' ) );
				wp_die();
			}

			// Restore the font data
			Local_Fonts_Uploader_Backup_Restore::restore( $data );

			// Send success response
			wp_send_json_success( esc_html__( 'Font data restored successfully.', 'local-fonts-uploader' ) );

			wp_die();
		}


	}
}

/** load */
Local_Fonts_Uploader_Ajax_Handler::get_instance();
