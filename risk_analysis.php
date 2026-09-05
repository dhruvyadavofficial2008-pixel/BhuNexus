<?php
require 'config.php';
require_admin();

$projects = $pdo->query("SELECT * FROM projects ORDER BY project_name")->fetchAll(PDO::FETCH_ASSOC);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['project_id'])) {
    $projectId = intval($_POST['project_id']);

    // ---- Gather real inputs from the database ----
    $disputes = $pdo->prepare("
        SELECT COUNT(*) FROM grievances g
        JOIN parcels p ON p.id = g.parcel_id
        WHERE p.project_id = ? AND g.status != 'resolved'
    ");
    $disputes->execute([$projectId]);
    $disputesCount = (int) $disputes->fetchColumn();

    $pendingComp = $pdo->prepare("
        SELECT COUNT(*) FROM compensation c
        JOIN parcels p ON p.id = c.parcel_id
        WHERE p.project_id = ? AND c.payment_status != 'paid'
    ");
    $pendingComp->execute([$projectId]);
    $pendingCompCount = (int) $pendingComp->fetchColumn();

    $avgDelay = $pdo->prepare("
        SELECT AVG(c.delay_days) FROM compensation c
        JOIN parcels p ON p.id = c.parcel_id
        WHERE p.project_id = ?
    ");
    $avgDelay->execute([$projectId]);
    $avgDelayDays = round((float) $avgDelay->fetchColumn(), 1);

    $pendingDocs = $pdo->prepare("
        SELECT COUNT(*) FROM documents d
        JOIN parcels p ON p.id = d.parcel_id
        WHERE p.project_id = ? AND d.status = 'pending'
    ");
    $pendingDocs->execute([$projectId]);
    $pendingDocsCount = (int) $pendingDocs->fetchColumn();

    // ---- Transparent scoring model (documented formula, not a black box) ----
    // Risk Score = Disputes x 5 + Pending Compensation x 2 + Delay Days x 0.5 + Pending Docs x 1
    $riskScore = ($disputesCount * 5) + ($pendingCompCount * 2) + ($avgDelayDays * 0.5) + ($pendingDocsCount * 1);

    if ($riskScore >= 25) {
        $label = 'HIGH';
        $recommendation = 'HIGH RISK — prioritize dispute resolution and compensation verification before proceeding further.';
    } elseif ($riskScore >= 12) {
        $label = 'MEDIUM';
        $recommendation = 'MEDIUM RISK — monitor closely; clear pending documents and follow up on delayed payments.';
    } else {
        $label = 'LOW';
        $recommendation = 'LOW RISK — project on track; continue standard monitoring.';
    }

    $pdo->prepare("
        INSERT INTO risk_logs (project_id, disputes_count, pending_compensation_count, avg_delay_days, pending_documents_count, risk_score, risk_label, recommendation)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([$projectId, $disputesCount, $pendingCompCount, $avgDelayDays, $pendingDocsCount, $riskScore, $label, $recommendation]);

    $result = compact('disputesCount','pendingCompCount','avgDelayDays','pendingDocsCount','riskScore','label','recommendation','projectId');
}

$history = $pdo->query("
    SELECT r.*, p.project_name FROM risk_logs r
    JOIN projects p ON p.id = r.project_id
    ORDER BY r.generated_at DESC LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>AI-Assisted Risk Analysis</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <h1 class="page-title">AI-Assisted Acquisition Risk Analysis</h1>
    <p class="muted section">
        This is a transparent, rule-based decision-support score — not a trained ML model.
        Formula: <b>Risk Score = Disputes×5 + Pending Compensation×2 + Delay Days×0.5 + Pending Documents×1</b>
    </p>

    <div class="card section">
        <form method="POST" style="display:flex; gap:12px; align-items:flex-end">
            <div style="flex:1">
                <label>Select Project</label>
                <select name="project_id" required>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['project_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" style="margin-bottom:14px">Run Risk Analysis</button>
        </form>
    </div>

    <?php if ($result): ?>
    <div class="card section">
        <div class="flex-between">
            <h3>Result</h3>
            <span class="badge badge-<?= $result['label'] ?>" style="font-size:1rem"><?= $result['label'] ?> RISK</span>
        </div>
        <div class="grid grid-4" style="margin-top:14px">
            <div class="stat-card"><div class="stat-value"><?= $result['disputesCount'] ?></div><div class="stat-label">Open Disputes</div></div>
            <div class="stat-card"><div class="stat-value"><?= $result['pendingCompCount'] ?></div><div class="stat-label">Pending Compensation</div></div>
            <div class="stat-card"><div class="stat-value"><?= $result['avgDelayDays'] ?></div><div class="stat-label">Avg Delay (days)</div></div>
            <div class="stat-card"><div class="stat-value"><?= $result['pendingDocsCount'] ?></div><div class="stat-label">Pending Documents</div></div>
        </div>
        <p style="margin-top:16px; font-size:1.1rem"><b>Score: <?= $result['riskScore'] ?></b></p>
        <p style="margin-top:6px" class="card" style="background:#fef9f0"><b>Recommendation:</b> <?= htmlspecialchars($result['recommendation']) ?></p>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-bottom:10px">Recent Risk Analysis History</h3>
        <table>
            <tr><th>Project</th><th>Score</th><th>Label</th><th>Recommendation</th><th>When</th></tr>
            <?php foreach ($history as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['project_name']) ?></td>
                <td><?= $h['risk_score'] ?></td>
                <td><span class="badge badge-<?= $h['risk_label'] ?>"><?= $h['risk_label'] ?></span></td>
                <td><?= htmlspecialchars($h['recommendation']) ?></td>
                <td><?= $h['generated_at'] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
