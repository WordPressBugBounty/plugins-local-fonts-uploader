<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

/**
 * Creates database tables for storing local fonts and their variants.
 *
 * This function sets up two tables:
 * - `lfontsup_fonts`: Stores font names and their metadata.
 * - `lfontsup_variants`: Stores font variant information, including file URLs.
 *
 * It uses `dbDelta` to ensure the tables are created if they do not exist.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 */
if ( ! function_exists( 'local_fonts_uploader_make_db' ) ) {
	function local_fonts_uploader_make_db() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// create fonts table
		$sql_fonts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lfontsup_fonts (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL UNIQUE,
    			`amount` INT(11) NOT NULL default '0',
    			`font_data` varchar(255) default NULL,
                PRIMARY KEY (`id`)
            ) $charset_collate;";

		dbDelta( $sql_fonts );

		// Create variants font settings table
		$sql_variants = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lfontsup_variants (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
    			`variant` VARCHAR(20) NOT NULL,
                `font_name` VARCHAR(255) NOT NULL,
    			`file_url` varchar(255) NOT NULL default '',
                `file_id` int(11) NOT NULL default '0',
    			`assign_to` text NULL,
                PRIMARY KEY (`id`)
            ) $charset_collate;";

		dbDelta( $sql_variants );
	}
}