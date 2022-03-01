<?php
require_once ('functions.php');

header('Content-Type: application/javascript');

// Check new version each time the url is called
header('Expires: '.date('r'));
header('Cache-Control: no-cache');
header('Pragma: no-cache');
header('Service-Worker-Allowed: /');

// Calculate a key depending on the delivery (Total byte size of cached files)
$version_tag = 0;
foreach (array_merge (glob ('../*'), glob ('../*/*')) as $f)
	$version_tag += filesize ($f);

// Read service worker & replace some values
$service_worker = read_replace (
	'service-worker.js', [
		'index.html' => $url_path.'index.php',
		'manifest.json' => 'manifest.json.php',
		'myGpsCache' => 'myGpsCache_'.$version_tag,
	]	
);

// Add GPX files in the url directory to the list of files to cache
$gpx_files = glob ($url_path.'*.gpx');
foreach ($gpx_files as $gf) {
	$version_tag += filesize ($gf);
	$service_worker = str_replace (
		"addAll([",
		"addAll([\n\t\t\t\t'$gf',",
		$service_worker
	);
}

echo $service_worker;