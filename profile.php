<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_student_login();

$studentId = $_SESSION['student_id'];
$tab = $_GET['tab'] ?? 'credentials';

// Update credentials
$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $college = trim($_POST['college_name']);
    $course = trim($_POST['course']);
    $year = trim($_POST['year']);
    $stmt = mysqli_prepare($conn, "UPDATE students SET college_name=?, course=?, year=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssi', $college, $course, $year, $studentId);
    mysqli_stmt_execute($stmt);
    $saved = true;
}

// Feedback submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $messId = (int)$_POST['mess_id'];
    $rating = (int)$_POST['rating'];
    $review = trim($_POST['review']);
    $stmt = mysqli_prepare($conn, "INSERT INTO feedback (student_id, mess_id, rating, review) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'iiis', $studentId, $messId, $rating, $review);
    mysqli_stmt_execute($stmt);
    $tab = 'feedback';
    $saved = true;
}

$student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM students WHERE id=$studentId"));

$subsRes = mysqli_query($conn, "SELECT sub.*, m.name mess_name, m.owner_contact, m.location_area, m.cover_image FROM subscriptions sub JOIN mess m ON m.id=sub.mess_id WHERE sub.student_id=$studentId ORDER BY sub.payment_date DESC");
$subscriptions = [];
while ($s = mysqli_fetch_assoc($subsRes)) $subscriptions[] = $s;

$activeSub = null;
foreach ($subscriptions as $s) {
    if ($s['status'] === 'Active' && $s['expiry_date'] >= date('Y-m-d')) { $activeSub = $s; break; }
}

// Attendance for current month for active mess
$calCells = [];
$presentPct = 0;
if ($activeSub) {
    $messId = $activeSub['mess_id'];
    $month = date('Y-m');
    $attRes = mysqli_query($conn, "SELECT attendance_date, status FROM attendance WHERE student_id=$studentId AND mess_id=$messId AND attendance_date LIKE '$month%'");
    $attMap = [];
    while ($a = mysqli_fetch_assoc($attRes)) $attMap[$a['attendance_date']] = $a['status'];

    $daysInMonth = date('t');
    $firstDow = (int)date('N', strtotime(date('Y-m-01'))); // 1=Mon..7=Sun
    $present = 0; $marked = 0;
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dateStr = date('Y-m-') . str_pad($d, 2, '0', STR_PAD_LEFT);
        $status = $attMap[$dateStr] ?? null;
        if ($status) { $marked++; if ($status === 'Present') $present++; }
        $calCells[] = ['day' => $d, 'status' => $status];
    }
    $presentPct = $marked > 0 ? round(($present / $marked) * 100) : 0;
}

$feedbackRes = mysqli_query($conn, "SELECT f.*, m.name mess_name FROM feedback f JOIN mess m ON m.id=f.mess_id WHERE f.student_id=$studentId ORDER BY f.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>My Profile — MealConnect</title>
<?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar_student.php'; ?>

<div class="container" style="padding-bottom:60px">
  <h1 style="margin-top:24px">My Profile</h1>

  <div class="stat-row">
    <div class="stat-box"><b><?= count($subscriptions) ?></b><span>Mess Joined (all time)</span></div>
    <div class="stat-box"><b><?= $activeSub ? '1' : '0' ?></b><span>Active Subscription</span></div>
    <div class="stat-box"><b><?= $presentPct ?>%</b><span>Attendance this month</span></div>
    <div class="stat-box"><b><?= mysqli_num_rows($feedbackRes) ?></b><span>Feedback Given</span></div>
  </div>

  <div class="profile-shell">
    <div class="profile-tabs">
      <a href="?tab=credentials" class="<?= $tab==='credentials'?'active':'' ?>">👤 Credentials</a>
      <a href="?tab=mess" class="<?= $tab==='mess'?'active':'' ?>">🍱 Mess Joined</a>
      <a href="?tab=payments" class="<?= $tab==='payments'?'active':'' ?>">💳 Payment History</a>
      <a href="?tab=attendance" class="<?= $tab==='attendance'?'active':'' ?>">📅 Attendance</a>
      <a href="?tab=feedback" class="<?= $tab==='feedback'?'active':'' ?>">⭐ Feedback</a>
      <a href="logout.php" class="logout">🚪 Log Out</a>
    </div>

    <div>
      <?php if ($saved): ?><div class="alert alert-success">Saved successfully!</div><?php endif; ?>

      <?php if ($tab === 'credentials'): ?>
        <div class="panel">
          <h3>Credentials</h3>
          <form method="post">
            <div class="field"><label>Full Name</label><input type="text" value="<?= h($student['full_name']) ?>" disabled></div>
            <div class="field"><label>Phone Number</label><input type="text" value="<?= h($student['phone']) ?>" disabled></div>
            <div class="field"><label>College Name</label><input type="text" name="college_name" value="<?= h($student['college_name']) ?>" placeholder="e.g. MIT Academy of Engineering"></div>
            <div class="field"><label>Course</label><input type="text" name="course" value="<?= h($student['course']) ?>" placeholder="e.g. B.E. Computer Engineering"></div>
            <div class="field"><label>Year</label>
              <select name="year">
                <?php foreach (['1st Year','2nd Year','3rd Year','4th Year'] as $y): ?>
                  <option <?= $student['year']===$y?'selected':'' ?>><?= $y ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" name="update_profile" value="1" class="btn btn-primary">Save Changes</button>
          </form>
        </div>

      <?php elseif ($tab === 'mess'): ?>
        <?php if (!$activeSub): ?>
          <div class="panel"><p>You haven't joined any mess yet. <a href="dashboard.php" style="color:var(--orange);font-weight:600">Browse mess near you →</a></p></div>
        <?php else: ?>
          <div class="panel" style="display:flex;gap:18px;align-items:center;flex-wrap:wrap">
            <img src="<?= h($activeSub['cover_image']) ?>" style="width:110px;height:90px;object-fit:cover;border-radius:12px" onerror="this.onerror=null;this.src='/mealconnect/assets/images/food-thali-1.svg'">
            <div style="flex:1;min-width:200px">
              <h3 style="margin-bottom:2px"><?= h($activeSub['mess_name']) ?></h3>
              <div class="loc">📍 <?= h($activeSub['location_area']) ?>, Pune</div>
              <div style="margin-top:8px" class="info-list">
                <div class="row"><b>Plan:</b>&nbsp;<?= h($activeSub['plan_label']) ?></div>
                <div class="row"><b>Join Date:</b>&nbsp;<span class="mono"><?= h($activeSub['join_date']) ?></span></div>
                <div class="row"><b>Expiry Date:</b>&nbsp;<span class="mono"><?= h($activeSub['expiry_date']) ?></span></div>
                <div class="row"><b>Days Remaining:</b>&nbsp;<?= max(0, days_between(date('Y-m-d'), $activeSub['expiry_date'])) ?> days</div>
                <div class="row"><b>Owner Contact:</b>&nbsp;<?= h($activeSub['owner_contact']) ?></div>
              </div>
              <a href="membership.php?mess_id=<?= (int)$activeSub['mess_id'] ?>" class="btn btn-sm btn-outline" style="margin-top:10px">Renew / Change Plan</a>
            </div>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'payments'): ?>
        <div class="panel">
          <h3>Payment History</h3>
          <?php if (empty($subscriptions)): ?>
            <p style="color:var(--ink-soft)">No payments yet.</p>
          <?php else: ?>
          <table class="data-table">
            <thead><tr><th>Date</th><th>Mess</th><th>Plan</th><th>Amount</th><th>Transaction ID</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($subscriptions as $s):
              $isActive = $s['status']==='Active' && $s['expiry_date'] >= date('Y-m-d');
            ?>
              <tr>
                <td class="mono"><?= h(date('d M Y', strtotime($s['payment_date']))) ?></td>
                <td><?= h($s['mess_name']) ?></td>
                <td><?= h($s['plan_label']) ?></td>
                <td>₹<?= number_format($s['amount']) ?></td>
                <td class="mono"><?= h($s['transaction_id']) ?></td>
                <td><span class="status-pill <?= $isActive ? 'active' : 'expired' ?>"><?= $isActive ? 'Active' : 'Expired' ?></span></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

      <?php elseif ($tab === 'attendance'): ?>
        <div class="panel">
          <h3>Monthly Attendance — <?= date('F Y') ?></h3>
          <?php if (!$activeSub): ?>
            <p style="color:var(--ink-soft)">Join a mess to start tracking attendance.</p>
          <?php else: ?>
          <div style="display:flex;gap:24px;align-items:center;flex-wrap:wrap;margin-bottom:16px">
            <div class="pct-ring" style="background:conic-gradient(#2f6f4e <?= $presentPct*3.6 ?>deg,#f0ddca 0deg)">
              <div style="background:#fff;width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem"><?= $presentPct ?>%</div>
            </div>
            <div style="font-size:.88rem;color:var(--ink-soft)">Overall attendance at <b><?= h($activeSub['mess_name']) ?></b> this month.</div>
          </div>
          <div class="cal-grid">
            <?php foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $dow): ?><div class="dow"><?= $dow ?></div><?php endforeach; ?>
            <?php for ($i = 1; $i < $firstDow; $i++): ?><div class="cal-cell empty"></div><?php endfor; ?>
            <?php foreach ($calCells as $c):
              $cls = $c['status'] === 'Present' ? 'present' : ($c['status'] === 'Absent' ? 'absent' : ($c['status'] === 'Holiday' ? 'holiday' : ''));
            ?>
              <div class="cal-cell <?= $cls ?>"><?= $c['day'] ?></div>
            <?php endforeach; ?>
          </div>
          <div class="legend">
            <span><i class="present"></i> Present</span>
            <span><i class="absent"></i> Absent</span>
            <span><i class="holiday"></i> Mess Holiday</span>
          </div>
          <?php endif; ?>
        </div>

      <?php elseif ($tab === 'feedback'): ?>
        <?php if ($activeSub): ?>
        <div class="panel">
          <h3>Rate <?= h($activeSub['mess_name']) ?></h3>
          <form method="post" id="fbForm">
            <input type="hidden" name="mess_id" value="<?= (int)$activeSub['mess_id'] ?>">
            <input type="hidden" name="rating" id="ratingInput" value="5">
            <div class="star-input" id="starInput">
              <span data-v="1">★</span><span data-v="2">★</span><span data-v="3">★</span><span data-v="4">★</span><span data-v="5">★</span>
            </div>
            <div class="field" style="margin-top:12px">
              <label>Your review</label>
              <textarea name="review" rows="3" placeholder="How was the food and service?" required></textarea>
            </div>
            <button type="submit" name="submit_feedback" value="1" class="btn btn-primary">Submit Feedback</button>
          </form>
        </div>
        <?php endif; ?>
        <div class="panel">
          <h3>Your Past Feedback</h3>
          <?php if (mysqli_num_rows($feedbackRes) === 0): ?>
            <p style="color:var(--ink-soft)">You haven't left any feedback yet.</p>
          <?php else: while ($f = mysqli_fetch_assoc($feedbackRes)): ?>
            <div class="review-item">
              <div style="display:flex;justify-content:space-between"><b><?= h($f['mess_name']) ?></b><span class="stars"><?= str_repeat('★', (int)$f['rating']) ?></span></div>
              <p style="color:var(--ink-soft);font-size:.88rem;margin:4px 0 0"><?= h($f['review']) ?></p>
            </div>
          <?php endwhile; endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
const stars = document.querySelectorAll('#starInput span');
if (stars.length) {
  stars.forEach(s => s.addEventListener('click', () => {
    const v = +s.dataset.v;
    document.getElementById('ratingInput').value = v;
    stars.forEach(x => x.classList.toggle('on', +x.dataset.v <= v));
  }));
  stars.forEach(x => x.classList.toggle('on', +x.dataset.v <= 5));
}
</script>
</body>
</html>
