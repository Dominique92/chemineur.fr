<?php
/**
* Extractions de données géographiques
*
* @copyright (c) Dominique Cavailhez 2015
* @license GNU General Public License, version 2 (GPL-2.0)
*
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../../../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Parameters
$layer = request_var ('layer', 'verbose'); // verbose (full data) | cluster (grouped points) | 'simple' (simplified)
$type = request_var ('type', ''); // List of forums to include "1,2,3"
$cat = request_var ('cat', ''); // List of categories of forums to include "1,2,3"
$bbox = explode (',', request_var ('bbox', ''));
$cluster_size = request_var ('cluster_size', 0.1); // ° Mercator
$limit = request_var ('limit', 200); // Nombre de points maximum

//BEST ? $priority = request_var ('priority', 0); // topic_id à affichage prioritaire
//BEST ? $select = request_var ('select', ''); // Post to display

$request_scheme = explode ('/', getenv('REQUEST_SCHEME'));
$request_uri = explode ('/ext/', getenv('REQUEST_URI'));
$url_base = $request_scheme[0].'://'.getenv('SERVER_NAME').$request_uri[0].'/';

$where = [
	'geom IS NOT NULL',
	'post_visibility = '.ITEM_APPROVED,
];
if ($type)
	$where[] = "forum_id IN ($type)";
if ($cat)
	$where[] = "parent_id IN ($cat)";

$where_domain = '';
if (count ($bbox) == 4) {
	$bbox_sql =
		$bbox[0].' '.$bbox[1].','.
		$bbox[2].' '.$bbox[1].','.
		$bbox[2].' '.$bbox[3].','.
		$bbox[0].' '.$bbox[3].','.
		$bbox[0].' '.$bbox[1];
	$where_domain = "\nand Intersects(GeomFromText('POLYGON(($bbox_sql))',4326),geom)";
}

$clusters = $isolated = $features = $hack_positions = [];
$debut=microtime(true);

// Extract clusters
if ($layer == 'cluster') {
	$sql="SELECT count(*), post_id, forum_image,
		ST_AsGeoJSON(ST_centroid(ST_Envelope(geom)),2) AS geojson
	FROM ".POSTS_TABLE."
		LEFT JOIN ".FORUMS_TABLE." USING(forum_id)
	WHERE ".implode ("\nand ", $where)."
		$where_domain
	GROUP BY round(ST_X(ST_centroid(ST_Envelope(geom)))/$cluster_size),
			 round(ST_Y(ST_centroid(ST_Envelope(geom)))/$cluster_size)";

	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result)) {
		if ($row['count(*)'] > 1 || // Don't cluster 1 point
			!$row['forum_image']) // Dont display traces on clusters layers
			$clusters[] = [
				'type' => 'Feature',
				'id' => $row['post_id'], // Pseudo id = post_id of the 1st item
				'geometry' => json_decode ($row['geojson'], true), // Pseudo position = position of the 1st item
				'properties' => [
					'cluster' => $row['count(*)'],
				],
			];
		else
			$isolated[] = $row['post_id'];
	}
	$db->sql_freeresult($result);
}
if (count ($clusters))
	$where_domain = "\nand post_id IN ('".implode("','",$isolated)."')";

// Extract other points
$sql="SELECT post_id, post_subject,
		topic_id,
		forum_name, forum_id, forum_image,
		geo_altitude,
		ST_AsGeoJSON(geom,5) AS geojson
	FROM ".POSTS_TABLE."
		LEFT JOIN ".FORUMS_TABLE." USING(forum_id)
	WHERE ".implode ("\nand ", $where).
		$where_domain.
	($limit ? " LIMIT $limit" : "");

$topic_ids = [];

$result = $db->sql_query($sql);
while ($row = $db->sql_fetchrow($result)) {
	$altitudes = array_filter (explode (',', str_replace ('~', '', $row['geo_altitude'])));

	$properties = [
		'id' => $row['topic_id'],
		'post_id' => $row['post_id'],
		'name' => $row['post_subject'],
	];

	if ($altitudes && $altitudes[0])
		$properties['alt'] = $altitudes[0];

	if ($layer == 'verbose') {
		$properties['type'] = $row['forum_name'];
		$properties['type_id'] = $row['forum_id'];
		$properties['link'] = $url_base.'viewtopic.php?t='.$row['topic_id'];
	}

	// Ajoute l'adresse complète aux images d'icones
	if ($row['forum_image']) {
		preg_match ('/([^\/]+)\./', $row['forum_image'], $icon);
		$properties['type'] = $icon[1];
		if ($layer == 'verbose')
			$properties['icon'] = $url_base .str_replace ('.png', '.svg', $row['forum_image']);
	}

	$geojson = preg_replace_callback (
		'/(-?[0-9.]+), ?(-?[0-9.]+)/',
		function ($m) {
			global $hack_positions, $altitudes;
			// Avoid points with the same position

			while (in_array ($m[1].$m[2], $hack_positions))
				$m[1] += 0.00001; // Spread 1m right
			$hack_positions[] = $m[1].$m[2];

			// Populate geojson altitudes
			//BEST repopulate (ex bug ol 8.2.0)
			//$m[] = count ($altitudes) ? array_shift($altitudes) : 0;
			unset ($m[0]);
			unset ($m[3]);

			return implode ($m, ',');
		},
		$row['geojson']
	);

	// Don't provide 2 position from the same topic
	if (!isset ($topic_ids[$row['topic_id']]))
		$features[] = [
			'type' => 'Feature',
			'id' => $row['topic_id'], // Conformité with WFS specification. Avoid multiple display of the same feature
			'geometry' => json_decode ($geojson, true),
			'properties' => $properties,
		];

	$topic_ids[$row['topic_id']] = true;
}
$db->sql_freeresult($result);

// Envoi de la page
$secondes_de_cache = 3600;
$expires = gmdate("D, d M Y H:i:s", time() + $secondes_de_cache);
header("Content-Transfer-Encoding: binary");
header("Pragma: cache");
header("Expires: $expires GMT");
header("Access-Control-Allow-Origin: *");
header("Cache-Control: max-age=$secondes_de_cache");
header("Content-Type: application/json; UTF-8");
header("Content-disposition: filename=geobb.json");
echo json_encode ([
	'type' => 'FeatureCollection',
	'comment' => count($features).' features, '.count($clusters).' clusters, '.
		round((microtime (true) - $debut) * 1000).' ms',
	'features' => array_merge ($clusters, $features),
]);
