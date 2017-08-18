<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-mapbox-post-map-base.php';

class Mapbox_Post_Map extends Mapbox_Post_Map_Base {
    private $map_script_alias = 'mb-script';

    function __construct() {
        // This constructor register the AJAX callback for the entire plugin, which is... not correct. Should be done somewhere else..
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 20);
        $this->register_ajax_callback();
        add_shortcode( 'post_map', array($this, 'shortcode_post_map'));
    }

    function enqueue_styles() {
        if (! is_single() and ! is_page())
            return;
        wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
        wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '../css/map.css', array('mapbox-style'));
    }

    function register_scripts() {
        if (! is_single() and ! is_page())
            return;
        wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
        wp_register_script('mb-load-map', plugin_dir_url(__FILE__) . '../includes/js/load_map.js', array('jquery'), '1.0', true);
        wp_register_script($this->map_script_alias, plugin_dir_url(__FILE__) . 'js/load_frontend_map.js', array('jquery', 'mb-load-map'), '1.0', true);
    }

    function shortcode_post_map( $atts ){
        // Get optional country name from shortcode 
        $atts = shortcode_atts( array('country' => ''), $atts, 'post_map' );

        $this->enqueue_scripts($this->map_script_alias, $atts['country']);
        return "<div id='map' class='map mapboxgl-map'></div>";
    }

    function localize_ajax_script($map_script_alias, $country, $is_interactive) {
        $this->create_nonce();
        wp_localize_script($map_script_alias, 'postmap', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ), 
            'country' => $country,
            'is_interactive' => (bool) $is_interactive,
            'nonce' => $this->create_map_nonce,
        ));
    }

    function enqueue_scripts($map_script_alias, $country = "", $is_interactive = false) {
        wp_enqueue_script('mapbox-gl-js');
        wp_enqueue_script($map_script_alias);
        $this->localize_ajax_script($map_script_alias, $country, $is_interactive);
    }
}