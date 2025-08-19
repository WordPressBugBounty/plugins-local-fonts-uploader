<?php
/** Don't load directly */
defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an array of localized strings for the Local Fonts Uploader plugin.
 *
 * This function defines and returns an array of translatable strings used throughout
 * the plugin, making it easier to manage and localize text elements.
 *
 * @return array An associative array of localized strings.
 */
if ( ! function_exists( 'local_fonts_uploader_strings' ) ) {
	function local_fonts_uploader_strings() {
		return [
			'appTitle'                    => esc_html__( 'Local Fonts Uploader', 'local-fonts-uploader' ),
			'appDescription'              => esc_html__( 'Easily upload premium fonts, host any font locally on your site. Avoid external requests to protect data, boost speed, and stay GDPR-compliant for better security, privacy, and performance.', 'local-fonts-uploader' ),
			'createNewFont'               => esc_html__( 'Start Creating Your Font', 'local-fonts-uploader' ),
			'createNewFontDesc'           => esc_html__( 'To get started, Enter a font family name and then upload your font file variants.', 'local-fonts-uploader' ),
			'addNewFont'                  => esc_html__( 'Add New Font', 'local-fonts-uploader' ),
			'createFontPlaceHolder'       => esc_html__( 'Enter a font family name without special characters...', 'local-fonts-uploader' ),
			'saveNewFont'                 => esc_html__( 'Save New Font', 'local-fonts-uploader' ),
			'fontsListing'                => esc_html__( 'Your Fonts', 'local-fonts-uploader' ),
			'variantsListing'             => esc_html__( 'Variants ', 'local-fonts-uploader' ),
			'fontsOverview'               => esc_html__( 'Fonts Overview', 'local-fonts-uploader' ),
			'variants'                    => esc_html__( 'Font Variants', 'local-fonts-uploader' ),
			'fontAssign'                  => esc_html__( 'Assigned to HTML', 'local-fonts-uploader' ),
			'duplicateNameErrorTitle'     => esc_html__( 'Duplicate Font Name', 'local-fonts-uploader' ),
			'duplicateNameErrorDesc'      => esc_html__( 'The font family already exists. Please choose a different name.', 'local-fonts-uploader' ),
			'noVariantInfo'               => esc_html__( 'No variants uploaded yet.', 'local-fonts-uploader' ),
			'cancel'                      => esc_html__( 'Cancel', 'local-fonts-uploader' ),
			/* translators: %s is the message of the font that was added */
			'addFontSuccessMessage'       => esc_html__( 'Font %s was added successfully.', 'local-fonts-uploader' ),
			/* translators: %s is the message of the variant that was added */
			'addVariantSuccessMessage'    => esc_html__( 'Font variant %s was added successfully.', 'local-fonts-uploader' ),
			'deleteFontSuccessTitle'      => esc_html__( 'Font Deleted Successfully.', 'local-fonts-uploader' ),
			/* translators: %s represents the name of the deleted font */
			'deleteFontSuccessMessage'    => esc_html__( 'Font %s was  deleted successfully.', 'local-fonts-uploader' ),
			/* translators: %s represents the name of the deleted variant */
			'deleteVariantSuccessMessage' => esc_html__( 'The variant %s has been deleted successfully.', 'local-fonts-uploader' ),
			/* translators: %s represents the font name */
			'uploadVariantHeading'        => esc_html__( 'Upload font variants for %s', 'local-fonts-uploader' ),
			'errorTitle'                  => esc_html__( 'Error Occurred', 'local-fonts-uploader' ),
			'errorDesc'                   => esc_html__( 'An unexpected error occurred. Please try again.', 'local-fonts-uploader' ),
			'delete'                      => esc_html__( 'Delete', 'local-fonts-uploader' ),
			'confirmDeleteTitle'          => esc_html__( 'Delete Confirmation', 'local-fonts-uploader' ),
			'confirmDeleteDescription'    => esc_html__( 'Are you sure you want to delete this item?', 'local-fonts-uploader' ),
			'editVariants'                => esc_html__( 'Variants', 'local-fonts-uploader' ),
			'notFoundFontTitle'           => esc_html__( 'Selected Font Not Found', 'local-fonts-uploader' ),
			'notFoundFontDesc'            => esc_html__( 'Please switch to the overview tab and select a font to set up variants.', 'local-fonts-uploader' ),
			'goToOverview'                => esc_html__( 'Go to Overview', 'local-fonts-uploader' ),
			'uploadVariantDesc'           => esc_html__( 'Select your own variant files to use.', 'local-fonts-uploader' ),
			'variantPlaceHolder'          => esc_html__( 'Choose a variant...', 'local-fonts-uploader' ),
			'replaceFile'                 => esc_html__( 'Replace File', 'local-fonts-uploader' ),
			'uploadFile'                  => esc_html__( 'Upload File', 'local-fonts-uploader' ),
			'fontTypeAlertTitle'          => esc_html__( 'Invalid File Type.', 'local-fonts-uploader' ),
			'fontTypeAlertDesc'           => esc_html__( 'Only .ttf, .otf, .woff, .woff2, .eot files are allowed.', 'local-fonts-uploader' ),
			'fontFileSupported'           => esc_html__( 'For maximum compatibility, please upload font files in the following formats: TTF, OTF, WOFF, and WOFF2.', 'local-fonts-uploader' ),
			'uploadFontButton'            => esc_html__( 'Select or Upload Font', 'local-fonts-uploader' ),
			'useThisFile'                 => esc_html__( 'Use This File', 'local-fonts-uploader' ),
			'missingFontTitle'            => esc_html__( 'Font ID Missing', 'local-fonts-uploader' ),
			'missingFontDesc'             => esc_html__( 'An issue occurred in the panel. Please switch to the overview tab and select a font before uploading variants.', 'local-fonts-uploader' ),
			'missingVariantTitle'         => esc_html__( 'Variant Missing', 'local-fonts-uploader' ),
			'missingVariantDesc'          => esc_html__( 'Please set a variant for this file before submitting.', 'local-fonts-uploader' ),
			'missingFileTitle'            => esc_html__( 'Font File Missing', 'local-fonts-uploader' ),
			'missingFileDesc'             => esc_html__( 'Please upload a font file before submitting.', 'local-fonts-uploader' ),
			'selectVariant'               => esc_html__( 'Select a Variant', 'local-fonts-uploader' ),
			'saveChanges'                 => esc_html__( 'Save Changes', 'local-fonts-uploader' ),
			'allVariantsAvailable'        => esc_html__( 'Congratulations. Variants Uploaded Successfully', 'local-fonts-uploader' ),
			'allVariantsAvailableDecs'    => esc_html__( 'CongYour font variants have been uploaded successfully. They are now ready for use.', 'local-fonts-uploader' ),
			'ok'                          => esc_html__( 'OK', 'local-fonts-uploader' ),
			'backupRestore'               => esc_html__( 'Backup and Restore', 'local-fonts-uploader' ),
			'cssSelectors'                => esc_html__( 'CSS Selectors', 'local-fonts-uploader' ),
			'assignSelectorsHeadline'     => esc_html__( 'Assign Variant to CSS Selectors', 'local-fonts-uploader' ),
			'assignedPlaceHolder'         => esc_html__( 'No CSS selectors yet...', 'local-fonts-uploader' ),
			'assignedEmptyMessage'        => esc_html__( 'Please add classnames before submitting.', 'local-fonts-uploader' ),
			'assignVariantSuccessMessage' => esc_html__( 'Variant assigned successfully.', 'local-fonts-uploader' ),
			'clearVariantSuccessMessage'  => esc_html__( 'Variant cleared successfully.', 'local-fonts-uploader' ),
			'assignVariantHelp'           => esc_html__( 'Enter CSS selectors to assign this variant to, separated by commas (e.g., .class-1, .class-2).', 'local-fonts-uploader' ),
			'exportSettings'              => esc_html__( 'Export Settings', 'local-fonts-uploader' ),
			'importSettings'              => esc_html__( 'Import Settings', 'local-fonts-uploader' ),
			'copy'                        => esc_html__( 'Copy', 'local-fonts-uploader' ),
			'import'                      => esc_html__( 'Import Data', 'local-fonts-uploader' ),
			'paste'                       => esc_html__( 'Paste From Clipboard', 'local-fonts-uploader' ),
			'copySuccessMessage'          => esc_html__( 'Copied to clipboard successfully.', 'local-fonts-uploader' ),
			'copyErrorMessage'            => esc_html__( 'Failed to copy. Please try again', 'local-fonts-uploader' ),
			'restorePlaceHolder'          => esc_html__( 'Paste your backup data here...', 'local-fonts-uploader' ),
			'fetchBackupPlaceHolder'      => esc_html__( 'Fetching backup data...', 'local-fonts-uploader' ),
			'copyEmptyMessage'            => esc_html__( 'No data to copy.', 'local-fonts-uploader' ),
			'pasteSuccessMessage'         => esc_html__( 'Pasted successfully.', 'local-fonts-uploader' ),
			'pasteEmptyMessage'           => esc_html__( 'No data to paste.', 'local-fonts-uploader' ),
			'emptyRestoreData'            => esc_html__( 'Empty restore content.', 'local-fonts-uploader' ),
			'invalidRestoreData'          => esc_html__( 'Invalid restore data format.', 'local-fonts-uploader' ),
			'restoreSuccessMessage'       => esc_html__( 'Data restored successfully.', 'local-fonts-uploader' ),
			'ajaxRequestFailed'           => esc_html__( 'AJAX request failed', 'local-fonts-uploader' ),
			'restoreSuccessTitle'         => esc_html__( 'Restore Complete', 'local-fonts-uploader' ),
			'restoreSuccessDescription'   => esc_html__( 'The data has been restored successfully.', 'local-fonts-uploader' ),
			'helps'                       => esc_html__( 'Helps', 'local-fonts-uploader' ),
			'tips'                        => esc_html__( 'Useful Tips', 'local-fonts-uploader' ),
			'assignTips'                  => esc_html__( 'If you are using our Themes, simply go to Typography panels, then assign your uploaded font to any elements you wish.', 'local-fonts-uploader' ),
			'documentation'               => esc_html__( 'Documentation', 'local-fonts-uploader' ),
			'fullDocumentation'           => esc_html__( 'Full Documentation', 'local-fonts-uploader' ),
			'premiumTitle'                => esc_html__( 'Premium News/Magazine Themes', 'local-fonts-uploader' ),
			'foxizThemeTitle'             => esc_html__( 'Foxiz', 'local-fonts-uploader' ),
			'foxizTagline'                => esc_html__( 'Newspaper News and Magazine WordPress Theme', 'local-fonts-uploader' ),
			'pixwellThemeTitle'           => esc_html__( 'Pixwell', 'local-fonts-uploader' ),
			'pixwellTagline'              => esc_html__( 'Magazine WordPress Theme', 'local-fonts-uploader' ),
			'learnMore'                   => esc_html__( 'Learn More', 'local-fonts-uploader' ),
		];
	}
}