<?php
// Expects $_SESSION to already be active (config.php included by caller)
$role = $_SESSION['role'] ?? 'citizen';
?>
<div class="navbar">
    <div class="brand">🏛️ Land Acquisition System</div>
    <div class="nav-links">
        <?php if ($role === 'admin'): ?>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="map.php">GIS Map</a>
            <a href="risk_analysis.php">Risk Analysis</a>
            <a href="grievance.php">Grievances</a>
        <?php else: ?>
            <a href="dashboard.php">My Dashboard</a>
            <a href="map.php">GIS Map</a>
            <a href="grievance.php">Grievances</a>
        <?php endif; ?>
        <span class="muted" style="color:#e5e7eb">| <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></span>
        <a href="logout.php">Logout</a>
    </div>
</div>
