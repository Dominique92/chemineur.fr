<?php
require_once ('functions.php');
$index = $url_path.'index.php'; // Protect $url_path from erasing

// Get $title
ob_start(); // Don't display the next
include ($index);
ob_end_clean();

// Read manifest.json, replace some values & display it
echo read_replace (
	'manifest.json', [
		'My GPS' => isset ($title) ? $title : 'My GPS',
		'./' => $scope_path,
		'index.html' => $index,
	]	
);
