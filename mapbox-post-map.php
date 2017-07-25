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

require_once plugin_dir_path(__FILE__) . 'admin/class-mapbox-meta-box.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-mapbox-map-settings.php';
require_once plugin_dir_path(__FILE__) . 'public/class-mapbox-post-map.php';

// TODO: add checks if we are on the right page?
$mapbox_map = new Mapbox_Post_Map();
$mapbox_metabox = new Mapbox_Meta_Box();
$mapbox_settings = new Mapbox_Map_Settings();
