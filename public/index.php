<?php

require __DIR__ . '/../vendor/autoload.php';

// Code source permettant d'accéder aux données parking du Grand Nancy
$parkings = [];

$db = (new MongoDB\Client('mongodb://mongo'))->selectDatabase('tdmongo');
$data = json_decode(file_get_contents('https://geoservices.grand-nancy.org/arcgis/rest/services/public/VOIRIE_Parking/MapServer/0/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=nom%2Cadresse%2Cplaces%2Ccapacite&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=4326&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=pjson'));



// listCollections() : liste de toutes les collections de la base de données
$collections = $db->listCollections();
// on vérifie si la collection 'parkings' existe
$exists = false;
foreach ($collections as $collection) {
  if ($collection->getName() == 'parkings') {
    $exists = true;
  }
}
// si la collection n'existe pas, on la crée
if (!$exists) $db->createCollection('parkings');
$db = $db->selectCollection('parkings');



// on parcourt les données récupérées

foreach ($data->features as $feature) {
  $parking = [
    'name' => $feature->attributes->NOM,
    'address' => $feature->attributes->ADRESSE,
    'description' => '',
    'category' => [
      'name' => 'parking',
      'icon' => 'fa-square-parking',
      'color' => 'blue'
    ],
    'geometry' => $feature->geometry,
    'places' => $feature->attributes->PLACES,
    'capacity' => $feature->attributes->CAPACITE,
  ];
  $parkings[] = $parking;
}

// si le parking n'existe pas, on l'ajoute
// si le parking existe, on le met à jour
foreach ($parkings as $parking) {
  $db->updateOne(
    ['name' => $parking['name']],
    ['$set' => $parking],
    ['upsert' => true]
  );
}


$test = $db->find();

foreach ($test as $t) {
  $parking[] = $t;

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

var parkingIcon = L.icon({
    iconUrl: 'parking.png',
    iconSize:     [53, 65], // size of the icon
    iconAnchor:   [22, 94], // point of the icon which will correspond to marker's location
    popupAnchor:  [-3, -76] // point from which the popup should open relative to the iconAnchor
});

    var map = L.map('map').setView([48.688, 6.186], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);

    var markers = L.markerClusterGroup();
    <?php foreach ($parkings as $parking) { ?>
      var marker = L.marker([<?php echo $parking['geometry']->y; ?>, <?php echo $parking['geometry']->x; ?>], {icon: parkingIcon}).bindPopup('<?php echo $parking['name']." - ".$parking['address']." - Places ".$parking['places']."/".$t['capacity']; ?>');
      markers.addLayer(marker);
    <?php } ?>
    map.addLayer(markers);
  </script>
</body>
</html>