// populate the marker table
console.log("Running load_settings_page.js!");
getPostLocations(settingsAjaxSuccessCallback, postmap.ajax_url, "", postmap.nonce);

function settingsAjaxSuccessCallback(response) {
	populateMarkerTable(response);
}
