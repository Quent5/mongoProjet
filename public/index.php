<?php

require __DIR__ . '/../vendor/autoload.php';

// Code source permettant d'accéder aux données parking du Grand Nancy
$parkings = [];
$velos = [];

$db = (new MongoDB\Client('mongodb://mongo'))->selectDatabase('tdmongo');

// récupération des données parking du Grand Nancy
$dataParking = json_decode(file_get_contents('https://geoservices.grand-nancy.org/arcgis/rest/services/public/VOIRIE_Parking/MapServer/0/query?where=1%3D1&text=&objectIds=&time=&geometry=&geometryType=esriGeometryEnvelope&inSR=&spatialRel=esriSpatialRelIntersects&relationParam=&outFields=nom%2Cadresse%2Cplaces%2Ccapacite&returnGeometry=true&returnTrueCurves=false&maxAllowableOffset=&geometryPrecision=&outSR=4326&returnIdsOnly=false&returnCountOnly=false&orderByFields=&groupByFieldsForStatistics=&outStatistics=&returnZ=false&returnM=false&gdbVersion=&returnDistinctValues=false&resultOffset=&resultRecordCount=&queryByDistance=&returnExtentsOnly=false&datumTransformation=&parameterValues=&rangeValues=&f=pjson'));

// récupération des données VélOstan du Grand Nancy
$dataVelostan = json_decode(file_get_contents('https://api.jcdecaux.com/vls/v3/stations?apiKey=frifk0jbxfefqqniqez09tw4jvk37wyf823b5j1i&contract=nancy'));


// listCollections() : liste de toutes les collections de la base de données
$collections = $db->listCollections();
// on vérifie si la collection 'parkings' existe
$existsParking = false;
foreach ($collections as $collection) {
  if ($collection->getName() == 'parkings') {
    $existsParking = true;
  }
}
// si la collection n'existe pas, on la crée
if (!$existsParking) $db->createCollection('parkings');
$dbParking = $db->selectCollection('parkings');

// on vérifie si la collection 'velo' existe
$existsVelo = false;
foreach ($collections as $collection) {
  if ($collection->getName() == 'velos') {
    $existsVelo = true;
  }
}
// si la collection n'existe pas, on la crée
if (!$existsVelo) $db->createCollection('velos');
$dbVelo = $db->selectCollection('velos');



// on parcourt les données récupérées - Parking

foreach ($dataParking->features as $feature) {
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
  $dbParking->updateOne(
    ['name' => $parking['name']],
    ['$set' => $parking],
    ['upsert' => true]
  );
  $parkings[] = [];
}



foreach ($dataVelostan as $stan) {
  $velo = [
    'name' => $stan->name,
    'coordinates' => $stan->position,
    'totalStands' => $stan->totalStands->availabilities->stands,
    'availableBikes' => $stan->totalStands->availabilities->bikes,
    'capacity' => $stan->totalStands->capacity,
  ];
  $velos[] = $velo;
}

// si le vélo n'existe pas, on l'ajoute
// si le vélo existe, on le met à jour
foreach ($velos as $velo) {
  $dbVelo->updateOne(
    ['name' => $velo['name']],
    ['$set' => $velo],
    ['upsert' => true]
  );
  $velos[] = [];
}


?>

<!-- affichage des données du Grand Nancy -->
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="utf-8">
  <title>Carte des points d'intérêts du Grand Nancy</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.5.1/leaflet.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/leaflet.markercluster.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.Default.css"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.4.1/MarkerCluster.css"></script>
  <style>
    html,
    body {
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
  <?php
  // on récupère les données de la base de données MongoDB
  $myPark = $dbParking->find();
  foreach ($myPark as $parkdb) {
    $parkingDB[] = $parkdb;
  }

  $myVelo = $dbVelo->find();
  foreach ($myVelo as $bikedb) {
    $veloDB[] = $bikedb;
  }

  // on crée un tableau avec les données des parkings de MongoDB
  foreach ($parkingDB as $park) {
    $parking = [
      'name' => $park['name'],
      'address' => $park['address'],
      'description' => $park['description'],
      'geometry' => $park['geometry'],
      'places' => $park['places'],
      'capacity' => $park['capacity'],
    ];
    $parkingsDB[] = $parking;
  }

  // on crée un tableau avec les données des vélos de MongoDB
  foreach ($veloDB as $velo) {
    $velo = [
      'name' => $velo['name'],
      'coordinates' => $velo['coordinates'],
      'totalStands' => $velo['totalStands'],
      'availableBikes' => $velo['availableBikes'],
      'capacity' => $velo['capacity'],
    ];
    $velosDB[] = $velo;
  }
  ?>
  <div id="map"></div>
  <script>
    var parkingIcon = L.icon({
      iconUrl: 'parking.png',
      iconSize: [53, 65], // size of the icon
      iconAnchor: [22, 94], // point of the icon which will correspond to marker's location
      popupAnchor: [-3, -76] // point from which the popup should open relative to the iconAnchor
    });

    var veloIcon = L.icon({
      iconUrl: 'velo.png',
      iconSize: [53, 65], // size of the icon
      iconAnchor: [22, 94], // point of the icon which will correspond to marker's location
      popupAnchor: [-3, -76] // point from which the popup should open relative to the iconAnchor
    });


    var map = L.map('map').setView([48.688, 6.186], 13.6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);

    var markers = L.markerClusterGroup({
      maxClusterRadius: 0
    });
    <?php foreach ($parkingsDB as $parking) { ?>
      var marker = L.marker([<?php echo $parking['geometry']->y; ?>, <?php echo $parking['geometry']->x; ?>], {
        icon: parkingIcon
      }).bindPopup('<?php echo '<b>'.$parking['name'] . "</b><br>" . $parking['address'] . "<br>Places libres : " . $parking['places'] . "/" . $parking['capacity']; ?>');
      markers.addLayer(marker);
    <?php } ?>
    <?php foreach ($velosDB as $velo) { ?>
      var markerVelo = L.marker([<?php echo $velo['coordinates']->latitude; ?>, <?php echo $velo['coordinates']->longitude; ?>], {
        icon: veloIcon
      }).bindPopup("<?php echo '<b>'. $velo["name"] . '</b><br>' . $velo["availableBikes"] . ' vélos libres / ' . $velo["capacity"] . ' places'; ?>");
      markers.addLayer(markerVelo);
    <?php } ?>
    map.addLayer(markers);
  </script>
</body>

</html>