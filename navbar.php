<?php
// Expects $_SESSION to already be active (config.php included by caller)
$role = $_SESSION['role'] ?? 'citizen';
$current = basename($_SERVER['SCRIPT_NAME']);
function nav_active($file, $current) { return $file === $current ? 'active' : ''; }
?>
<div class="navbar">
    <div class="brand">🏛️ Land Acquisition System</div>
    <div class="nav-links">
        <?php if ($role === 'admin'): ?>
            <a class="<?= nav_active('admin_dashboard.php', $current) ?>" href="admin_dashboard.php">Dashboard</a>
            <a class="<?= nav_active('map.php', $current) ?>" href="map.php">GIS Map</a>
            <a class="<?= nav_active('risk_analysis.php', $current) ?>" href="risk_analysis.php">Risk Analysis</a>
            <a class="<?= nav_active('grievance.php', $current) ?>" href="grievance.php">Grievances</a>
        <?php else: ?>
            <a class="<?= nav_active('dashboard.php', $current) ?>" href="dashboard.php">My Dashboard</a>
            <a class="<?= nav_active('map.php', $current) ?>" href="map.php">GIS Map</a>
            <a class="<?= nav_active('grievance.php', $current) ?>" href="grievance.php">Grievances</a>
        <?php endif; ?>
        <a class="btn-add <?= nav_active('add_parcel.php', $current) ?>" href="add_parcel.php">➕ Add Parcel</a>
        <span class="divider">|</span>
        <span class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
        <a href="logout.php">Logout</a>
    </div>
</div>
