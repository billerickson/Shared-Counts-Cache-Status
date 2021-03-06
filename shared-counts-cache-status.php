<?php
/**
 * Plugin Name: Shared Counts - Cache Status
 * Plugin URI:  https://github.com/billerickson/Shared-Counts-Cache-Status
 * Description: Build and check the status of the Shared Counts cache
 * Author:      Bill Erickson
 * Version:     1.0.0
 *
 * Shared Counts - Cache Status is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Shared Counts - Cache Status is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Shared Counts. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    SharedCountsCacheStatus
 * @author     Bill Erickson
 * @since      1.0.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2018
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize the Settings page options.
 *
 * @since 1.0.0
 */
function sccs_settings_init() {

	register_setting( 'shared_counts_cache_status', 'shared_counts_cache_status', false );
}
add_action( 'admin_init', 'sccs_settings_init' );


/**
 * Add the Settings page.
 *
 * @since 1.0.0
 */
function sccs_settings_add() {

	add_options_page( __( 'Shared Counts Cache', 'sccs' ), __( 'Shared Counts Cache', 'sccs' ), 'manage_options', 'shared_counts_cache_status', 'sccs_settings_page' );
}
add_action( 'admin_menu', 'sccs_settings_add' );

/**
 * Build the Settings page.
 *
 * @since 1.0.0
 */
function sccs_settings_page() {

	if( ! function_exists( 'shared_counts' ) ) {
		echo '<p>Error: The <a href="https://wordpress.org/plugins/shared-counts/">Shared Counts</a> plugin must be active.</p>';
		return;
	}


	echo '<div class="wrap">';
	echo '<h2>Shared Counts Cache</h2>';

	if( !empty( $_GET['cache_status'] ) ) {
		echo '<p>Loading share data...</p>';
		echo '<p>When page has <strong>finished loading</strong> you can <a href="' . admin_url( 'options-general.php?page=shared_counts_cache_status' ) . '">click here</a> to see the updated cache information.</p>';

	} else {

		$counts = scps_shared_counts_posts();
		$total = sccs_available_posts();
		$percentage = (int) ( ( $counts / $total) * 100 + .5 );
		echo '<p>' . $counts . ' of ' . $total . ' items have Shared Counts data (' . $percentage . '%).</p>';
		if( $total > $counts )
			echo '<p><a href="' . add_query_arg( 'cache_status', 1, admin_url( 'options-general.php?page=shared_counts_cache_status' ) ) . '">Update posts with missing data</a>';
	}

	echo '</div>';
}

/**
 * Prime Cache
 *
 */
function sccs_cache_status() {

	if( ! function_exists( 'shared_counts' ) )
		return;

	$screen = get_current_screen();
	if( 'settings_page_shared_counts_cache_status' != $screen->base )
		return;

	if( empty( $_GET['cache_status'] ) )
		return;

	shared_counts()->core->prime_the_pump( sccs_available_posts(), apply_filters( 'sccs_interval', 20 ) );


}
add_action( 'admin_head', 'sccs_cache_status' );

/**
 * Available Posts
 *
 */
function sccs_available_posts() {

	if( ! function_exists( 'shared_counts' ) )
		return;

	$options = shared_counts()->admin->options();
	$count = 0;
	foreach( $options['post_type'] as $post_type ) {
		$count += wp_count_posts( $post_type )->publish;
	}

	return $count;
}

/**
 * Shared Counts Posts
 *
 */
function scps_shared_counts_posts() {

	$options = shared_counts()->admin->options();
	$shared_counts = new WP_Query( array(
		'fields' => 'ids',
		'post_type' => $options['post_type'],
		'post_status' => 'publish',
		'meta_key' => 'shared_counts',
		'posts_per_page' => 1,
	));
	return $shared_counts->found_posts;

}
