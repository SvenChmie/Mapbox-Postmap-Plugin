<?php
/**
 * Plugin Name: Mapbox Post Map
 * Plugin URI: http://www.gobigemma.com
 * Description: Add a Mapbox post map to your pages.
 * Version: 0.3.1
 * Author: Sven Chmielewski
 * Author URI: http://www.gobigemma.com
 * License: GPL3
 */

// for debugging
include 'ChromePhp.php';

global $mb_db_version;
$mb_db_version = '1.0';


/**
 * Code that runs when the plugin is activated.
 * Create database table when the plugin is activated.
 */
function activate_mapbox_post_map() {
    global $wpdb;
    global $mb_db_version;
    $installed_version = get_option('mb_db_version');

    if ( $installed_version !== $booking_db_version ) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $wpdb->prefix . 'mb_locdata';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name text NOT NULL,
            loclat text NOT NULL,
            loclng text NOT NULL,
            type text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta( $sql );
        add_option( 'mb_db_version', $mb_db_version );
    }
}

register_activation_hook( __FILE__, 'activate_mapbox_post_map' );
// Also run the activation function whenever the plugin loads to check if the database needs upgrading.
add_action( 'plugins_loaded', 'activate_mapbox_post_map' );

require_once plugin_dir_path(__FILE__) . 'admin/class-mapbox-meta-box.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-mapbox-map-settings.php';
require_once plugin_dir_path(__FILE__) . 'public/class-mapbox-post-map.php';

// TODO: add checks if we are on the right page?
$mapbox_map = new Mapbox_Post_Map();
$mapbox_metabox = new Mapbox_Meta_Box();
$mapbox_settings = new Mapbox_Map_Settings();
