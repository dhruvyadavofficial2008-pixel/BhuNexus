<?php
require 'config.php';
require_login();

// ---- Helper: check if a column exists (lets this page work even before the
//      optional boundary_geojson migration has been run in phpMyAdmin) ----
function column_exists(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (bool) $stmt->fetchColumn();
}
$hasBoundaryColumn = column_exists($pdo, 'parcels', 'boundary_geojson');

$projects = $pdo->query("SELECT id, project_name FROM projects ORDER BY project_name")->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $parcelCode = trim($_POST['parcel_code'] ?? '');
    $projectId  = $_POST['project_id'] !== '' ? intval($_POST['project_id']) : null;
    $village    = trim($_POST['village'] ?? '');
    $district   = trim($_POST['district'] ?? '');
    $state      = trim($_POST['state'] ?? '');
    $areaAcres  = $_POST['area_acres'] !== '' ? (float) $_POST['area_acres'] : null;
    $marketVal  = $_POST['market_value'] !== '' ? (float) $_POST['market_value'] : null;
    $latitude   = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $longitude  = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $boundary   = trim($_POST['boundary_geojson'] ?? '');

    if ($parcelCode === '' || $village === '' || $district === '' || $state === '' || $areaAcres === null) {
        $error = "Please fill in all required fields.";
    } elseif ($latitude === null || $longitude === null) {
        $error = "Please mark your parcel's location and boundary on the map before submitting.";
    } else {
        $check = $pdo->prepare("SELECT id FROM parcels WHERE parcel_code = ?");
        $check->execute([$parcelCode]);
        if ($check->fetch()) {
            $error = "A parcel with this code already exists. Please use a different code.";
        } else {
            if ($hasBoundaryColumn) {
                $stmt = $pdo->prepare("
                    INSERT INTO parcels (parcel_code, owner_id, project_id, village, district, state, area_acres, latitude, longitude, boundary_geojson, stage, market_value)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'identify', ?)
                ");
                $stmt->execute([$parcelCode, $_SESSION['user_id'], $projectId, $village, $district, $state, $areaAcres, $latitude, $longitude, $boundary ?: null, $marketVal]);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO parcels (parcel_code, owner_id, project_id, village, district, state, area_acres, latitude, longitude, stage, market_value)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'identify', ?)
                ");
                $stmt->execute([$parcelCode, $_SESSION['user_id'], $projectId, $village, $district, $state, $areaAcres, $latitude, $longitude, $marketVal]);
            }
            $newId = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO parcel_timeline (parcel_id, stage, remarks, updated_by) VALUES (?, 'identify', ?, ?)")
                ->execute([$newId, 'Parcel submitted via self-service portal', $_SESSION['user_id']]);

            header("Location: parcel_details.php?id=" . $newId . "&added=1");
            exit;
        }
    }
}

// Suggest the next parcel code (editable by the user)
$maxId = (int) $pdo->query("SELECT COALESCE(MAX(id),0) FROM parcels")->fetchColumn();
$suggestedCode = 'PCL-' . str_pad((string) ($maxId + 1), 3, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Parcel</title>
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">➕ Add a Land Parcel</h1>
    <p class="subtle-note">Draw your parcel's boundary directly on the map — the area (in acres) and coordinates are filled in automatically. You can still adjust the area manually before submitting.</p>

    <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if (!$hasBoundaryColumn): ?>
    <p class="muted section" style="background:#fef9f0; border:1px solid #f3e0b8; padding:10px 14px; border-radius:8px;">
        Note: boundary shapes will not be saved yet because the <code>boundary_geojson</code> column hasn't been added to your database.
        The parcel's location, area and details will still save normally. See <code>migration.sql</code> to enable saving the drawn shape.
    </p>
    <?php endif; ?>

    <div class="grid" style="grid-template-columns: 1.4fr 1fr; gap: 22px;">
        <div class="card">
            <div class="map-instructions">
                🗺️ Use the polygon/rectangle tool on the left of the map to draw your parcel's boundary. Search a place below to jump to your area first.
            </div>
            <div style="display:flex; gap:10px; margin-bottom:12px;">
                <input type="text" id="place-search" placeholder="Search village, district or state..." style="margin-bottom:0;">
                <button type="button" id="place-search-btn" style="white-space:nowrap;">Locate</button>
            </div>
            <div id="picker-map"></div>
            <div class="map-readout">
                <div class="ro-item"><span class="lbl">Latitude</span><b id="ro-lat">—</b></div>
                <div class="ro-item"><span class="lbl">Longitude</span><b id="ro-lng">—</b></div>
                <div class="ro-item"><span class="lbl">Computed Area</span><b id="ro-area">—</b></div>
            </div>
        </div>

        <div class="card">
            <h3 style="margin-bottom:14px">Parcel Details</h3>
            <form method="POST" id="parcel-form">
                <label>Parcel Code</label>
                <input type="text" name="parcel_code" value="<?= htmlspecialchars($_POST['parcel_code'] ?? $suggestedCode) ?>" required>

                <label>Project (optional)</label>
                <select name="project_id">
                    <option value="">— Not linked to a project —</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= (isset($_POST['project_id']) && $_POST['project_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['project_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <label>Village</label>
                <input type="text" name="village" value="<?= htmlspecialchars($_POST['village'] ?? '') ?>" required>

                <label>District</label>
                <input type="text" name="district" value="<?= htmlspecialchars($_POST['district'] ?? '') ?>" required>

                <label>State</label>
                <input type="text" name="state" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" required>

                <label>Area (acres)</label>
                <input type="number" step="0.01" min="0" name="area_acres" id="area_acres" value="<?= htmlspecialchars($_POST['area_acres'] ?? '') ?>" required>

                <label>Market Value (₹, optional)</label>
                <input type="number" step="0.01" min="0" name="market_value" value="<?= htmlspecialchars($_POST['market_value'] ?? '') ?>">

                <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                <input type="hidden" name="boundary_geojson" id="boundary_geojson" value="">

                <button type="submit" style="width:100%; margin-top:6px;">Submit Parcel</button>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script src="assets/js/add_parcel.js"></script>
</body>
</html>
