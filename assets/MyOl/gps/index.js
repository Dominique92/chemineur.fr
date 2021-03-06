/**
 * PWA
 */

// Force https to allow PWA and geolocation
// Force full script name of short url to allow PWA
if (!location.href.match(/(https|localhost).*index/)) {
	location.replace(
		(location.hostname == 'localhost' ? 'http://' : 'https://') +
		location.hostname +
		location.pathname + (location.pathname.slice(-1) == '/' ? scriptName : '') +
		location.search);

	throw 'Exit'; //HACK exit page
}

// Load service worker for web application install & updates
if ('serviceWorker' in navigator)
	navigator.serviceWorker.register(
		typeof service_worker == 'undefined' ? 'service-worker.js' : service_worker, {
			// Max scope. Allow service worker to be in a different directory
			scope: typeof scope == 'undefined' ? './' : scope,
		}
	)
	// Reload if the service worker md5 (including the total files key) has changed
	.then(function(reg) {
		reg.addEventListener('updatefound', function() {
			location.reload();
			//alert('location.reload');
		});
	});

/**
 * OPENLAYERS
 */
const areLiTags = document.getElementsByTagName('li').length,
	elListe = document.getElementById('liste'),

	help = 'Pour utiliser les cartes et le GPS hors réseau :\n' +
	'Avant le départ :\n' +
	'- Enregistrez un marque-page ou installez l‘application web (explorateur -> options -> ajouter à l‘écran d‘accueil)\n' +
	'- Choisissez une couche de carte\n' +
	'- Placez-vous au point de départ de votre randonnée\n' +
	'- Zoomez au niveau le plus détaillé que vous voulez mémoriser\n' +
	'- Déplacez-vous suivant le trajet de votre randonnée suffisamment lentement pour charger toutes les dalles\n' +
	'- Recommencez avec les couches de cartes que vous voulez mémoriser\n' +
	'* Toutes les dalles visualisées une fois seront conservées dans le cache de l‘explorateur quelques jours\n' +
	'Hors réseau :\n' +
	'- Ouvrez votre marque-page ou votre application\n' +
	'- Si vous avez un fichier .gpx dans votre mobile, visualisez-le en cliquant sur ▲\n' +
	'* Fonctionne bien sur Android avec Chrome, Edge, Samsung Internet, fonctions réduites avec Firefox & Safari\n' +
	'* Cette application ne permet pas d‘enregistrer le parcours\n' +
	'* Aucune donnée ni géolocalisation n‘est remontée ni mémorisée',

	controls = [
		controlTilesBuffer(4),
		controlLayerSwitcher(),
		controlPermalink(),

		new ol.control.Attribution({
			collapseLabel: '>',
		}),
		new ol.control.ScaleLine(),
		controlMousePosition(),
		controlLengthLine(),

		new ol.control.Zoom(),
		new ol.control.FullScreen({
			label: '', //HACK Bad presentation on IE & FF
			tipLabel: 'Plein écran',
		}),
		controlGeocoder(),
		controlGPS(),

		areLiTags ?
		controlButton({
			label: '\u25B3',
			title: 'Choisir une trace dans la liste / fermer',
			activate: function() {
				if (elListe)
					elListe.style.display = elListe.style.display == 'none' ? 'block' : 'none';
				window.scrollTo(0, 0);
				if (document.fullscreenElement)
					document.exitFullscreen();
			},
		}) :
		// No button display
		new ol.control.Control({
			element: document.createElement('div'),
		}),

		controlLoadGPX(),
		controlDownload(),
		controlButton({
			label: '?',
			title: help,
			activate: function() {
				alert(this.title);
			},
		}),
	],

	map = new ol.Map({
		target: 'map',
		controls: controls,
		view: new ol.View({
			constrainResolution: true, // Force le zoom sur la définition des dalles disponibles
		}),
	});

// Add a gpx layer if any arguments to the url
const gpxFile = location.search.replace('?', '').replace('gpx=', '');
if (gpxFile)
	window.addEventListener('load', function() {
		addLayer(gpxFile + '.gpx');
	});

function addLayer(url) {
	const layer = new ol.layer.Vector({
		source: new ol.source.Vector({
			format: new ol.format.GPX(),
			url: url,
		}),
		style: new ol.style.Style({
			stroke: new ol.style.Stroke({
				color: 'blue',
				width: 2,
			}),
		}),
	});

	// Zoom the map on the added features
	layer.once('prerender', function() {
		const features = layer.getSource().getFeatures(),
			extent = ol.extent.createEmpty();
		for (let f in features)
			ol.extent.extend(extent, features[f].getGeometry().getExtent());
		map.getView().fit(extent, {
			maxZoom: 17,
			size: map.getSize(),
			padding: [5, 5, 5, 5],
		});
	});

	map.addLayer(layer);

	//HACK needed because the layer only becomes active when in the map area
	map.getView().setZoom(1);

	// Mask the local .gpx file list
	if (elListe)
		elListe.style.display = 'none';
}