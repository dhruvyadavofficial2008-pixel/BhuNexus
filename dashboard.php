<?php
require 'config.php';
require_login();

$stmt = $pdo->prepare("
    SELECT p.*, pr.project_name,
        (SELECT COUNT(*) FROM documents d WHERE d.parcel_id = p.id) AS doc_count,
        (SELECT payment_status FROM compensation c WHERE c.parcel_id = p.id LIMIT 1) AS payment_status
    FROM parcels p
    LEFT JOIN projects pr ON pr.id = p.project_id
    WHERE p.owner_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$parcels = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grievanceStmt = $pdo->prepare("SELECT COUNT(*) FROM grievances WHERE raised_by = ? AND status != 'resolved'");
$grievanceStmt->execute([$_SESSION['user_id']]);
$openGrievances = $grievanceStmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Dashboard</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?></h1>

    <div class="grid grid-4 section">
        <div class="card stat-card">
            <div class="stat-value"><?= count($parcels) ?></div>
            <div class="stat-label">My Land Parcels</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value"><?= $openGrievances ?></div>
            <div class="stat-label">Open Grievances</div>
        </div>
        <div class="card stat-card" style="display:flex; align-items:center; justify-content:center;">
            <a href="add_parcel.php" class="btn btn-accent" style="width:100%">➕ Add a Parcel</a>
        </div>
        <div class="card stat-card" style="display:flex; align-items:center; justify-content:center;">
            <a href="grievance.php" class="btn" style="width:100%">File a Grievance</a>
        </div>
    </div>

    <div class="section">
        <h2 class="page-title" style="font-size:1.2rem">My Parcels & Acquisition Status</h2>
        <table>
            <tr>
                <th>Parcel Code</th><th>Project</th><th>Village/District</th>
                <th>Area (acres)</th><th>Stage</th><th>Compensation</th><th>Documents</th><th></th>
            </tr>
            <?php foreach ($parcels as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['parcel_code']) ?></td>
                <td><?= htmlspecialchars($p['project_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($p['village'] . ', ' . $p['district']) ?></td>
                <td><?= htmlspecialchars($p['area_acres']) ?></td>
                <td><span class="badge badge-<?= $p['stage'] ?>"><?= ucfirst($p['stage']) ?></span></td>
                <td><span class="badge badge-<?= $p['payment_status'] ?? 'pending' ?>"><?= ucfirst($p['payment_status'] ?? 'pending') ?></span></td>
                <td><?= $p['doc_count'] ?> file(s)</td>
                <td><a href="parcel_details.php?id=<?= $p['id'] ?>" class="btn btn-outline">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($parcels)): ?>
            <tr><td colspan="8" class="muted">No parcels linked to your account yet.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>
