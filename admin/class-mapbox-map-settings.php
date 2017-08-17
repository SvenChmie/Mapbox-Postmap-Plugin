<?php

// TODO: Create some parent structure for admin-side classes/functionality where the script enqueues etc. can live.

require_once plugin_dir_path(__FILE__) . '../includes/class-mapbox-post-map-base.php';

class Mapbox_Map_Settings extends Mapbox_Post_Map_Base {
	public function __construct () {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_menu', array($this, 'add_menu_entry'));
    	add_filter("plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'add_settings_link'));
	}

	function enqueue_admin_scripts($hook) {
		// enqueue the marker table and load_map script here.
		// TODO: check if we're in the right context.
		wp_enqueue_script("mb-settings-data", plugin_dir_url(__FILE__) . 'js/marker_table.js');
		wp_enqueue_script("mb-load-settings", plugin_dir_url(__FILE__) . 'js/load_settings_page.js', array('mb-settings-data'), '1.0', true);
	}

	public function add_settings_link($links) {
		$settings_link = '<a href="options-general.php?page=simple-author-box-options">Settings</a>';
        array_unshift( $links, $settings_link );
        return $links;
	}

	public function add_menu_entry() {
        global $mapbox_post_map_settings_page;
        $mapbox_post_map_settings_page = add_options_page( 'Mapbox Post Map', 'Mapbox Post Map', 'manage_options', 'mapbox-post-map-options', array( $this, 'display_settings_page' ) );
	}

	public function display_settings_page() {
        if( !current_user_can('manage_options') ) {
                wp_die('You do not have sufficient permissions to access this page.');
            }
        // display the settings here (or include file that does)
        ?>
        <div class='marker-table' id='marker-table'></div>
        <?php
        }
}