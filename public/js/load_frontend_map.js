// Create our map and load all markers into it
// console.log("Loaded load_frontend_map.js");
// for now: create an interactive marker outside the function. Later, put all that in objects!
var interactiveMarker;	// why does this need to be here?
var map = createMap(postmap.is_interactive, '');
var postLocations = getPostLocations(postmap.ajax_url, postmap.country, postmap.nonce);
addPostsToMap(postLocations, map);
