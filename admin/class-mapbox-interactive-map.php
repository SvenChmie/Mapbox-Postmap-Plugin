<?php

// Note: This is the future base class for all interactive maps (currently in metaboxes and in the settings). As of now, this doesn't do anything (I think...).

require_once plugin_dir_path(__FILE__) . '../includes/class-mapbox-post-map-base.php';

abstract class Mapbox_Interactive_Map extends Mapbox_Post_Map_Base {
    function __construct() {
        // nothing here    
    }

    function enqueue_general_styles() {
        wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
        wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '../css/map.css', array('mapbox-style'));
    }

    function register_general_scripts() {
        wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
        // the following register call might change
        wp_register_script('mb-load-map', plugin_dir_url(__FILE__) . '../includes/js/load_map.js', array('jquery'), '1.0', true);
    }

    function enqueue_admin_scripts($hook) {
        if (!$this->check_context($hook)) {
            return;
        }
        
        $this->enqueue_general_styles();
        $this->register_general_scripts();
        wp_register_script($this->map_script_alias, plugin_dir_url(__FILE__) . 'js/load_metabox_map.js', array('jquery', 'mb-load-map'), '1.0', true);
        // this call might change too
        $this->enqueue_scripts($this->map_script_alias, "", true);
    }

    abstract function check_context($hook) {
        // fill this with content in the derived class!
    }

    function localize_ajax_script($map_script_alias, $localization_data) {
        // $post_location = get_post_meta( get_the_ID(), 'mb-location', true );
        wp_localize_script($map_script_alias, 'postmap', $localization_data);
    }

    function enqueue_scripts($map_script_alias) {
        wp_enqueue_script('mapbox-gl-js');
        wp_enqueue_script($map_script_alias);
        // $this->localize_ajax_script($map_script_alias, $country, $is_interactive);
    }

}