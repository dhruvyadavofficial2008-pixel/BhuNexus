<?php
require 'config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GIS Map</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">Interactive Land Parcel Map</h1>
    <p class="muted section">Click a marker to view parcel details, owner and acquisition stage.</p>
    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/map.js"></script>
</body>
</html>
