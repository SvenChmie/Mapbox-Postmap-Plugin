// Create our map and load all markers into it
// console.log("Loaded load_frontend_map.js");
// for now: create an interactive marker outside the function. Later, put all that in objects!
var interactiveMarker;	// why does this need to be here?
var map = createMap(postmap.is_interactive, '');
getPostLocations(frontendAjaxSuccessCallback, postmap.ajax_url, postmap.country, postmap.new_map_nonce);

function frontendAjaxSuccessCallback(response) {
	addPostsToMap(response, map);
}
