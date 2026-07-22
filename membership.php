<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_student_login();

$messId = (int)($_GET['mess_id'] ?? 0);
$mess = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mess WHERE id=$messId"));
if (!$mess) { header('Location: dashboard.php'); exit; }

$plans = mysqli_query($conn, "SELECT * FROM plans WHERE mess_id=$messId ORDER BY duration_days");
$trials = mysqli_query($conn, "SELECT * FROM trial_plans WHERE mess_id=$messId ORDER BY trial_days");

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kind = $_POST['kind'] ?? '';
    $studentId = $_SESSION['student_id'];
    $joinDate = date('Y-m-d');
    $txn = gen_txn_id();

    if ($kind === 'plan') {
        $planId = (int)$_POST['plan_id'];
        $plan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM plans WHERE id=$planId"));
        if ($plan) {
            $expiry = date('Y-m-d', strtotime("+{$plan['duration_days']} days"));
            $label = $plan['plan_name'] . ' Plan';
            $stmt = mysqli_prepare($conn, "INSERT INTO subscriptions (student_id, mess_id, plan_id, plan_label, join_date, expiry_date, amount, transaction_id, status) VALUES (?,?,?,?,?,?,?,?, 'Active')");
            mysqli_stmt_bind_param($stmt, 'iiisssis', $studentId, $messId, $planId, $label, $joinDate, $expiry, $plan['price'], $txn);
            mysqli_stmt_execute($stmt);
            $success = true;
        }
    } elseif ($kind === 'trial') {
        $trialId = (int)$_POST['trial_id'];
        $trial = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM trial_plans WHERE id=$trialId"));
        if ($trial) {
            $expiry = date('Y-m-d', strtotime("+{$trial['trial_days']} days"));
            $label = $trial['trial_days'] . '-Day Trial';
            $stmt = mysqli_prepare($conn, "INSERT INTO subscriptions (student_id, mess_id, trial_id, plan_label, join_date, expiry_date, amount, transaction_id, status) VALUES (?,?,?,?,?,?,?,?, 'Active')");
            mysqli_stmt_bind_param($stmt, 'iiisssis', $studentId, $messId, $trialId, $label, $joinDate, $expiry, $trial['price'], $txn);
            mysqli_stmt_execute($stmt);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Membership Plans — <?= h($mess['name']) ?> — MealConnect</title>
<?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar_student.php'; ?>

<div class="container" style="padding-top:20px">
  <a href="mess_detail.php?id=<?= $messId ?>" style="color:var(--orange);font-weight:600;font-size:.85rem">← Back to <?= h($mess['name']) ?></a>

  <?php if ($success): ?>
    <div class="panel" style="text-align:center;margin-top:20px;border-color:var(--green)">
      <div style="font-size:2.4rem">✅</div>
      <h2>Payment Successful!</h2>
      <p style="color:var(--ink-soft)">You've joined <?= h($mess['name']) ?>. Details have been added to your profile.</p>
      <a href="profile.php" class="btn btn-primary" style="margin-top:10px">Go to My Profile →</a>
    </div>
  <?php else: ?>

  <h1 style="margin-top:16px">Choose a plan for <?= h($mess['name']) ?></h1>
  <p style="color:var(--ink-soft)">Not ready to commit? Try a short trial first — pay only for the days you eat.</p>

  <div class="trial-row">
    <?php while ($t = mysqli_fetch_assoc($trials)): ?>
      <div class="trial-card">
        <b><?= (int)$t['trial_days'] ?>-Day Trial</b>
        <p style="font-size:.85rem;color:var(--ink-soft);margin:4px 0 12px">Try <?= (int)$t['trial_days'] ?> days of meals before subscribing monthly.</p>
        <div class="price" style="font-size:1.3rem">₹<?= number_format($t['price']) ?></div>
        <form method="post" style="margin-top:10px">
          <input type="hidden" name="kind" value="trial">
          <input type="hidden" name="trial_id" value="<?= (int)$t['id'] ?>">
          <button class="btn btn-outline btn-block btn-sm" type="submit">Pay &amp; Start Trial</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>

  <div class="tiffin-divider" style="margin-top:34px"><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>

  <div class="plan-grid">
    <?php $idx = 0; while ($p = mysqli_fetch_assoc($plans)): $idx++; $isBest = $p['plan_name'] === 'Half-Yearly'; ?>
      <div class="plan-card <?= $isBest ? 'best' : '' ?>">
        <?php if ($isBest): ?><div class="ribbon">Best Value</div><?php endif; ?>
        <h3><?= h($p['plan_name']) ?></h3>
        <div class="amt">₹<?= number_format($p['price']) ?><span>/<?= (int)$p['duration_days'] ?> days</span></div>
        <?php if ($p['savings_note']): ?><div class="save"><?= h($p['savings_note']) ?></div><?php endif; ?>
        <ul>
          <li>✔️ <?= h($p['meals_per_day']) ?></li>
          <li>✔️ <?= h($p['menu_type']) ?> menu</li>
          <li>✔️ Cancel / renew anytime</li>
        </ul>
        <form method="post">
          <input type="hidden" name="kind" value="plan">
          <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="btn <?= $isBest ? 'btn-primary' : 'btn-outline' ?> btn-block">Pay &amp; Join Now</button>
        </form>
      </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
