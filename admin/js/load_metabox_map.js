// Create our map and load all markers into it

// for now: create an interactive marker outside the function. Later, put all that in objects!
// ToDo: don't need to pass map, since it's global!
var interactiveMarker;

// Create the map
var map = createMap(postmap.is_interactive, postmap.location_field_id);

// Populate the map with static post markers
getPostLocations(map, postmap.ajax_url, postmap.country, postmap.nonce);

// Set the interactive marker for the current post
var currentPostLocation = convertToLngLat(postmap.post_location);
updateInteractiveMarker(map, currentPostLocation);
updateLocationField(postmap.location_field_id, currentPostLocation);

// Handle key press event for enter key in the coordinate field
var locationFieldElement = document.getElementById(postmap.location_field_id);
if (locationFieldElement) {
	locationFieldElement.addEventListener("keypress", function(e) {
		var key = e.which || e.keyCode;
		if (key === 13) { // 13 is enter
			e.preventDefault();
			var coordinates =  convertToLngLat(locationFieldElement.value);
	    	updateInteractiveMarker(map, coordinates);
	    	flyToMarker(coordinates, false);
	    	return false;
	    }
	});
}

// Handle button press event for the clear button
var locationButtonElement = document.getElementById(postmap.location_button_id);
if (locationButtonElement) {
	locationButtonElement.addEventListener("click", function(e) {
		locationFieldElement.value = '';
		removeInteractiveMarker();
	});
}

function convertToLngLat(inputStr) {
	var coords = inputStr.split(',');
	if (!coords.length == 2)
		return [];
	return new mapboxgl.LngLat(parseFloat(coords[1]), parseFloat(coords[0]));
}