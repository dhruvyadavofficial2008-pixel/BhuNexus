<?php
require 'config.php';
require_login();

$id = intval($_POST['parcel_id'] ?? $_GET['id'] ?? 0);

// Admin can advance a parcel's stage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin' && isset($_POST['new_stage'])) {
    $stage = $_POST['new_stage'];
    $remarks = trim($_POST['remarks'] ?? '');
    $pdo->prepare("UPDATE parcels SET stage = ? WHERE id = ?")->execute([$stage, $id]);
    $pdo->prepare("INSERT INTO parcel_timeline (parcel_id, stage, remarks, updated_by) VALUES (?, ?, ?, ?)")
        ->execute([$id, $stage, $remarks ?: 'Stage updated', $_SESSION['user_id']]);
}

// Document upload (demo: just logs metadata, doesn't require real file storage)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['doc_type'])) {
    $fname = $_FILES['doc_file']['name'] ?? 'uploaded_file.pdf';
    $pdo->prepare("INSERT INTO documents (parcel_id, uploaded_by, file_name, file_path, doc_type, status) VALUES (?, ?, ?, ?, ?, 'pending')")
        ->execute([$id, $_SESSION['user_id'], $fname, 'uploads/' . $fname, $_POST['doc_type']]);
}

// Grievance quick-file from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'])) {
    $pdo->prepare("INSERT INTO grievances (parcel_id, raised_by, subject, description, status) VALUES (?, ?, ?, ?, 'open')")
        ->execute([$id, $_SESSION['user_id'], $_POST['subject'], $_POST['description']]);
}

// Admin verifies or rejects an uploaded document
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin' && isset($_POST['document_id'], $_POST['doc_status'])) {
    $docId = intval($_POST['document_id']);
    $docStatus = $_POST['doc_status']; // 'verified' or 'rejected'
    if (in_array($docStatus, ['verified', 'rejected'], true)) {
        $pdo->prepare("UPDATE documents SET status = ? WHERE id = ? AND parcel_id = ?")
            ->execute([$docStatus, $docId, $id]);
    }
}

$stmt = $pdo->prepare("
    SELECT p.*, pr.project_name, u.full_name AS owner_name
    FROM parcels p
    LEFT JOIN projects pr ON pr.id = p.project_id
    LEFT JOIN users u ON u.id = p.owner_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$parcel = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parcel) { die("Parcel not found."); }

// Access control: citizens can only view their own parcel
if ($_SESSION['role'] !== 'admin' && $parcel['owner_id'] != $_SESSION['user_id']) {
    die("You do not have access to this parcel.");
}

$timelineStmt = $pdo->prepare("SELECT * FROM parcel_timeline WHERE parcel_id = ? ORDER BY updated_at ASC");
$timelineStmt->execute([$id]);
$timeline = $timelineStmt->fetchAll(PDO::FETCH_ASSOC);

$docStmt = $pdo->prepare("SELECT * FROM documents WHERE parcel_id = ? ORDER BY uploaded_at DESC");
$docStmt->execute([$id]);
$documents = $docStmt->fetchAll(PDO::FETCH_ASSOC);

$compStmt = $pdo->prepare("SELECT * FROM compensation WHERE parcel_id = ?");
$compStmt->execute([$id]);
$compensation = $compStmt->fetch(PDO::FETCH_ASSOC);

$grievStmt = $pdo->prepare("SELECT * FROM grievances WHERE parcel_id = ? ORDER BY created_at DESC");
$grievStmt->execute([$id]);
$grievances = $grievStmt->fetchAll(PDO::FETCH_ASSOC);

$stages = ['identify','survey','notify','verify','acquire','compensate','monitor'];
$completedStages = array_column($timeline, 'stage');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parcel <?= htmlspecialchars($parcel['parcel_code']) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'navbar.php'; ?>
<div class="container">
    <?php if (isset($_GET['added'])): ?>
    <p class="success-msg">✅ Your parcel was submitted successfully and is now in the "Identify" stage of acquisition.</p>
    <?php endif; ?>
    <div class="flex-between section">
        <h1 class="page-title">Parcel <?= htmlspecialchars($parcel['parcel_code']) ?></h1>
        <span class="badge badge-<?= $parcel['stage'] ?>" style="font-size:0.9rem"><?= ucfirst($parcel['stage']) ?></span>
    </div>

    <div class="grid grid-2 section">
        <div class="card">
            <h3 style="margin-bottom:10px">Parcel Information</h3>
            <p><b>Owner:</b> <?= htmlspecialchars($parcel['owner_name'] ?? '-') ?></p>
            <p><b>Project:</b> <?= htmlspecialchars($parcel['project_name'] ?? '-') ?></p>
            <p><b>Location:</b> <?= htmlspecialchars($parcel['village'] . ', ' . $parcel['district'] . ', ' . $parcel['state']) ?></p>
            <p><b>Area:</b> <?= htmlspecialchars($parcel['area_acres']) ?> acres</p>
            <p><b>Market Value:</b> ₹<?= number_format($parcel['market_value']) ?></p>
            <p><b>Coordinates:</b> <?= $parcel['latitude'] ?>, <?= $parcel['longitude'] ?></p>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px">Compensation</h3>
            <?php if ($compensation): ?>
                <p><b>Amount Due:</b> ₹<?= number_format($compensation['amount_due']) ?></p>
                <p><b>Amount Paid:</b> ₹<?= number_format($compensation['amount_paid']) ?></p>
                <p><b>Status:</b> <span class="badge badge-<?= $compensation['payment_status'] ?>"><?= ucfirst($compensation['payment_status']) ?></span></p>
                <p><b>Delay:</b> <?= $compensation['delay_days'] ?> days</p>
            <?php else: ?>
                <p class="muted">No compensation record yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-2 section">
        <div class="card">
            <h3 style="margin-bottom:10px">Acquisition Timeline</h3>
            <ul class="timeline">
                <?php foreach ($stages as $s):
                    $entry = null;
                    foreach ($timeline as $t) { if ($t['stage'] === $s) $entry = $t; }
                    $done = $entry !== null;
                ?>
                <li class="<?= $done ? 'done' : '' ?>">
                    <div class="stage-name"><?= $s ?></div>
                    <?php if ($done): ?>
                        <div class="muted"><?= htmlspecialchars($entry['remarks']) ?></div>
                        <div class="stage-time"><?= $entry['updated_at'] ?></div>
                    <?php else: ?>
                        <div class="muted">Not yet reached</div>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($_SESSION['role'] === 'admin'): ?>
            <form method="POST" style="margin-top:14px; border-top:1px solid var(--border); padding-top:14px">
                <input type="hidden" name="parcel_id" value="<?= $id ?>">
                <label>Advance Stage</label>
                <select name="new_stage">
                    <?php foreach ($stages as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Remarks</label>
                <input type="text" name="remarks" placeholder="e.g. Survey completed on site">
                <button type="submit">Update Stage</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-bottom:10px">Documents</h3>
            <table>
                <tr>
                    <th>File</th><th>Type</th><th>Status</th>
                    <?php if ($_SESSION['role'] === 'admin'): ?><th>Action</th><?php endif; ?>
                </tr>
                <?php foreach ($documents as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['file_name']) ?></td>
                    <td><?= htmlspecialchars($d['doc_type']) ?></td>
                    <td><span class="badge badge-<?= $d['status'] === 'verified' ? 'paid' : ($d['status'] === 'rejected' ? 'open' : 'pending') ?>"><?= ucfirst($d['status']) ?></span></td>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <td>
                        <?php if ($d['status'] === 'pending'): ?>
                        <form method="POST" style="display:flex; gap:6px; margin:0">
                            <input type="hidden" name="parcel_id" value="<?= $id ?>">
                            <input type="hidden" name="document_id" value="<?= $d['id'] ?>">
                            <button type="submit" name="doc_status" value="verified" style="padding:6px 10px">Verify</button>
                            <button type="submit" name="doc_status" value="rejected" class="btn-danger" style="padding:6px 10px; background:var(--danger)">Reject</button>
                        </form>
                        <?php else: ?>
                        <span class="muted">Reviewed</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($documents)): ?>
                <tr><td colspan="<?= $_SESSION['role'] === 'admin' ? 4 : 3 ?>" class="muted">No documents uploaded yet.</td></tr>
                <?php endif; ?>
            </table>

            <form method="POST" enctype="multipart/form-data" style="margin-top:14px; border-top:1px solid var(--border); padding-top:14px">
                <input type="hidden" name="parcel_id" value="<?= $id ?>">
                <label>Document Type</label>
                <input type="text" name="doc_type" placeholder="e.g. Title Deed" required>
                <label>File</label>
                <input type="file" name="doc_file">
                <button type="submit">Upload Document</button>
            </form>
        </div>
    </div>

    <div class="card section">
        <h3 style="margin-bottom:10px">Grievances / Objections for this Parcel</h3>
        <table>
            <tr><th>Subject</th><th>Description</th><th>Status</th><th>Filed On</th></tr>
            <?php foreach ($grievances as $g): ?>
            <tr>
                <td><?= htmlspecialchars($g['subject']) ?></td>
                <td><?= htmlspecialchars($g['description']) ?></td>
                <td><span class="badge badge-<?= $g['status'] ?>"><?= ucwords(str_replace('_',' ',$g['status'])) ?></span></td>
                <td><?= $g['created_at'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($grievances)): ?>
            <tr><td colspan="4" class="muted">No grievances filed for this parcel.</td></tr>
            <?php endif; ?>
        </table>

        <?php if ($_SESSION['role'] !== 'admin'): ?>
        <form method="POST" style="margin-top:14px; border-top:1px solid var(--border); padding-top:14px">
            <input type="hidden" name="parcel_id" value="<?= $id ?>">
            <label>Subject</label>
            <input type="text" name="subject" required>
            <label>Description</label>
            <textarea name="description" rows="3" required></textarea>
            <button type="submit">File Grievance</button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
