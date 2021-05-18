<?php
/**
 * SharpSpring to Forminator Add On Uninstall methods
 * Called when plugin is deleted
 *
 * @since 1.0.2
 */

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}


/**
 * Delete custom options and addon options
 *
 * @since 1.0.2
 * @since 1.0.6 Delete privacy options
 * @since 1.14.10 Deletes all forminator options including the addons' options
 * @since 1.14.10 Added $db_prefix parameter
 *
 * @param string $db_prefix - database prefix
 */
function forminator_delete_sharpspring_options( $db_prefix = 'wp_' ) {
	global $wpdb;

	$forminator_options = $wpdb->get_results( "SELECT option_name FROM {$db_prefix}options WHERE option_name LIKE 'forminator_addon_sharpspring%'" );

	foreach( $forminator_options as $option ) {
		delete_option( $option->option_name );
	}
}

global $wpdb;
if ( ! is_multisite() ) {
	$db_prefix = $wpdb->prefix;

	forminator_delete_sharpspring_options( $db_prefix );

} else {
	$sites = get_sites();

	foreach ( $sites as $site ) {
		$blog_id = $site->blog_id;
		$db_prefix = $wpdb->get_blog_prefix( $blog_id );

		// Switch to blog before deleting options
		forminator_delete_sharpspring_options( $blog_id );
		restore_current_blog();
	}

}
