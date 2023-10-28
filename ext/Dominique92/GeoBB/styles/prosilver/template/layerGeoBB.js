myol.trace();

var map = new ol.Map({
	target: 'map',
	view: new ol.View({
		enableRotation: false,
	}),
	controls: [
		...myol.control.collection(),
		new myol.control.LayerSwitcher({
			layers: myol.layer.tile.collection(mapKeys),
			selectExtId: 'select-ext',
		}),
		new myol.control.Permalink({
			init: mapType != 'line' || scriptName != 'viewtopic',
			display: scriptName == 'index',
		}),
	],
	layers: [
		new myol.layer.vector.GeoBB({
			selectName: 'select-geobb',
			host: '', // Relative to this location
			noClick: scriptName == 'posting',
			noHover: scriptName == 'posting',
			/*
			urlParams: { //BEST implement ???
				v: version, // Reload layer if posting called between
			},
			*/
		}),
		new myol.layer.Hover(),
	],
});

if (mapType == 'point')
	map.addLayer(new myol.layer.Marker({
		src: 'ext/Dominique92/GeoBB/styles/prosilver/theme/images/' + scriptName + '.svg',
		focus: 15, // Map zoom level
		dragable: scriptName == 'posting',
	}));

if (mapType == 'line' && scriptName == 'viewtopic') {
	const geoJson = document.getElementById('marker-json'),
		features = new ol.format.GeoJSON().readFeatures(geoJson.value, {
			featureProjection: "EPSG:3857",
		}),
		extent = ol.extent.createEmpty();

	for (let f in features)
		ol.extent.extend(extent, features[f].getGeometry().getExtent());

	map.getView().fit(extent, {
		maxZoom: 15,
	});
}

if (mapType == 'line' && scriptName == 'posting')
	//BEST save only layerEditGeoJson.layer
	map.addLayer(new myol.layer.Editor({
		geoJsonId: 'marker-json',
		editOnly: 'line',
	}));