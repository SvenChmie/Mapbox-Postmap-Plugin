
function populateMarkerTable(markerData) {
	var table = document.getElementById('marker-table');
	if (!table)
		return;

	if (table.getElementsByTagName('thead').length < 1) {
		var header = table.createTHead();
		var headerRow = header.insertRow(0);
		var nameHeader = headerRow.insertCell(0);
		var locHeader = headerRow.insertCell(1);
		var typeHeader = headerRow.insertCell(2);
		nameHeader.innerHTML = "<b>Marker Title</b>";
		locHeader.innerHTML = "<b>Location (Lat,  Lng)</b>";
		typeHeader.innerHTML = "<b>Marker Type</b>";
	}

	// Get the body element of the table or create it if there is none
	var tableBodyElements = table.getElementsByTagName('tbody')
	if (tableBodyElements.length < 1) {
		var tableBody = document.createElement('tbody');
		table.appendChild(tableBody);
	}
	else var tableBody = tableBodyElements[0];

	if (!markerData) return;

	for (i = 0; i < markerData.features.length; i++) {
		var row = document.createElement('tr');
		var nameCell = row.insertCell(0);	// marker name
		var locCell = row.insertCell(1);	// marker location
		var typeCell = row.insertCell(2);	// marker type
		nameCell.innerHTML = markerData.features[i].properties.postTitle;
		locCell.innerHTML = markerData.features[i].geometry.coordinates[1] + ", " + markerData.features[i].geometry.coordinates[0];
		typeCell.innerHTML = "Post";
		tableBody.appendChild(row);
	}
}
