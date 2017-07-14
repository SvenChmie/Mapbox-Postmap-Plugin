<?php
/**
 * Plugin Name: Mapbox Post Map
 * Plugin URI: http://www.gobigemma.com
 * Description: Add a Mapbox post map to your pages.
 * Version: 0.2.0
 * Author: Sven Chmielewski
 * Author URI: http://www.gobigemma.com
 * License: GPL3
 */

// for debugging
include 'ChromePhp.php';

class MapboxPostMap {
	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueue_mb_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_map_styles'), 20);
		add_action('wp_ajax_nopriv_mb_get_post_locations', array($this, 'mb_get_post_locations'));
		add_action('wp_ajax_mb_get_post_locations', array($this, 'mb_get_post_locations'));
		add_shortcode( 'post_map', array($this, 'shortcode_post_map'));
	}

	function enqueue_map_styles() {
		if (! is_single() and ! is_page())
			return;
		wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
		wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '/css/map.css', array('mapbox-style'));
	}

	function enqueue_mb_scripts() {
		if (! is_single() and ! is_page())
			return;
		wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
		wp_register_script('mb-script', plugin_dir_url(__FILE__) . '/js/load_map.js', array('jquery'), '1.0', true);
	}

	function mb_get_post_locations() {
		// Check nonce
		if( ! wp_verify_nonce( $_REQUEST['nonce'], 'mb_create_map' ) ){
        	wp_send_json_error();
    	}

		// Get country filter through AJAX
		$mb_country = $_POST['country'];

		// Set up output data structure
		$geojson_output_data = new stdClass();
		$geojson_output_data->type = "FeatureCollection";
		$geojson_output_data->features = array();

		// Set up WP query
		$args = array(
		    'post_type' => 'post',
		    'posts_per_page' => '-1'
		);

		$post_query = new WP_Query($args);

		// Loop through posts
		if($post_query->have_posts() ) {
		    while($post_query->have_posts() ) {
		        $post_query->the_post(); 

		        // Check if post is published, skip if not
		        if (get_post_status() != 'publish') {
		        	continue;
		        }

		        // If country is set, get the tag to check if we care about this post
		        if ((!$mb_country) or (($mb_country) and (has_tag($mb_country)))) {

		            // Get location
		            $location = get_field('map_location');
		            if( !empty($location)) {

		                $post_obj = new stdClass();

		                // Create GeoJSON structure
		                $post_obj->type = "Feature";
		                $post_obj->geometry->type = "Point";

		                // Set location
		                $post_obj->geometry->coordinates = array($location['lng'], $location['lat']);

		                // Get the post title
		                $post_obj->properties->postTitle = the_title('', '', false);


		                $post_obj->properties->postLink = get_permalink();

		                // Get the category name, link, and color  
		                if (($cat_label = Bunyad::posts()->meta('cat_label'))) {
		                    $post_obj->properties->postCatId = get_category($cat_label)->cat_ID;
		                    $post_obj->properties->postCatName = esc_html(get_category($cat_label)->name);

		                }
		                else {
		                    $post_obj->properties->postCatId = current(get_the_category())->cat_ID;
		                    $post_obj->properties->postCatName = esc_html(current(get_the_category())->name);

		                }
		                $post_obj->properties->postCatLink = esc_url(get_category_link($post_obj->properties->postCatId));

		                // Get the post thumbnail url
		                $thumbnail_url = get_the_post_thumbnail_url(null, 'grid-slider-small');
		                if ($thumbnail_url == "false") {
		                    $post_obj->properties->postThumbnailUrl = "";
		                }
		                else {
		                    $post_obj->properties->postThumbnailUrl = $thumbnail_url;
		                }

		                // Get post id
		                $postid = get_the_ID();
		                if ($postid == "false") {
		                    $post_obj->properties->postID = "";
		                }
		                else {
		                    $post_obj->properties->postID = $postid;
		                }
		                array_push($geojson_output_data->features, $post_obj);
		            }
		        }
		    }
		}

		// Convert output data to JSON and return
		wp_send_json($geojson_output_data);
	}

	function shortcode_post_map( $atts ){
		// Get optional country name from shortcode 
		$atts = shortcode_atts( array('country' => ''), $atts, 'post_map' );

		// Enqueue map scripts
		wp_enqueue_script('mapbox-gl-js');
		wp_enqueue_script('mb-script');
		wp_localize_script( 'mb-script', 'postmap', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ), 
			'country' => $atts['country'],
			'nonce' => wp_create_nonce('mb_create_map'),
		));

		return "<div id='map' class='map mapboxgl-map'></div>";
	}
}

$map = new MapboxPostMap();