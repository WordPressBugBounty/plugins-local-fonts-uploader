<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

/**
 * Handles the client-side functionality for the Local Fonts Uploader plugin.
 *
 * This class is responsible for managing font uploads, interactions with the database,
 * and any other client-side operations required by the plugin.
 */
if ( ! class_exists( 'Local_Fonts_Uploader_Client', false ) ) {
	class Local_Fonts_Uploader_Client {

		private static $instance;
		private $cache_key = 'local_fonts_uploader_css'; // Cache key for storing generated CSS

		static $font_formats = [
			'woff2' => 'woff2',
			'woff'  => 'woff',
			'ttf'   => 'truetype',
			'otf'   => 'opentype',
			'eot'   => 'embedded-opentype',
		];

		/**
		 * Singleton instance.
		 *
		 * @return Local_Fonts_Uploader_Client
		 */
		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Class constructor for initializing hooks.
		 */
		public function __construct() {
			self::$instance = $this;

			// Hook to enqueue styles on the frontend
			add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_fonts' ], 50 );
		}


		/**
		 * Retrieves all font variants with assigned CSS selectors.
		 *
		 * @return array List of font variants.
		 * @global wpdb $wpdb WordPress database abstraction object.
		 */
		public function get_variants() {

			global $wpdb;

			// Retrieve variants that have an 'assign_to' value
			return $wpdb->get_results(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT font_name, variant, file_url, assign_to FROM {$wpdb->prefix}lfontsup_variants WHERE assign_to IS NOT NULL AND assign_to != ''",
				ARRAY_A
			);
		}

		/**
		 * Generates and returns CSS for a specific font variant.
		 *
		 * @param array $variant Font variant data.
		 *
		 * @return string Generated CSS rules.
		 */
		public function render_font_css( $variant ) {

			if ( empty( $variant['font_name'] ) || empty( $variant['variant'] ) || empty( $variant['file_url'] ) || empty( $variant['assign_to'] ) ) {
				return ''; // Return empty if required data is missing
			}

			$font_name     = esc_attr( $variant['font_name'] );
			$variant_value = esc_attr( $variant['variant'] );
			$file_url      = esc_url( $variant['file_url'] );
			$file_ext      = pathinfo( $file_url, PATHINFO_EXTENSION );
			$format        = isset( self::$font_formats[ $file_ext ] ) ? self::$font_formats[ $file_ext ] : 'woff2';

			// Extract font-weight and font-style from variant value
			$font_weight = preg_replace( '/[^0-9]/', '', $variant_value );
			$font_style  = ( strpos( $variant_value, 'italic' ) !== false ) ? 'italic' : 'normal';
			$assign_to   = esc_attr( $variant['assign_to'] );

			// Generate @font-face rule
			$output_font_face = "@font-face {\n";
			$output_font_face .= "    font-family: '{$font_name}';\n";
			$output_font_face .= "    font-weight: {$font_weight};\n";
			$output_font_face .= "    font-style: {$font_style};\n";
			$output_font_face .= "    src: url('{$file_url}') format('{$format}');\n";
			$output_font_face .= "    font-display: swap;\n";
			$output_font_face .= "}\n";

			// Apply filters for customization
			$output = apply_filters( 'local_fonts_uploader_font_face_css', $output_font_face, $font_name, $font_weight, $font_style, $file_url );

			// Generate CSS rules to apply the font to the assigned selectors
			$output_assign_to = "{$assign_to} {\n";
			$output_assign_to .= "    font-family: '{$font_name}', sans-serif;\n";
			$output_assign_to .= "    font-weight: {$font_weight};\n";
			$output_assign_to .= "    font-style: {$font_style};\n";
			$output_assign_to .= "}\n";

			// Apply filters for additional customization
			$output .= apply_filters( 'local_fonts_uploader_selector_css', $output_assign_to, $font_name, $font_weight, $font_style, $assign_to );

			return $output;
		}


		/**
		 * Generates and returns CSS for all font variants.
		 *
		 * Uses caching to optimize performance.
		 *
		 * @return string Generated CSS for all fonts.
		 */
		public function render_all_fonts_css() {

			$cached_css = get_option( $this->cache_key );

			if ( $cached_css !== false ) {
				return stripslashes( $cached_css );
			}

			$variants = $this->get_variants();

			if ( empty( $variants ) ) {
				return ''; // Return empty if no fonts are available
			}

			$css = '';
			foreach ( $variants as $variant ) {
				$css .= $this->render_font_css( $variant );
			}

			// Cache the generated CSS
			update_option( $this->cache_key, addslashes( $css ) );

			return $css;
		}

		/**
		 * Enqueues the generated font CSS to be output on the frontend.
		 *
		 * This function retrieves the CSS for all uploaded fonts, applies filters,
		 * and enqueues it as an inline style. If no CSS is generated, it exits early.
		 *
		 * @return void
		 */
		public function enqueue_fonts() {

			// Apply filter to allow modification of the generated font CSS.
			$css = apply_filters( 'local_fonts_uploader_css', $this->render_all_fonts_css() );

			// Exit if no CSS is generated.
			if ( empty( $css ) ) {
				return;
			}

			// Register an empty stylesheet handle for local fonts uploader.
			wp_register_style( 'local-fonts-uploader', false );

			// Add inline styles containing the generated font CSS.
			wp_add_inline_style( 'local-fonts-uploader', html_entity_decode( $css ) );

			// Enqueue the registered stylesheet.
			wp_enqueue_style( 'local-fonts-uploader' );
		}
	}
}

/** Load the class */
Local_Fonts_Uploader_Client::get_instance();
