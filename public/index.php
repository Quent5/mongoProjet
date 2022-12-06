<?php

require __DIR__ . '/../vendor/autoload.php';

// Code source permettant d'accéder aux données parking du Grand Nancy
$pis = [];

$db = (new MongoDB\Client('mongodb://mongo'))->selectDatabase('tdmongo');
$data = json_decode(file_get_contents('https://geoservices.grand-nancy.org/arcgis/rest/services/public/VOIRIE_Parking/MapServer/0/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=nom%2Cadresse%2Cplaces%2Ccapacite&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=4326&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=pjson'));
// $db->createCollection('pis');
$db = $db->selectCollection('pis');

// foreach ($data->features as $feature) {
//   $pi = [
//     'name' => $feature->attributes->NOM,
//     'address' => $feature->attributes->ADRESSE,
//     'description' => '',
//     'category' => [
//       'name' => 'parking',
//       'icon' => 'fa-square-parking',
//       'color' => 'blue'
//     ],
//     'geometry' => $feature->geometry,
//     'places' => $feature->attributes->PLACES,
//     'capacity' => $feature->attributes->CAPACITE,
//   ];
//   $pis[] = $pi;
// }
// if (count($pis) > 0) {
//   $res = $db->insertMany($pis);
// }

$test = $db->find();

foreach ($test as $t) {
  $pis[] = $t;

}



?>

<!-- affichage des données parking du Grand Nancy -->
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
  <title>Carte des parkings du Grand Nancy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/leaflet.markercluster.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.Default.css"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.css"></script>
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    #map {
      width: 100%;
      height: 100%;
    }
  </style>
</head>
<body>
  <div id="map"></div>
  <script>
    var map = L.map('map').setView([48.688, 6.186], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);

    var markers = L.markerClusterGroup();
    <?php foreach ($pis as $pi) { ?>
      var marker = L.marker([<?php echo $pi['geometry']->y; ?>, <?php echo $pi['geometry']->x; ?>]).bindPopup('<?php echo $pi['name']." - ".$pi['address']." - Places ".$pi['places']."/".$t['capacity']; ?>');
      markers.addLayer(marker);
    <?php } ?>
    map.addLayer(markers);
  </script>
</body>
</html>