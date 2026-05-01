<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
$user = currentUser();
$role = $user['role'];
$flash = getFlash();

// Set nav items per role
$navItems = [];
if ($role === 'admin') {
    $navItems = [
        'Dashboard' => [
            ['url' => '../admin/dashboard.php', 'icon' => '📊', 'label' => 'Dashboard'],
        ],
        'Students' => [
            ['url' => '../admin/applications.php', 'icon' => '📋', 'label' => 'Applications'],
            ['url' => '../admin/allocations.php', 'icon' => '🏠', 'label' => 'Room Allocations'],
            ['url' => '../admin/students.php', 'icon' => '👥', 'label' => 'All Students'],
        ],
        'Finance' => [
            ['url' => '../admin/payments.php', 'icon' => '💰', 'label' => 'Payments'],
            ['url' => '../admin/invoices.php', 'icon' => '🧾', 'label' => 'Invoices'],
        ],
        'Tools' => [
            ['url' => '../admin/inquiries.php', 'icon' => '📬', 'label' => 'Inquiries'],
            ['url' => '../admin/reports.php', 'icon' => '📈', 'label' => 'Reports'],
            ['url' => '../admin/rooms.php', 'icon' => '🏨', 'label' => 'Rooms & Halls'],
        ]
    ];
} elseif ($role === 'operator') {
    $navItems = [
        'Main' => [
            ['url' => '../operator/dashboard.php', 'icon' => '📊', 'label' => 'Dashboard'],
            ['url' => '../operator/invoices.php', 'icon' => '🧾', 'label' => 'Invoices'],
            ['url' => '../operator/payments.php', 'icon' => '💰', 'label' => 'Payments'],
            ['url' => '../operator/receipts.php', 'icon' => '🧾', 'label' => 'Receipts'],
        ],
        'Support' => [
            ['url' => '../operator/inquiries.php', 'icon' => '📬', 'label' => 'Inquiries'],
            ['url' => '../operator/students.php', 'icon' => '👥', 'label' => 'Students'],
        ]
    ];
} else {
    $navItems = [
        'My Account' => [
            ['url' => '../student/dashboard.php', 'icon' => '🏠', 'label' => 'Dashboard'],
            ['url' => '../student/apply.php', 'icon' => '✍️', 'label' => 'Apply for Room'],
            ['url' => '../student/status.php', 'icon' => '📋', 'label' => 'My Application'],
        ],
        'Finance' => [
            ['url' => '../student/invoice.php', 'icon' => '🧾', 'label' => 'My Invoice'],
            ['url' => '../student/payment.php', 'icon' => '💳', 'label' => 'Upload Payment'],
            ['url' => '../student/receipt.php', 'icon' => '✅', 'label' => 'My Receipt'],
        ],
        'Support' => [
            ['url' => '../student/inquiry.php', 'icon' => '📬', 'label' => 'Submit Inquiry'],
        ]
    ];
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?><?= SITE_NAME ?></title>
<link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <a href="<?= SITE_URL ?>" class="brand-logo">
      <div class="logo-icon">M</div>
      <div class="brand-name">
        <strong>MUST Hostel</strong>
        <span>Allocation System</span>
      </div>
    </a>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar <?= $role ?>">
      <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
    </div>
    <div class="user-info">
      <strong><?= htmlspecialchars($user['full_name'] ?? 'User') ?></strong>
      <span><?= $role ?></span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($navItems as $section => $items): ?>
    <div class="nav-section">
      <div class="nav-section-label"><?= $section ?></div>
    </div>
    <?php foreach ($items as $item): ?>
    <a href="<?= $item['url'] ?>" class="nav-item <?= basename($item['url']) === $currentPage ? 'active' : '' ?>">
      <span class="icon"><?= $item['icon'] ?></span>
      <?= $item['label'] ?>
    </a>
    <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= SITE_URL ?>/logout.php" class="nav-item" style="margin:0">
      <span class="icon">🚪</span> Logout
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main-content">
  <header class="topbar">
    <div>
      <h1><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : SITE_NAME ?></h1>
      <?php if (isset($pageSubtitle)): ?>
      <p><?= htmlspecialchars($pageSubtitle) ?></p>
      <?php endif; ?>
    </div>
    <div class="topbar-right">
      <?php if ($role === 'student'): ?>
      <span class="badge badge-navy">📚 <?= htmlspecialchars($user['reg_number'] ?? '') ?></span>
      <?php endif; ?>
      <span class="text-muted"><?= date('D, d M Y') ?></span>
    </div>
  </header>

  <div class="page-content">
  <?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : $flash['type']) ?>">
    <span><?= $flash['type'] === 'success' ? '✅' : ($flash['type'] === 'error' ? '❌' : 'ℹ️') ?></span>
    <?= htmlspecialchars($flash['message']) ?>
  </div>
  <?php endif; ?>
