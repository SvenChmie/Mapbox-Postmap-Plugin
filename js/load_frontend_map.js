// Create our map and load all markers into it
console.log("Loaded load_frontend_map.js");
// for now: create an interactive marker outside the function. Later, put all that in objects!
var interactive_marker;
var map = createMap(postmap.is_interactive, '');
get_post_locations(map, postmap.ajax_url, postmap.country, postmap.nonce);
