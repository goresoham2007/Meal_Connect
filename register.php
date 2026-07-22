<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!empty($_SESSION['student_id'])) { header('Location: dashboard.php'); exit; }

$errors = [];
$full_name = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // FR-1.3 Username validation
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Phone number must be exactly 10 digits';
    }
    // Full name validation (2-50 alphabets & space)
    if (!preg_match('/^[A-Za-z ]{2,50}$/', $full_name)) {
        $errors[] = 'Enter a valid full name (letters and spaces only, 2-50 characters)';
    }
    // FR-1.4 Password validation
    if (!(strlen($password) >= 8 && strlen($password) <= 32
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[!@#$%^&*]/', $password))) {
        $errors[] = 'Password must include uppercase, number, and special character';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $check = mysqli_query($conn, "SELECT id FROM students WHERE phone='" . mysqli_real_escape_string($conn, $phone) . "'");
        if (mysqli_num_rows($check) > 0) {
            $errors[] = 'This phone number is already registered. Please login instead.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "INSERT INTO students (full_name, phone, password_hash) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $full_name, $phone, $hash);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['student_id'] = mysqli_insert_id($conn);
            $_SESSION['student_name'] = $full_name;
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Student Register — MealConnect</title>
<?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-side">
      <span class="mark" style="background:#fff2"><svg viewBox="0 0 24 24" fill="none"><path d="M6 3v8a3 3 0 003 3v7M6 3v8M9 3v8M12 3v6a2 2 0 002 2h1v9m0-13V3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
      <h2>Join MealConnect</h2>
      <p>Create your free student account and start discovering mess &amp; cloud kitchens near your college.</p>
      <ul>
        <li>Browse 26+ verified mess across Pune</li>
        <li>Enquire before you join</li>
        <li>2-day / 3-day trial meals</li>
        <li>Track attendance &amp; payments</li>
      </ul>
    </div>
    <div class="auth-form">
      <div class="auth-tabs">
        <a href="login.php">Login</a>
        <a href="register.php" class="active">Register</a>
      </div>
      <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e) echo h($e) . '<br>'; ?></div>
      <?php endif; ?>
      <form method="post" id="regForm" novalidate>
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="full_name" value="<?= h($full_name) ?>" placeholder="e.g. Aditi Sharma" required>
        </div>
        <div class="field">
          <label>Phone Number (Username)</label>
          <input type="text" name="phone" id="phone" maxlength="10" value="<?= h($phone) ?>" placeholder="10-digit mobile number" required>
          <div class="hint">Must be exactly 10 digits — this will be your login username</div>
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" id="password" placeholder="Create a strong password" required>
          <div class="strength-meter"><i id="strengthBar"></i></div>
          <div class="check-list">
            <span id="c-len">8+ characters</span>
            <span id="c-up">Uppercase</span>
            <span id="c-num">Number</span>
            <span id="c-sym">Symbol !@#$%^&amp;*</span>
          </div>
        </div>
        <div class="field">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Re-enter password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
      </form>
      <p style="text-align:center;font-size:.85rem;color:var(--ink-soft);margin-top:16px">Are you a mess owner? <a href="../owner/register.php" style="color:var(--orange);font-weight:600">List your mess</a></p>
    </div>
  </div>
</div>

<script>
const pw = document.getElementById('password');
const bar = document.getElementById('strengthBar');
const checks = {len:document.getElementById('c-len'), up:document.getElementById('c-up'), num:document.getElementById('c-num'), sym:document.getElementById('c-sym')};
pw.addEventListener('input', () => {
  const v = pw.value;
  const tests = {
    len: v.length >= 8,
    up: /[A-Z]/.test(v),
    num: /[0-9]/.test(v),
    sym: /[!@#$%^&*]/.test(v)
  };
  let score = 0;
  for (const k in tests) {
    checks[k].classList.toggle('ok', tests[k]);
    if (tests[k]) score++;
  }
  const pct = (score/4)*100;
  bar.style.width = pct + '%';
  bar.style.background = pct < 50 ? '#c1401f' : pct < 100 ? '#d9a441' : '#2f6f4e';
});
document.getElementById('phone').addEventListener('input', function(){
  this.value = this.value.replace(/[^0-9]/g,'').slice(0,10);
});
</script>
</body>
</html>
