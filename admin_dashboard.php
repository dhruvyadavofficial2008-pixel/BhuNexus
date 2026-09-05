<?php
require 'config.php';
require_admin();

$totalParcels = $pdo->query("SELECT COUNT(*) FROM parcels")->fetchColumn();
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$openGrievances = $pdo->query("SELECT COUNT(*) FROM grievances WHERE status != 'resolved'")->fetchColumn();
$pendingComp = $pdo->query("SELECT COUNT(*) FROM compensation WHERE payment_status != 'paid'")->fetchColumn();

$projects = $pdo->query("SELECT * FROM projects ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$recentParcels = $pdo->query("
    SELECT p.*, pr.project_name, u.full_name AS owner_name
    FROM parcels p
    LEFT JOIN projects pr ON pr.id = p.project_id
    LEFT JOIN users u ON u.id = p.owner_id
    ORDER BY p.id DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">Admin Dashboard</h1>

    <div class="grid grid-4 section">
        <div class="card stat-card"><div class="stat-value"><?= $totalProjects ?></div><div class="stat-label">Active Projects</div></div>
        <div class="card stat-card"><div class="stat-value"><?= $totalParcels ?></div><div class="stat-label">Land Parcels</div></div>
        <div class="card stat-card"><div class="stat-value"><?= $openGrievances ?></div><div class="stat-label">Open Disputes</div></div>
        <div class="card stat-card"><div class="stat-value"><?= $pendingComp ?></div><div class="stat-label">Pending Compensation</div></div>
    </div>

    <div class="grid grid-3 section">
        <div class="card"><h3 style="margin-bottom:10px; font-size:0.95rem">Parcels by Stage</h3><canvas id="stageChart"></canvas></div>
        <div class="card"><h3 style="margin-bottom:10px; font-size:0.95rem">Compensation Status</h3><canvas id="compChart"></canvas></div>
        <div class="card"><h3 style="margin-bottom:10px; font-size:0.95rem">Grievance Status</h3><canvas id="grievChart"></canvas></div>
    </div>

    <div class="grid grid-2 section">
        <div class="card">
            <div class="flex-between"><h3>Projects</h3><span class="muted"><?= count($projects) ?> total</span></div>
            <table style="margin-top:10px">
                <tr><th>Name</th><th>Department</th><th>Status</th></tr>
                <?php foreach ($projects as $pr): ?>
                <tr>
                    <td><?= htmlspecialchars($pr['project_name']) ?></td>
                    <td><?= htmlspecialchars($pr['department']) ?></td>
                    <td><span class="badge badge-<?= $pr['status']==='completed'?'paid':($pr['status']==='ongoing'?'partial':'pending') ?>"><?= ucfirst($pr['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <div class="card">
            <div class="flex-between"><h3>Recent Parcels</h3><a href="map.php" class="btn btn-outline">Open Map</a></div>
            <table style="margin-top:10px">
                <tr><th>Code</th><th>Owner</th><th>Stage</th><th></th></tr>
                <?php foreach ($recentParcels as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['parcel_code']) ?></td>
                    <td><?= htmlspecialchars($p['owner_name'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $p['stage'] ?>"><?= ucfirst($p['stage']) ?></span></td>
                    <td><a href="parcel_details.php?id=<?= $p['id'] ?>" class="btn btn-outline">View</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/dashboard-charts.js"></script>
</body>
</html>
