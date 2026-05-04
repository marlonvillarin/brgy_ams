<?php
require_once 'includes/db.php';
require_once 'includes/auth.php'; // ✅ THIS IS MISSING IN YOUR CASE
include 'includes/header.php'; ?>

<div class="logout-overlay">
    <div class="logout-box">
        <h3>Are you sure you want to logout?</h3>

        <div style="margin-top:15px;display:flex;gap:10px;justify-content:center;">
            <a href="logout.php" class="btn btn-danger">Yes, Logout</a>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>