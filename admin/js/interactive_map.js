// requires: load_map.js

function setLocationFieldKeyPressEvent(fieldID, key=13) {
	var locationFieldElement = document.getElementById(fieldID);
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
}

function setClearButtonPressEvent(buttonID) {
	var locationButtonElement = document.getElementById(buttonID);
	if (locationButtonElement) {
		locationButtonElement.addEventListener("click", function(e) {
			locationFieldElement.value = '';
			removeInteractiveMarker();
		});
	}
}