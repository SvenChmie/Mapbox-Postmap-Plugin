var postdata = [1, 2, 3, 4, 5];

function populateMarkerTable() {
	var table = document.getElementById('marker-table');
	if (!table)
		return;

	for (i = 0; i < postdata.length; i++) {
		var row = table.insertRow(i);
		var nameCell = row.insertCell(0);	// marker name
		var locCell = row.insertCell(1);	// marker location
		var typeCell = row.insertCell(2);	// marker type
		nameCell.innerHTML = "The marker name goes here";
		locCell.innerHTML = "The marker location goes here";
		typeCell.innerHTML = "The marker type goes here";
	}
}
