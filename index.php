<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: " . SITE_URL . "/{$role}/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = clean($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your credentials.';
    } else {
        $db = getDB();
        
        // Find user by reg_number, email, or username
        $stmt = $db->prepare(
            "SELECT * FROM users WHERE (reg_number = ? OR email = ? OR username = ?) LIMIT 1"
        );
        $stmt->execute([$identifier, $identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['reg_number'] = $user['reg_number'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['gender'] = $user['gender'];

            header("Location: " . SITE_URL . "/{$user['role']}/dashboard.php");
            exit;
        } else {
            $error = 'Invalid credentials. Please check your username, email, registration number, or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - MUST Hostel  Allocation</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; color: #aaa; font-size: 12px; }
.divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
.register-link { text-align: center; margin-top: 20px; font-size: 13px; color: #64748b; }
.register-link a { color: #0f2d52; font-weight: 700; text-decoration: none; }
</style>
</head>
<body>
<div class="login-page">
  <div class="login-left">
    <div class="login-hero">
      <div class="school-badge">
        <div class="badge-icon">M</div>
        <div class="school-name">
          <strong>MUST</strong>
          Malawi University of Science<br>and Technology
        </div>
      </div>
      <h1>Hostel Room <span>Booking</span> System</h1>
      <p>Seamlessly manage hostel accommodation for students. Apply, track, pay and get allocated — all in one place.</p>
      <div class="login-features">
        <div class="feature-item"><div class="fi">🏠</div> Random & Manual Room Allocation</div>
        <div class="feature-item"><div class="fi">📧</div> Email Notifications & Receipts</div>
        <div class="feature-item"><div class="fi">💳</div> Invoice & Payment Tracking</div>
        <div class="feature-item"><div class="fi"></div> Special Needs Support</div>
      </div>
    </div>
  </div>

  <div class="login-right">
    <div class="login-form-wrap">
      <h2>Welcome Back</h2>
      <p>Sign in to your account to continue</p>

      <?php if ($error): ?>
      <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Account created! You can now log in.</div>
      <?php endif; ?>
      <?php if (isset($_GET['error']) && $_GET['error'] === 'unauthorized'): ?>
      <div class="alert alert-warning"> You don't have permission to access that page.</div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <div class="form-group">
          <label>Username / Email / Registration Number</label>
          <input type="text" name="identifier" id="identifier"
                 placeholder="Enter your username, email, or registration number" required
                 value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
          Sign In →
        </button>
      </form>

      <div class="divider">or</div>

      <div class="register-link">
        New student? <a href="register.php">Create your account</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
