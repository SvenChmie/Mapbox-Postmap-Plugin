// This file contains some JQuery code, but it sure could be a lot more efficient.
// Fix that one day.

function populateMarkerTable(markerData) {
	var table = document.getElementById('marker-table');
	if (!table)
		return;

	if (table.children('thead').length < 1) {
		var header = table.createTHead();
		var headerRow = header.insertRow(0);
		var nameHeader = headerRow.insertCell(0);
		var locHeader = headerRow.insertCell(1);
		var typeHeader = headerRow.insertCell(2);
		nameHeader.innerHTML = "<b>Marker Title</b>";
		locHeader.innerHTML = "<b>Location (Lat,  Lng)</b>";
		typeHeader.innerHTML = "<b>Marker Type</b>";
	}

	var tableContent;

	for (i = 0; i < markerData.features.length; i++) {
		var row = document.createElement('tr');
		var nameCell = row.insertCell(0);	// marker name
		var locCell = row.insertCell(1);	// marker location
		var typeCell = row.insertCell(2);	// marker type
		nameCell.innerHTML = markerData.features[i].properties.postTitle;
		locCell.innerHTML = markerData.features[i].geometry.coordinates[1] + ", " + markerData.features[i].geometry.coordinates[0];
		typeCell.innerHTML = "Post";
		tableContent.append(row);
	}

	if (table.children('tbody').length < 1)
		table.appendChild(document.createElement('tbody'));

	table.children('tbody').html(tableContent);
}
