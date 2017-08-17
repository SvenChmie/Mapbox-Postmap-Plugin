var postdata = [1, 2, 3, 4, 5];

function populateMarkerTable(markerData) {
	console.log("In populateMarkerTable()");
	console.log(markerData);
	var table = document.getElementById('marker-table');
	if (!table)
		return;
	console.log("Found table div!");

	for (i = 0; i < markerData.length; i++) {
		var row = table.insertRow(i);
		var nameCell = row.insertCell(0);	// marker name
		var locCell = row.insertCell(1);	// marker location
		var typeCell = row.insertCell(2);	// marker type
		nameCell.innerHTML = markerData[i].properties.postTitle;
		locCell.innerHTML = "The marker location goes here";
		typeCell.innerHTML = "The marker type goes here";
	}
}
