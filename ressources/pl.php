<?php
error_reporting(E_ALL);
ini_set('display_errors','on');
ini_set('display_startup_errors', 'on');

// Icon https://ice.artifica.fr/www/clients/pere-lachaise/icons/square/293.svg

$entete = 'https://pere-lachaise.plan-interactif.com/datas/map/list'.
	'?key=ghr8aoy51m4qktndlix3zcb67e0w2sjv&lang=fr&parent=';
$ur = json_decode (file_get_contents ($entete .'834972'));
$out = [];

foreach ($ur->children as $vv) {
	$fc = json_decode (file_get_contents ($entete  .$vv->tree->id));

	foreach ($fc->children as $value)
		$out[]= "{
			\"id\": {$value->id},
			\"type\": \"Feature\",
			\"geometry\": {
				\"type\": \"Point\",
				\"coordinates\": [{$value->lng}, {$value->lat}]
			},
			\"properties\": {
				\"name\": \"{$value->title}\",
				\"id\": {$value->id},
				\"parent\": {$value->tree->parent}
			}
		}";
}

echo "{\"type\": \"FeatureCollection\",\t\"features\": [";
echo implode (',', $out);
echo "]\n}";
