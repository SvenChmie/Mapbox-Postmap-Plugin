// populate the marker table
console.log("Running load_settings_page.js!");
var markerData = getPostLocations(postmap.ajax_url, "", postmap.nonce);
populateMarkerTable(markerData);