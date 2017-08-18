<?php

require_once plugin_dir_path(__FILE__) . '../includes/class-mapbox-post-map-base.php';

class Mapbox_Meta_Box extends Mapbox_Post_Map_Base {
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
        wp_enqueue_style('mb-style', plugin_dir_url(__FILE__) . '../css/map.css', array('mapbox-style'));
        wp_register_script('mapbox-gl-js', 'https://api.tiles.mapbox.com/mapbox-gl-js/v0.31.0/mapbox-gl.js');
        wp_register_script('mb-load-map', plugin_dir_url(__FILE__) . '../includes/js/load_map.js', array('jquery', 'mapbox-gl-js'), '1.0', true);
        wp_enqueue_script("mb-interactive-map", plugin_dir_url(__FILE__) . 'js/interactive_map.js');
        wp_register_script($this->map_script_alias, plugin_dir_url(__FILE__) . 'js/load_metabox_map.js', array('jquery', 'mb-load-map', 'mb-interactive-map'), '1.0', true);
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
        // ChromePhp::log($current_post_location);
        if (gettype($current_post_location) !== 'array' || !sizeof($current_post_location) === 2) {
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