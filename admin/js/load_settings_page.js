// requires: load_map.js, marker_table.js, interactive_map.js

// populate the marker table
console.log("Running load_settings_page.js!");

var interactiveMarker;

// Create the map
var map = createMap(true, postmap.location_field_id);
setLocationFieldKeyPressEvent(postmap.location_field_id);
setClearButtonPressEvent(postmap.clear_button_id);
setSaveButtonPressEvent(postmap.save_button_id, 
						postmap.name_field_id, 
						postmap.location_field_id, 
						postmap.type_select_id, 
						postmap.ajax_url, 
						postmap.nonce, 
						settingsAjaxSuccessCallback);

getPostLocations(settingsAjaxSuccessCallback, postmap.ajax_url, "", postmap.nonce);

function settingsAjaxSuccessCallback(response) {
	populateMarkerTable(response);
}

function setSaveButtonPressEvent(buttonID, nameFieldID, locationFieldID, typeSelectID, ajaxUrl, nonce, ajaxSuccessCallback) {
	var locationButtonElement = document.getElementById(buttonID);
	if (locationButtonElement) {
		locationButtonElement.addEventListener("click", function(e) {
			var markerName = document.getElementById(nameFieldID).value;
			var markerLocation =  document.getElementById(locationFieldID).value;
			var markerType = document.getElementById(typeSelectID).value;
			if (markerName == '' || markerLocation == '' || markerType == '') {
				alert("Please fill out all fields.");
				return;
			}
			var dataString = 'marker_name=' + markerName + '&marker_location=' + markerLocation + '&marker_type=' + markerType;
			jQuery.ajax({
			    url : ajaxUrl,
			    type : 'post',
			    data : {
			      action : 'mb_save_new_location_set',
			      nonce : nonce,
			      data: dataString
			    },
			    error : function(request, error) {
			      console.log(error);
			    },
			    success : ajaxSuccessCallback
		  });
		});
	}
}