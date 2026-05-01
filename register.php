<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header("Location: " . SITE_URL . "/student/dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regNumber = strtoupper(trim($_POST['reg_number'] ?? ''));
    $fullName = clean($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $gender = clean($_POST['gender'] ?? '');

    if (empty($regNumber) || empty($fullName) || empty($password) || empty($gender)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^[A-Z0-9\/\-]+$/i', $regNumber)) {
        $error = 'Invalid registration number format.';
    } else {
        $db = getDB();
        $email = genEmail($regNumber);
        
        // Check if reg number already exists
        $existing = $db->prepare("SELECT id FROM users WHERE reg_number = ?");
        $existing->execute([$regNumber]);
        
        if ($existing->fetch()) {
            $error = 'A student with this registration number already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $db->prepare(
                    "INSERT INTO users (reg_number, full_name, email, password, role, gender) VALUES (?,?,?,?,?,?)"
                )->execute([$regNumber, $fullName, $email, $hash, 'student', $gender]);
                
                header("Location: " . SITE_URL . "/index.php?registered=1");
                exit;
            } catch (Exception $e) {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - MUST Hostel</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-left">
    <div class="login-hero">
      <div class="school-badge">
        <div class="badge-icon">M</div>
        <div class="school-name"><strong>MUST</strong>Malawi University of Science<br>and Technology</div>
      </div>
      <h1>Create Your <span>Student</span> Account</h1>
      <p>Register with your student details to access the hostel booking system. Your email will be generated automatically from your registration number.</p>
      <div class="login-features">
        <div class="feature-item"><div class="fi">🎓</div> Use your official registration number</div>
        <div class="feature-item"><div class="fi">📧</div> Email auto-generated: regnumber@must.ac.mw</div>
        <div class="feature-item"><div class="fi">🔒</div> Your password stays private</div>
      </div>
    </div>
  </div>

  <div class="login-right">
    <div class="login-form-wrap">
      <h2>Student Registration</h2>
      <p>Create your account to apply for hostel accommodation</p>

      <?php if ($error): ?>
      <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label>Registration Number <span class="req">*</span></label>
          <input type="text" name="reg_number" placeholder="e.g. MU/BSCS/24/001" required
                 value="<?= htmlspecialchars($_POST['reg_number'] ?? '') ?>"
                 oninput="updateEmail(this.value)">
          <div class="form-hint">Your email will be: <strong id="emailPreview">regnumber@must.ac.mw</strong></div>
        </div>

        <div class="form-group">
          <label>Full Name <span class="req">*</span></label>
          <input type="text" name="full_name" placeholder="e.g. Chimwemwe Banda" required
                 value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Gender <span class="req">*</span></label>
          <select name="gender" required>
            <option value="">-- Select Gender --</option>
            <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
          </select>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Password <span class="req">*</span></label>
            <input type="password" name="password" placeholder="Min 6 characters" required>
          </div>
          <div class="form-group">
            <label>Confirm Password <span class="req">*</span></label>
            <input type="password" name="confirm_password" placeholder="Repeat password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width:100%">
          Create Account →
        </button>
      </form>

      <div style="text-align:center;margin-top:20px;font-size:13px;color:#64748b">
        Already have an account? <a href="index.php" style="color:#0f2d52;font-weight:700">Sign In</a>
      </div>
    </div>
  </div>
</div>

<script>
function updateEmail(val) {
    const clean = val.replace(/[^a-zA-Z0-9\/\-]/g, '').toLowerCase();
    document.getElementById('emailPreview').textContent = (clean || 'regnumber') + '@must.ac.mw';
}
</script>
</body>
</html>
