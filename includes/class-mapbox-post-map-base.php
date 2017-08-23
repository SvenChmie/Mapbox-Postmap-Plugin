<?php

class Mapbox_Post_Map_Base {
	private $new_map_nonce_name = 'mb_create_map';
	// private $new_map_nonce = null;

	function __construct() {
		// nothing to see here
	}

	function create_new_map_nonce() {
		// This nonce is used to verify intent on fetching post location data for map creation. That action is an AJAX call to get_post_locations().
		$this->new_map_nonce = wp_create_nonce($this->new_map_nonce_name);
	}


	function get_post_locations_from_wp($mb_country) {
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
		return $geojson_output_data;
	}

	function get_post_locations() {
		// Check nonce
		if( ! wp_verify_nonce( $_REQUEST['nonce'], $this->new_map_nonce_name) ){
        	wp_send_json_error();
    	}

		// Get country filter through AJAX
		$mb_country = $_POST['country'];

		// Get output data and send it as JSON
		wp_send_json($this->get_post_locations_from_wp($mb_country));
	}
		
	function register_ajax_callback() {
		add_action('wp_ajax_nopriv_mb_get_post_locations', array($this, 'get_post_locations'));
		add_action('wp_ajax_mb_get_post_locations', array($this, 'get_post_locations'));
	}
}