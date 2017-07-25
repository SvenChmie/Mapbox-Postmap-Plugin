<?php

// TODO: Create some parent structure for admin-side classes/functionality where the script enqueues etc. can live.

class Mapbox_Map_Settings {
	public function __construct () {
		add_action('admin_menu', array($this, 'add_menu_entry'));
    	add_filter("plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'add_settings_link'));
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
        <div>Hello World!</div>
        <?php
        }
}