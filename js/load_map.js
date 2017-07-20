function createMap(is_interactive, location_field_id) {
  // Tell Mapbox that we're allowed to access it
  mapboxgl.accessToken = 'pk.eyJ1IjoiZ29iaWdlbW1hIiwiYSI6ImNpeWoxNHAxYzA1Y3AzMm12dXZ4NjAxMzYifQ.uHWQ11XpdfCnQNI-vDYkew';

  // Create the map
  var map = new mapboxgl.Map({
    container: 'map', 
    style: 'mapbox://styles/mapbox/light-v9', 
    center: [-90.573345, 14.911650],
    zoom: 1
  });

  // Add navigation controls to map
  map.addControl(new mapboxgl.NavigationControl());

  // Handle clicks on the map
  map.on('click', function (e) {
    // Change marker styles back to inactive marker
    deactivateMarkers();
    // If this map is interactive and no popup has been closed, create marker in clicked location
    if (!removePopUp() && is_interactive) {
      updateInteractiveMarker(map, e.lngLat);
      updateLocationField(location_field_id, e.lngLat);
    }
  });
  return map;
}

function get_post_locations(map, ajax_url, country, nonce) {
  // Create the AJAX request to obtain the post data
  jQuery.ajax({
    url : ajax_url,
    type : 'post',
    data : {
      action : 'mb_get_post_locations',
      country : country,
      nonce : nonce,
    },
    success : function( response ) {
      add_posts_to_map(response, map);
    }
  });
}

function add_posts_to_map(post_locations, map) {
  // Add the post data to our map
  map.on('load', function (e) {
    map.addSource('places', {
      type: 'geojson',
      data: post_locations
    });
  });

  post_locations.features.forEach(function(marker, i) {
    // Create a div element for the marker
    var el = document.createElement('div');
    // Add a class called 'marker' to each div
    el.id = "marker-" + i;
    el.className = 'marker';
    // By default the image for your custom marker will be anchored
    // by its top left corner. Adjust the position accordingly
    el.style.left = '-14px';
    el.style.top = '-14px';
    
    var inner_el = document.createElement('div');
    inner_el.className = "marker-inner";
    el.appendChild(inner_el);
    // Create the custom markers, set their position, and add to map
    newmarker = new mapboxgl.Marker(el)
        .setLngLat([parseFloat(marker.geometry.coordinates[0]), parseFloat(marker.geometry.coordinates[1])])
        .addTo(map);

    el.addEventListener('click', function(e) {
        // change marker image for clicked marker
        deactivateMarkers();
        activateMarker(this);

        flyToMarker(marker.geometry.coordinates);
        createPopUp(marker);
        // Make sure the map is updated and the popup is rendered
        var activeItem = document.getElementsByClassName('active');
        e.stopPropagation();
        if (activeItem[0]) {
          activeItem[0].classList.remove('active');
        }
    });
  });

  // Make map fit bounds of all markers on it
  var coordinates = [];
  for (var i = 0; i < post_locations.features.length; i++) {
    coordinates.push(post_locations.features[i].geometry.coordinates)
  };
  if (coordinates.length > 0) {
    var bounds = coordinates.reduce(function(bounds, coord) {
          return bounds.extend(coord);
      }, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));
    map.fitBounds(bounds, {
        padding: 60,
        maxZoom: 10
    }); 
  }
}

function activateMarker(markerElement) {
  markerElement.style.left = '-20px';
  markerElement.style.top = '-20px';
  var inner_div = markerElement.getElementsByClassName('marker-inner');
  if (inner_div[0]) inner_div[0].className = inner_div[0].className.replace( /(?:^|\s)marker-inner(?!\S)/g , 'marker-clicked' );
}

function deactivateMarkers() {
  var active_markers = document.getElementsByClassName('marker-clicked');
  for (i = 0; i < active_markers.length; i++) {
    var markerDiv = active_markers[i].parentNode;
    markerDiv.style.left = '-14px';
    markerDiv.style.top = '-14px';
    active_markers[i].className = active_markers[i].className.replace
      ( /(?:^|\s)marker-clicked(?!\S)/g , 'marker-inner' );
  }    
}

function flyToMarker(coordinates, allowZoom = true) {
  var currentZoom = map.getZoom();
  if (allowZoom && currentZoom < 5) currentZoom = 5;
  map.flyTo({
    center: coordinates,
    zoom: currentZoom
  });
}

function createPopUp(currentFeature) {
  var popUps = document.getElementsByClassName('mapboxgl-popup');
  // Check if there is already a popup on the map and if so, remove it
  if (popUps[0]) popUps[0].remove();
    var popup = new mapboxgl.Popup({ closeOnClick: false, offset: 25 })
    .setLngLat(currentFeature.geometry.coordinates)
    .setHTML('<div class="map-popup-content" style="background-image: url(\'' + currentFeature.properties.postThumbnailUrl + '\');"><span class="cat-title cat-' + currentFeature.properties.postCatId + '"><a style="color: #ffffff" href="' + currentFeature.properties.postCatLink + '">' + currentFeature.properties.postCatName + '</a></span><div class="map-popup-content-title"><span><a href="' + currentFeature.properties.postLink +'">' + currentFeature.properties.postTitle + '</a></span><div style="clear:both"></div></div></div>')
    .addTo(map);
}

function removePopUp() {
  var popUps = document.getElementsByClassName('mapboxgl-popup');
  if (popUps[0]) {
    popUps[0].remove();
    return true;  // return true if popup was removed, false if there wasn't a popup
  }
  return false;
}

function createInteractiveMarker(map, lngLat) {
  var interactive_marker_div = document.createElement('div');
  interactive_marker_div.id = "interactive-marker-1";
  interactive_marker_div.className = 'interactive-marker';
  interactive_marker = new mapboxgl.Marker(interactive_marker_div)
      .setLngLat(lngLat)
      .addTo(map);
}

function updateInteractiveMarker(map, lngLat) {
  if (typeof interactive_marker == "undefined" ){
    createInteractiveMarker(map, lngLat);
  } else {
    interactive_marker.setLngLat(lngLat);
  }
}

function removeInteractiveMarker() {
  interactive_marker.remove();
  interactive_marker = void 0; // this is supposed to be an idiomatic way to reset the marker var to undefined. See https://stackoverflow.com/questions/5795936/how-to-set-a-javascript-var-as-undefined/24748543#24748543
}

function updateLocationField(location_field_id, lngLat) {
  var location_field = document.getElementById(location_field_id);
  if (location_field) {
    location_field.value = String(lngLat.lat) + ", " + String(lngLat.lng);
  }
}