<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Local_Fonts_Uploader_Data' ) ) {
	class Local_Fonts_Uploader_Data {

		/**
		 * Inserts a new font into the database.
		 *
		 * This function inserts a font name into the `lfontsup_fonts` table while ensuring
		 * it is a sanitized text string. It prevents SQL injection and ignores duplicate entries.
		 *
		 * @param string $name The name of the font to insert.
		 * @param int|null $amount The number of font variations (optional).
		 * @param string $font_data Additional font metadata (optional).
		 *
		 * @return int|false The number of rows affected or false on failure.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		static function insert_font( $name, $amount = 0, $font_data = null ) {

			global $wpdb;

			// Ensure font name is sanitized and valid
			$name = trim( sanitize_text_field( $name ) );

			// Sanitize font_data if needed
			$font_data = is_string( $font_data ) ? sanitize_textarea_field( $font_data ) : null;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$inserted = $wpdb->insert(
				$wpdb->prefix . 'lfontsup_fonts',
				[
					'name'      => $name,
					'amount'    => absint( $amount ),
					'font_data' => $font_data,
				],
				[ '%s', '%d', '%s' ]
			);

			return $inserted ? $wpdb->insert_id : false;
		}

		/**
		 * Deletes a font and its associated data from the database.
		 *
		 * @param string $font_name The name of the font to delete.
		 *
		 * @return bool True on success, false if the font name is empty.
		 */
		static function delete_font( $font_name ) {

			global $wpdb;

			// Ensure font name is sanitized and valid
			$font_name = sanitize_text_field( $font_name );

			$wpdb->delete( $wpdb->prefix . 'lfontsup_fonts', [ 'name' => $font_name ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Get all file IDs related to the font from lfontsup_variants
			$file_ids = $wpdb->get_col( $wpdb->prepare(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT file_id FROM {$wpdb->prefix}lfontsup_variants WHERE font_name = %s",
				$font_name
			) );

			// Delete attachments if there are any file IDs
			if ( ! empty( $file_ids ) ) {
				foreach ( $file_ids as $file_id ) {
					wp_delete_attachment( $file_id, true );
				}
			}

			$wpdb->delete( $wpdb->prefix . 'lfontsup_variants', [ 'font_name' => $font_name ] );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// clear css caching
			self::clear_caching();

			return true;
		}

		/**
		 * Retrieve all fonts along with their variants from the database.
		 *
		 * This function queries the `lfontsup_fonts` table and returns all stored fonts
		 * with their associated variant data in an associative array format.
		 *
		 * @return array List of fonts with variants, each as an associative array.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 *
		 */
		static function get_fonts() {

			global $wpdb;

			return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lfontsup_fonts", ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Retrieves all font variants from the 'lfontsup_variants' table.
		 *
		 * @return array List of all font variants as associative arrays.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public static function get_all_variants() {
			global $wpdb;

			// Fetch all variants from the database table
			return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lfontsup_variants", ARRAY_A );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Retrieves all variants of a given font from the database.
		 *
		 * @param string $font_name The name of the font whose variants are to be fetched.
		 *
		 * @return array List of font variants as associative arrays.
		 */
		public static function get_variants( $font_name ) {

			if ( empty( $font_name ) ) {
				return [];
			}

			// Ensure font name is sanitized and valid
			$font_name = sanitize_text_field( $font_name );

			global $wpdb;

			// Fetch all variants for the given font_name, ordered by length and alphabetically
			return $wpdb->get_results(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}lfontsup_variants 
			         WHERE font_name = %s 
			         ORDER BY LENGTH(variant), variant ASC",
					$font_name
				),
				ARRAY_A // Return results as an associative array
			);
		}

		/**
		 * Checks if a font with the given name already exists in the database.
		 *
		 * This function sanitizes the font name before querying the database
		 * to prevent SQL injection and ensure data integrity. It then checks
		 * if the font is already stored in the `lfontsup_fonts` table.
		 *
		 * @param string $font_name The name of the font to check.
		 *
		 * @return bool True if the font exists, false otherwise.
		 */
		static function font_exists( $font_name ) {
			global $wpdb;

			// Sanitize the font name to ensure valid input
			$font_name = sanitize_text_field( $font_name );

			// Query the database to check if the font already exists
			$exists = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lfontsup_fonts WHERE name = %s",
					$font_name
				)
			);

			// Return true if at least one record exists, otherwise false
			return ! empty( $exists );
		}


		/**
		 * Checks if a specific font variant exists in the database.
		 *
		 * @param string $font_name The name of the font.
		 * @param string $variant The variant of the font to check.
		 *
		 * @return bool True if the variant exists, false otherwise.
		 */
		static function variant_exists( $font_name, $variant ) {
			global $wpdb;

			// Ensure font name and variant is sanitized and valid
			$font_name = sanitize_text_field( $font_name );
			$variant   = sanitize_text_field( $variant );

			if ( empty( $font_name ) || empty( $variant ) ) {
				return true;
			}

			$exists = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}lfontsup_variants WHERE font_name = %s AND variant = %s",
					$font_name,
					$variant
				)
			);

			return ! empty( $exists );
		}


		/**
		 * Inserts a new font variant into the database.
		 *
		 * @param array $data {
		 *     Associative array containing variant details.
		 *
		 * @type string $variant The font variant name.
		 * @type string $font_name The name of the font.
		 * @type string $file_url The URL of the associated file.
		 * @type int $file_id The ID of the associated file.
		 * @type string $assign_to (Optional) The entity the variant is assigned to.
		 * }
		 * @return int|false The inserted row ID on success, false on failure.
		 */
		static function insert_variant( $data ) {
			global $wpdb;

			// Insert the new variant
			$inserted = $wpdb->insert(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prefix . 'lfontsup_variants',
				[
					'variant'   => sanitize_text_field( $data['variant'] ),
					'font_name' => sanitize_text_field( $data['font_name'] ),
					'file_url'  => esc_url_raw( $data['file_url'] ),
					'file_id'   => intval( $data['file_id'] ),
					'assign_to' => ! empty( $data['assign_to'] ) ? sanitize_text_field( $data['assign_to'] ) : '',
				],
				[ '%s', '%s', '%s', '%d', '%s' ]
			);

			return $inserted ? $wpdb->insert_id : false;
		}


		/**
		 * Deletes a font variant from the database.
		 *
		 * This function removes a font variant by its ID, deletes its associated attachment (if any),
		 * and returns the font name if the deletion is successful.
		 *
		 * @param int $variant_id The ID of the font variant to be deleted.
		 *
		 * @return string|false The name of the font if the variant is deleted successfully, or false if the variant is not found.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		static function delete_variant( $variant_id ) {

			$variant_id = intval( $variant_id );

			global $wpdb;

			// Get the file_id associated with this variant
			$variant = $wpdb->get_row( $wpdb->prepare(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT font_name, file_id FROM {$wpdb->prefix}lfontsup_variants WHERE id = %d",
				$variant_id
			), ARRAY_A );

			if ( ! $variant ) {
				return false; // Variant not found
			}

			$font_name = $variant['font_name'];
			$file_id   = $variant['file_id'];

			// Delete the attachment if a file_id exists
			if ( ! empty( $file_id ) ) {
				wp_delete_attachment( $file_id, true );
			}
			$result = $wpdb->delete( $wpdb->prefix . 'lfontsup_variants', [ 'id' => $variant_id ] );  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// clear css caching
			self::clear_caching();

			return $result ? $font_name : false;
		}

		/**
		 * Synchronizes the variant count for a given font.
		 *
		 * This function counts the number of variants associated with the specified font name
		 * in the `lfontsup_variants` table and updates the `amount` field in the `lfontsup_fonts` table.
		 *
		 * @param string $font_name The name of the font whose variant count needs to be updated.
		 *
		 * @return int|false Returns the total variant count if updated successfully, or false if the font name is invalid.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		static function sync_variant_count( $font_name ) {

			if ( empty( $font_name ) ) {
				return false;
			}

			// Ensure font name is sanitized and valid
			$font_name = sanitize_text_field( $font_name );

			global $wpdb;

			// Get the total number of rows with the given font_name
			$total = (int) $wpdb->get_var( $wpdb->prepare(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM {$wpdb->prefix}lfontsup_variants WHERE font_name = %s",
				$font_name
			) );

			// If there are variants associated with the font, update the count in lfontsup_fonts
			if ( ! empty( $total ) ) {
				$wpdb->update(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prefix . 'lfontsup_fonts',
					[ 'amount' => $total ],
					[ 'name' => $font_name ],
					[ '%d' ],
					[ '%s' ]
				);
			}

			return $total;
		}

		/**
		 * Assigns an HTML class to a font variant by updating the `assign_to` field in the database.
		 *
		 * This function checks if the variant ID exists, retrieves the current `assign_to` value,
		 * appends the new class name if it's not already present, and updates the database.
		 *
		 * @param int $variant_id The ID of the font variant to update.
		 * @param string $assign_to The HTML class name to assign to the variant.
		 *
		 * @return bool  Returns true if the update was successful, false otherwise.
		 */
		static function assign_variant( $variant_id, $assign_to ) {
			global $wpdb;

			// Ensure that $assign_to is sanitized and $variant_id is a valid integer.
			$variant_id = intval( $variant_id );
			$assign_to  = sanitize_text_field( $assign_to );

			// Check if variant ID exists
			$variant_exists = $wpdb->get_var( $wpdb->prepare(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT COUNT(*) FROM {$wpdb->prefix}lfontsup_variants WHERE id = %d",
				$variant_id
			) );

			if ( ! $variant_exists ) {
				return false; // Exit if the variant ID does not exist
			}

			// Update assign_to field
			$updated = $wpdb->update(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prefix . 'lfontsup_variants',
				[ 'assign_to' => $assign_to ],
				[ 'id' => $variant_id ],
				[ '%s' ],
				[ '%d' ]
			);

			// clear css caching
			self::clear_caching();

			return $updated !== false;
		}

		/**
		 * Clears the cached Easy Font Uploader CSS option.
		 *
		 * This function deletes the `local_fonts_uploader_css` option from the database
		 * to force a refresh of the stored font styles.
		 */
		static function clear_caching() {
			delete_option( 'local_fonts_uploader_css' );
		}
	}
}