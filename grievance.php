<?php
require 'config.php';
require_login();

// Admin can resolve grievances here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin' && isset($_POST['grievance_id'])) {
    $gid = intval($_POST['grievance_id']);
    $status = $_POST['status'];
    $resolvedAt = $status === 'resolved' ? date('Y-m-d H:i:s') : null;
    $pdo->prepare("UPDATE grievances SET status = ?, resolved_at = ? WHERE id = ?")
        ->execute([$status, $resolvedAt, $gid]);
}

if ($_SESSION['role'] === 'admin') {
    $stmt = $pdo->query("
        SELECT g.*, p.parcel_code, u.full_name AS citizen_name
        FROM grievances g
        JOIN parcels p ON p.id = g.parcel_id
        LEFT JOIN users u ON u.id = g.raised_by
        ORDER BY g.created_at DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT g.*, p.parcel_code, u.full_name AS citizen_name
        FROM grievances g
        JOIN parcels p ON p.id = g.parcel_id
        LEFT JOIN users u ON u.id = g.raised_by
        WHERE g.raised_by = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}
$grievances = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Grievances</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">Grievances / Objections</h1>
    <table>
        <tr>
            <th>Parcel</th><th>Citizen</th><th>Subject</th><th>Description</th><th>Status</th><th>Filed On</th>
            <?php if ($_SESSION['role'] === 'admin'): ?><th>Action</th><?php endif; ?>
        </tr>
        <?php foreach ($grievances as $g): ?>
        <tr>
            <td><a href="parcel_details.php?id=<?= $g['parcel_id'] ?>"><?= htmlspecialchars($g['parcel_code']) ?></a></td>
            <td><?= htmlspecialchars($g['citizen_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($g['subject']) ?></td>
            <td><?= htmlspecialchars($g['description']) ?></td>
            <td><span class="badge badge-<?= $g['status'] ?>"><?= ucwords(str_replace('_',' ',$g['status'])) ?></span></td>
            <td><?= $g['created_at'] ?></td>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <td>
                <form method="POST" style="display:flex; gap:6px; margin:0">
                    <input type="hidden" name="grievance_id" value="<?= $g['id'] ?>">
                    <select name="status" style="margin:0; padding:6px">
                        <option value="open" <?= $g['status']==='open'?'selected':'' ?>>Open</option>
                        <option value="under_review" <?= $g['status']==='under_review'?'selected':'' ?>>Under Review</option>
                        <option value="resolved" <?= $g['status']==='resolved'?'selected':'' ?>>Resolved</option>
                    </select>
                    <button type="submit" style="padding:6px 10px">Save</button>
                </form>
            </td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($grievances)): ?>
        <tr><td colspan="7" class="muted">No grievances found.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>
