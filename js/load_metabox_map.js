// Create our map and load all markers into it

// for now: create an interactive marker outside the function. Later, put all that in objects!
// ToDo: don't need to pass map, since it's global!
var interactive_marker;

// Create the map
var map = createMap(postmap.is_interactive, postmap.location_field_id);

// Populate the map with static post markers
get_post_locations(map, postmap.ajax_url, postmap.country, postmap.nonce);

// Set the interactive marker for the current post
var current_post_location = convertToLngLat(postmap.post_location);
updateInteractiveMarker(map, current_post_location);
updateLocationField(postmap.location_field_id, current_post_location);

// Handle key press event for enter key in the coordinate field
var loc_field_el = document.getElementById(postmap.location_field_id);
if (loc_field_el) {
	loc_field_el.addEventListener("keypress", function(e) {
		var key = e.which || e.keyCode;
		if (key === 13) { // 13 is enter
			e.preventDefault();
			var coordinates =  convertToLngLat(loc_field_el.value);
	    	updateInteractiveMarker(map, coordinates);
	    	flyToMarker(coordinates, false);
	    	return false;
	    }
	});
}

// Handle button press event for the clear button
var loc_button_el = document.getElementById(postmap.location_button_id);
if (loc_button_el) {
	loc_button_el.addEventListener("click", function(e) {
		loc_field_el.value = '';
		removeInteractiveMarker();
	});
}

function convertToLngLat(inputStr) {
	var coords = inputStr.split(',');
	if (!coords.length == 2)
		return [];
	return new mapboxgl.LngLat(parseFloat(coords[1]), parseFloat(coords[0]));
}