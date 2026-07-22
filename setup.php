<?php
/**
 * RUN THIS FILE ONCE IN YOUR BROWSER AFTER IMPORTING schema.sql
 *   http://localhost/mealconnect/setup.php
 *
 * It fixes the demo owner password hashes (bcrypt hashes can't be hand
 * written safely into a .sql file) and creates a ready-to-use demo
 * student account with sample subscription, payment, attendance and
 * feedback data so the dashboards aren't empty on first look.
 *
 * Safe to run more than once.
 */
require_once __DIR__ . '/config/db.php';

$msgs = [];

// 1. Fix owner demo passwords -> "Owner@123"
$ownerHash = password_hash('Owner@123', PASSWORD_BCRYPT);
mysqli_query($conn, "UPDATE owners SET password_hash='" . mysqli_real_escape_string($conn, $ownerHash) . "'");
$msgs[] = "Owner demo accounts ready. Login with phone 9876500001 or 9876500002, password: Owner@123";

// 2. Create (or reset) a demo student account -> "Student@123"
$studentPhone = '9998887770';
$studentHash = password_hash('Student@123', PASSWORD_BCRYPT);
$res = mysqli_query($conn, "SELECT id FROM students WHERE phone='$studentPhone'");
if (mysqli_num_rows($res) > 0) {
    $studentId = mysqli_fetch_assoc($res)['id'];
    mysqli_query($conn, "UPDATE students SET password_hash='" . mysqli_real_escape_string($conn, $studentHash) . "' WHERE id=$studentId");
} else {
    mysqli_query($conn, "INSERT INTO students (full_name, phone, password_hash, college_name, course, year)
        VALUES ('Demo Student', '$studentPhone', '" . mysqli_real_escape_string($conn, $studentHash) . "', 'MIT Academy of Engineering', 'B.E. Computer Engineering', '3rd Year')");
    $studentId = mysqli_insert_id($conn);
}
$msgs[] = "Demo student account ready. Login with phone $studentPhone, password: Student@123";

// 3. Give the demo student an active subscription with a demo mess (id 1) if none exists
$res = mysqli_query($conn, "SELECT id FROM subscriptions WHERE student_id=$studentId AND status='Active'");
if (mysqli_num_rows($res) == 0) {
    $messId = 1;
    $planRes = mysqli_query($conn, "SELECT * FROM plans WHERE mess_id=$messId AND plan_name='Monthly' LIMIT 1");
    $plan = mysqli_fetch_assoc($planRes);
    $joinDate = date('Y-m-d', strtotime('-10 days'));
    $expiryDate = date('Y-m-d', strtotime($joinDate . ' +30 days'));
    $txn = 'TXN' . strtoupper(substr(md5(uniqid()), 0, 10));
    mysqli_query($conn, "INSERT INTO subscriptions (student_id, mess_id, plan_id, plan_label, join_date, expiry_date, amount, transaction_id, status)
        VALUES ($studentId, $messId, {$plan['id']}, 'Monthly Plan', '$joinDate', '$expiryDate', {$plan['price']}, '$txn', 'Active')");

    // 4. Seed 10 days of attendance for the current month
    for ($i = 10; $i >= 1; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $rand = mt_rand(1, 10);
        $status = $rand <= 7 ? 'Present' : ($rand <= 9 ? 'Absent' : 'Holiday');
        mysqli_query($conn, "INSERT IGNORE INTO attendance (student_id, mess_id, attendance_date, status)
            VALUES ($studentId, $messId, '$date', '$status')");
    }

    // 5. Seed one feedback entry
    mysqli_query($conn, "INSERT INTO feedback (student_id, mess_id, rating, review) VALUES
        ($studentId, $messId, 5, 'Great home-style food, tastes just like ghar ka khana. Highly recommend!')");

    $msgs[] = "Sample subscription, 10 days of attendance and a feedback entry were added for the demo student at mess #1.";
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>MealConnect Setup</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#fff8f0;margin:0;padding:60px 20px;color:#2b1b12}
.card{max-width:640px;margin:0 auto;background:#fff;border-radius:16px;padding:36px 40px;box-shadow:0 10px 30px rgba(43,27,18,.08)}
h1{background:linear-gradient(135deg,#e8620c,#ff3d68);-webkit-background-clip:text;background-clip:text;color:transparent;margin-top:0}
ul{padding-left:20px;line-height:1.9}
li{margin-bottom:6px}
a.btn{display:inline-block;margin-top:20px;background:linear-gradient(135deg,#e8620c,#ff3d68);color:#fff;padding:12px 26px;border-radius:30px;text-decoration:none;font-weight:600}
code{background:#fdece0;padding:2px 6px;border-radius:4px}
</style>
</head>
<body>
<div class="card">
<h1>✅ MealConnect setup complete</h1>
<ul>
<?php foreach ($msgs as $m) echo "<li>$m</li>"; ?>
</ul>
<p>You can now delete or rename this file — it doesn't need to run again unless you re-import the database.</p>
<a class="btn" href="index.php">Go to MealConnect →</a>
</div>
</body>
</html>
