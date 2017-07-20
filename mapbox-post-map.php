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

class MapboxPostMapBase {
	function __construct() {
		// nothinng to see here
	}

	function get_post_locations() {
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
		
	function register_ajax_callback() {
		add_action('wp_ajax_nopriv_mb_get_post_locations', array($this, 'get_post_locations'));
		add_action('wp_ajax_mb_get_post_locations', array($this, 'get_post_locations'));
	}
}

class MapboxPostMap extends MapboxPostMapBase {
	private $map_script_alias = 'mb-script';

	function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 20);
		$this->register_ajax_callback();
		add_shortcode( 'post_map', array($this, 'shortcode_post_map'));
	}

	function enqueue_styles() {
		if (! is_single() and ! is_page())
			return;
		wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
		wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '/css/map.css', array('mapbox-style'));
	}

	function register_scripts() {
		if (! is_single() and ! is_page())
			return;
		wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
		wp_register_script('mb-load-map', plugin_dir_url(__FILE__) . '/js/load_map.js', array('jquery'), '1.0', true);
		wp_register_script($this->map_script_alias, plugin_dir_url(__FILE__) . '/js/load_frontend_map.js', array('jquery', 'mb-load-map'), '1.0', true);
	}

	function shortcode_post_map( $atts ){
		// Get optional country name from shortcode 
		$atts = shortcode_atts( array('country' => ''), $atts, 'post_map' );

		$this->enqueue_scripts($this->map_script_alias, $atts['country']);
		return "<div id='map' class='map mapboxgl-map'></div>";
	}

	function localize_ajax_script($map_script_alias, $country, $is_interactive) {
		wp_localize_script($map_script_alias, 'postmap', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ), 
			'country' => $country,
			'is_interactive' => (bool) $is_interactive,
			'nonce' => wp_create_nonce('mb_create_map'),
		));
	}

	function enqueue_scripts($map_script_alias, $country = "", $is_interactive = false) {
		wp_enqueue_script('mapbox-gl-js');
		wp_enqueue_script($map_script_alias);
		$this->localize_ajax_script($map_script_alias, $country, $is_interactive);
	}
}

class MapboxMetaBox extends MapboxPostMapBase {
	// TODO: add hidden field with nonce in the callback!
	private static $metabox = array(
		'id' => 'mapbox-metabox-1',
		'title' => 'Post Location',
		'page' => 'post',
		'context' => 'normal',
		'priority' => 'high');

	private $map_script_alias = "mb-metabox-script";
	private $location_field_id = "coord-text";
	private $location_button_id = "coord-button";
	private $location = array('lat' => '', 'lng' => '');

	function __construct() {
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_menu', array($this, 'add_map_meta_box'));
		add_action('save_post', array($this, 'meta_box_save'));
	}

	function enqueue_admin_scripts($hook) {
		if( $hook != 'edit.php' && $hook != 'post.php' && $hook != 'post-new.php' ) 
			return;
		wp_enqueue_style('mapbox-style', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.css');
		wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '/css/map.css', array('mapbox-style'));
		wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
		wp_register_script('mb-load-map', plugin_dir_url(__FILE__) . '/js/load_map.js', array('jquery'), '1.0', true);
		wp_register_script($this->map_script_alias, plugin_dir_url(__FILE__) . '/js/load_metabox_map.js', array('jquery', 'mb-load-map'), '1.0', true);
		$this->enqueue_scripts($this->map_script_alias, "", true);
	}

	function add_map_meta_box() {
		add_meta_box(self::$metabox['id'], self::$metabox['title'], array($this, meta_box_callback), self::$metabox['page'], self::$metabox['context'], self::$metabox['priority']);
	}

	function meta_box_save($post_id) {
		$is_autosave = wp_is_post_autosave($post_id);
		$is_revision = wp_is_post_revision($post_id);
		$is_valid_nonce = (isset($_POST['mb_metabox_nonce']) && wp_verify_nonce($_POST['mb_metabox_nonce'], basename(__FILE__))) ? true : false;

		if ($is_autosave || $is_revision || !$is_valid_nonce) {
			return;
		}

		$this->get_current_post_location();
		update_post_meta($post_id, 'mb-location', $this->location);
		// if (isset($_POST[$this->location_field_id])) {
			// update_post_meta($post_id, 'mb-location', sanitize_text_field($_POST[$this->location_field_id]));
			
		// }
	}

	function meta_box_callback($post) {
		wp_nonce_field(basename(__FILE__), 'mb_metabox_nonce');
		?>
		<div id='map' class='map mapboxgl-map'></div>
		<div>
			<p>
        		<label for='<?php echo $this->location_field_id; ?>' class='coord-text-label'>Coordinates:</label>
        		<input type='text' name='<?php echo $this->location_field_id; ?>' id='<?php echo $this->location_field_id; ?>' value="" />
        		<button type="button" class="coord-button" id='<?php echo $this->location_button_id; ?>'>Clear</button>
    		</p>
    	</div>
    	<?php
	}

	function enqueue_scripts($map_script_alias, $country = "", $is_interactive = false) {
		wp_enqueue_script('mapbox-gl-js');
		wp_enqueue_script($map_script_alias);
		$this->localize_ajax_script($map_script_alias, $country, $is_interactive);
	}

	function localize_ajax_script($map_script_alias, $country, $is_interactive) {
		// $post_location = get_post_meta( get_the_ID(), 'mb-location', true );
		$post_location = $this->get_saved_post_location();
		wp_localize_script($map_script_alias, 'postmap', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ), 
			'country' => $country,
			'is_interactive' => (bool) $is_interactive,
			'location_field_id' => $this->location_field_id,
			'location_button_id' => $this->location_button_id,
			'post_location' => $post_location,
			'nonce' => wp_create_nonce('mb_create_map'),
		));
	}

	function get_saved_post_location() {
		$current_post_location = get_post_meta(get_the_ID(), 'mb-location', true);
		if (!gettype($current_post_location) == 'array' || !sizeof($current_post_location) === 2) {
			return '';
		}
		// If the checks passed, store location in object and return string representation
		$this->location = $current_post_location;
		return $this->location['lat'] . ', ' . $this->location['lng'];
	}

	function get_current_post_location() {
		$loc_strings = explode(',', sanitize_text_field($_POST[$this->location_field_id]), 2);
		if (sizeof($loc_strings) !== 2) {
			return false;
		}
		$this->location['lat'] = $loc_strings[0];
		$this->location['lng'] = $loc_strings[1];
		return true;
	}
}

// TODO: add checks if we are on the right page?
$map = new MapboxPostMap();
$metabox = new MapboxMetaBox();
