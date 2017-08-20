<?php

// TODO: Create some parent structure for admin-side classes/functionality where the script enqueues etc. can live.

require_once plugin_dir_path(__FILE__) . '../includes/class-mapbox-post-map-base.php';

class Mapbox_Map_Settings extends Mapbox_Post_Map_Base {
	private $script_alias = 'mb-load-settings';
	private $name_field_id = "new_marker_name_field";
    private $location_field_id = "new_marker_coords_field";
    private $type_select_id = "new_marker_type_select";
    private $clear_button_id = "new_marker_clear_button";
    private $save_button_id = "new_marker_save_button";

	public function __construct () {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_menu', array($this, 'add_menu_entry'));
    	add_filter("plugin_action_links_" . plugin_basename( __FILE__ ), array( $this, 'add_settings_link'));
    	$this->register_marker_save_ajax_callback();
	}

	function enqueue_admin_scripts($hook) {
		// enqueue the marker table and load_map script here.
		// TODO: check if we're in the right context.
		wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
        wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '../css/map.css', array('mapbox-style'));
		wp_enqueue_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
		wp_enqueue_script('mb-load-map', plugin_dir_url(__FILE__) . '../includes/js/load_map.js', array('jquery', 'mapbox-gl-js'), '1.0', true);
		wp_enqueue_script("mb-settings-data", plugin_dir_url(__FILE__) . 'js/marker_table.js');
		wp_enqueue_script("mb-interactive-map", plugin_dir_url(__FILE__) . 'js/interactive_map.js');
		wp_enqueue_script($this->script_alias, plugin_dir_url(__FILE__) . 'js/load_settings_page.js', array('mb-load-map', 'mb-settings-data', 'mb-interactive-map'), '1.0', true);
		$this->localize_ajax_script();
	}

	function localize_ajax_script() {
        wp_localize_script($this->script_alias, 'postmap', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce('mb_create_map'),
            'name_field_id' => $this->name_field_id,
            'location_field_id' => $this->location_field_id,
            'type_select_id' => $this->type_select_id,
            'clear_button_id' => $this->clear_button_id,
            'save_button_id' => $this->save_button_id,
        ));
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
        ?>
        <div id='map' class='map mapboxgl-map'></div>
        <div>
            <p>
                <label for='<?php echo $this->name_field_id; ?>' class='name_text_label'>Marker Name:</label>
                <input type='text' name='<?php echo $this->name_field_id; ?>' id='<?php echo $this->name_field_id; ?>' value="" />
                <label for='<?php echo $this->location_field_id; ?>' class='coord-text-label'>Coordinates:</label>
                <input type='text' name='<?php echo $this->location_field_id; ?>' id='<?php echo $this->location_field_id; ?>' value="" />
                <label for='<?php echo $this->type_select_id; ?>' class='type-text-label'>Marker Type:</label>
                <select name='<?php echo $this->type_select_id; ?>' id='<?php echo $this->type_select_id; ?>'>
				  <option value="event">Event</option>
				  <option value="other">Other</option>
				</select>
                <button type="button" class="coord-button" id='<?php echo $this->clear_button_id; ?>'>Clear</button>
                <button type="button" class="coord-button" id='<?php echo $this->save_button_id; ?>'>Save</button>
            </p>
        </div>
        <div class='marker-table-wrap' id='marker-table-wrap'>
        	<table class='marker-table' id='marker-table'></table>
        </div>
        <?php
        }

    function mb_save_new_location_set() {
    	// Check nonce
    	// TODO: give this call its own nonce!
		if( ! wp_verify_nonce( $_REQUEST['nonce'], $this->create_map_nonce_name) ){
        	wp_send_json_error();
        	return;
    	}

		global $wpdb;
		global $location_table_name;
		
		$table_name = $wpdb->prefix . $location_table_name;

		$marker_name, $marker_lat, $marker_lng, $marker_type;

		if ( isset( $_POST['markerName'] ) && ! empty( $_POST['markerName'] ) {
			$marker_name = sanitize_text_field($_POST['markerName']);
		}
		
		if ( isset( $_POST['markerLocation'] ) && ! empty( $_POST['markerLocation'] ) {
			$marker_location = explode(',', sanitize_text_field($_POST['markerLocation']), 2);
        if (sizeof($marker_location) == 2) {
        	$marker_lng = $marker_location[1];
        	$marker_lat = $marker_location[0];
        }

		if ( isset( $_POST['markerType'] ) && ! empty( $_POST['markerType'] ) {
			$marker_type = sanitize_text_field($_POST['markerType']);
		}

		if (empty($marker_name) || empty($marker_lat) || empty($marker_lng) || empty($marker_type)) {
			wp_send_json_error();
			return;
		}

		// write data to database
		$wpdb->insert( 
			$table_name, 
			array( 
				'name' => $marker_name, 
				'loclat' => $marker_lat, 
				'loclng' => $marker_lng,
				'type' => $marker_type
			) 
		);

		// This needs to change, it only fetches post locations and not event locations (which is the only thing that changes).
		wp_send_json($this->get_post_locations_from_wp(""));
    }

    private function register_marker_save_ajax_callback() {
		add_action('wp_ajax_mb_save_new_location_set', array($this, 'mb_save_new_location_set'));
	}
}