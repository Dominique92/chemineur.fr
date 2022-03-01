<!DOCTYPE html>
<!--
© Dominique Cavailhez 2019
https://github.com/Dominique92/MyOl
Based on https://openlayers.org
-->
<?php
$entry_url = $_SERVER['SCRIPT_NAME'];
require_once ('functions.php');
?>
<html>
<head>
	<link rel="manifest" href="<?=$script_path?>manifest.json.php">

	<title><?=isset($title)?$title:'My GPS'?></title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
	<link rel="icon" type="png" href="<?=$script_path?>favicon.png" />

	<!-- Polyfill iOS : Amélioration du pseudo full screen pour les cartes pour d'anciennes versions d'iOS/Safari -->
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

	<!-- Openlayers -->
	<link href="<?=$script_path?>../ol/ol.css" type="text/css" rel="stylesheet">
	<script src="<?=$script_path?>../ol/ol.js"></script>

	<!-- Recherche par nom -->
	<link href="<?=$script_path?>../geocoder/ol-geocoder.min.css" type="text/css" rel="stylesheet">
	<script src="<?=$script_path?>../geocoder/ol-geocoder.js"></script>

	<!-- My Openlayers -->
	<link href="<?=$script_path?>../myol.css" type="text/css" rel="stylesheet">
	<script src="<?=$script_path?>../myol.js"></script>

	<!-- This app -->
	<link href="<?=$script_path?>index.css" type="text/css" rel="stylesheet">
	<script src="<?=$script_path?>index.js" defer="defer"></script>
	<script>
		var service_worker = '<?=$script_path?>service-worker.js.php',
			scope = '<?=$scope_path?>',
			scriptName = 'index.php',
			mapKeys = <?=json_encode(@$mapKeys)?>;
	</script>
</head>

<body>
	<?php
	// List gpx files on the url directory
	$gpx_files = glob ('*.gpx');
	if (count ($gpx_files) && !isset ($_GET['gpx'])) { ?>
		<div id="liste">
			<p>Cliquez sur le nom de la trace pour l'afficher :</p>
			<ul>
			<?php foreach ($gpx_files AS $gpx) { ?>
				<li>
					<a title="Cliquer pour afficher la trace"
						onclick="addLayer('<?=dirname($_SERVER['SCRIPT_NAME']).'/'.$gpx?>')">
						<?=ucfirst(pathinfo($gpx,PATHINFO_FILENAME))?>
					</a>
				</li>
		<?php } ?>
			</ul>
			<p>Puis sur la cible pour afficher votre position.</p>
			<p>Fermer : <a onclick="document.getElementById('liste').style.display='none'" title="Replier">&#9651;</a></p>
		</div>
	<?php } ?>

	<div id="map"></div>

	<?php if(file_exists ('footer.php'))
		include 'footer.php';
	?>
</body>
</html>
