<?php
$servers = [
	'ecmaps.de' => 'http://ec1.cdn.ecmaps.de/WmsGateway.ashx.jpg',
	'minambiente.it' => 'http://wms.pcn.minambiente.it/ogc',
];

ini_set('display_errors','on');

set_error_handler(
    function ($severity, $message) {
		$messages = explode ('HTTP/1.1', $message);
		header ('HTTP/1.1'.$messages[1]);
		echo $messages[1];
		exit;
    }
);

$type = @$_GET['type'];
if ($type)
	unset ($_GET['type']);

$s = @$_GET['s'];
if ($s)
	unset ($_GET['s']);
$url = @$servers[$s];

if ($url && $type) {
	$img = file_get_contents ($url.'?'.http_build_query ($_GET));
	if ($img) {
		header('Content-Type: image/'.$type);
		header('Cache-Control: max-age='.(31*24*3600));
		echo $img;
	} else {
		header('HTTP/1.1 404 Not Found');
		exit;
	}
} else {
	echo"<pre style='background:white;color:black;font-size:16px'>server = ".var_export($s,true).'</pre>';
	echo"<pre style='background:white;color:black;font-size:16px'>url = ".var_export($url,true).'</pre>';
	echo"<pre style='background:white;color:black;font-size:16px'>type = ".var_export($type,true).'</pre>';
	echo"<pre style='background:white;color:black;font-size:16px'>args = ".var_export($_GET,true).'</pre>';
	echo"<pre style='background:white;color:black;font-size:16px'>img = ".var_export($img,true).'</pre>';
}
