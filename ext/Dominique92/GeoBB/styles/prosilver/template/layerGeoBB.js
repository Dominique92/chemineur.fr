myol.trace();

// Force at least one layer
if (!localStorage.myol_selectgeobb && scriptName == 'index')
	localStorage.myol_selectgeobb = 'all';

// Activate the layer corresponding to the topic
if (typeof topic_category == 'string') {
	const ls = (localStorage.myol_selectgeobb || '').split(',');
	ls.push(topic_category);
	localStorage.myol_selectgeobb = [...new Set(ls)];
}

// Desactivate trace layer when edit a trace
if (mapType == 'line' && scriptName == 'posting' && localStorage.myol_selectgeobb)
	localStorage.myol_selectgeobb = localStorage.myol_selectgeobb.toString().replace('64', '');

var geobbLayer = new myol.layer.vector.GeoBB({
		selectName: 'select-geobb',
		host: '', // Relative to this location
		noClick: scriptName == 'posting',
		noHover: scriptName == 'posting',
	}),

	markerLayer = new myol.layer.Marker({
		src: 'ext/Dominique92/GeoBB/styles/prosilver/theme/images/' + scriptName + '.svg',
		focus: 15, // Map zoom level
		dragable: scriptName == 'posting',
	}),

	editorLayer = new myol.layer.Editor({
		geoJsonId: 'marker-json',
		editOnly: 'line',
	}),

	map = new ol.Map({
		target: 'map',
		view: new ol.View({
			enableRotation: false,
		}),
		controls: [
			...myol.control.collection({
				download: {
					savedLayer: editorLayer,
				}
			}),
			new myol.control.LayerSwitcher({
				layers: myol.layer.tile.collection(mapKeys),
				selectExtId: 'select-ext',
			}),
			new myol.control.Permalink({
				init: mapType != 'point' && (mapType != 'line' || scriptName != 'viewtopic'),
				display: scriptName == 'index',
			}),
		],
		layers: [
			geobbLayer,
			new myol.layer.Hover(),
		],
	});

if (mapType == 'point')
	map.addLayer(markerLayer);

if (mapType == 'line' && scriptName == 'posting')
	map.addLayer(editorLayer);

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

// Resize map
if (jQuery.ui)
	$(map.getTargetElement()).resizable({
		handles: 's,w,sw', // 2 sides and 1 corner

		resize: function(event, ui) {
			ui.position.left = ui.originalPosition.left; // Reste à droite de la page
			map.updateSize(); // Repost all new <div>
		},
	});