<?php
header('content-type:application/json');
header('Access-Control-Allow-Origin: *');
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
echo file_get_contents ('pl.geojson');