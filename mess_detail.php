<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_student_login();

$id = (int)($_GET['id'] ?? 0);
$res = mysqli_query($conn, "SELECT * FROM mess WHERE id=$id");
$mess = mysqli_fetch_assoc($res);
if (!$mess) { header('Location: dashboard.php'); exit; }

$enquirySent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enquire_message'])) {
    $msg = trim($_POST['enquire_message']);
    if ($msg !== '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO enquiries (student_id, mess_id, message) VALUES (?,?,?)");
        mysqli_stmt_bind_param($stmt, 'iis', $_SESSION['student_id'], $id, $msg);
        mysqli_stmt_execute($stmt);
        $enquirySent = true;
    }
}

// Images: gallery table + cover image fallback
$imgRes = mysqli_query($conn, "SELECT image_url FROM mess_images WHERE mess_id=$id");
$gallery = [$mess['cover_image']];
while ($g = mysqli_fetch_assoc($imgRes)) $gallery[] = $g['image_url'];
if (count($gallery) < 4) {
    $allImgs = [
        'https://commons.wikimedia.org/wiki/Special:FilePath/Indian_Thali_(Gujrati).jpg?width=600',
        'https://commons.wikimedia.org/wiki/Special:FilePath/Hyderabad_Veg_Thali.jpg?width=600',
        'https://commons.wikimedia.org/wiki/Special:FilePath/South_Indian_Thali_from_Hyderabad.JPG?width=600',
        'https://commons.wikimedia.org/wiki/Special:FilePath/Yummy_Andhra_Thali.jpg?width=600',
        'https://commons.wikimedia.org/wiki/Special:FilePath/Rajasthani_Food.JPG?width=600',
        'https://commons.wikimedia.org/wiki/Special:FilePath/A_Crispy_Dosa.jpg?width=600',
    ];
    for ($i = 0; $i < 3; $i++) {
        $gallery[] = $allImgs[($id + $i) % count($allImgs)];
    }
}

// Menu
$menuRes = mysqli_query($conn, "SELECT * FROM menu_items WHERE mess_id=$id ORDER BY FIELD(day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), FIELD(meal_type,'Breakfast','Lunch','Dinner')");
$menu = [];
while ($row = mysqli_fetch_assoc($menuRes)) $menu[$row['day_of_week']][] = $row;
$days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

// Reviews
$revRes = mysqli_query($conn, "SELECT f.*, s.full_name FROM feedback f JOIN students s ON s.id=f.student_id WHERE mess_id=$id ORDER BY f.created_at DESC LIMIT 6");
$reviewCountRes = mysqli_query($conn, "SELECT COUNT(*) c, AVG(rating) a FROM feedback WHERE mess_id=$id");
$revStats = mysqli_fetch_assoc($reviewCountRes);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title><?= h($mess['name']) ?> — MealConnect</title>
<?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar_student.php'; ?>

<div class="container">
  <div style="margin-top:16px;font-size:.85rem;color:var(--ink-soft)">
    <a href="dashboard.php" style="color:var(--orange);font-weight:600">← Back to results</a>
  </div>

  <div class="detail-hero">
    <img id="mainImg" src="<?= h($gallery[0]) ?>" alt="<?= h($mess['name']) ?>" onerror="this.onerror=null;this.src='/mealconnect/assets/images/food-thali-1.svg'">
  </div>
  <div class="gallery-strip">
    <?php foreach ($gallery as $i => $g): ?>
      <img src="<?= h($g) ?>" class="<?= $i === 0 ? 'active' : '' ?>" onerror="this.onerror=null;this.src='/mealconnect/assets/images/food-thali-1.svg'" onclick="document.getElementById('mainImg').src=this.src; document.querySelectorAll('.gallery-strip img').forEach(x=>x.classList.remove('active')); this.classList.add('active')">
    <?php endforeach; ?>
  </div>

  <div class="detail-grid">
    <div>
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px">
          <div>
            <span class="badge <?= badge_class($mess['veg_type']) ?>" style="position:static;display:inline-block;margin-bottom:8px"><?= h($mess['veg_type']) ?></span>
            <h1 style="font-size:1.6rem"><?= h($mess['name']) ?></h1>
            <div class="loc">📍 <?= h($mess['address']) ?></div>
          </div>
          <div style="text-align:right">
            <div class="stars">★★★★★</div>
            <div style="font-size:.8rem;color:var(--ink-soft)"><?= h($mess['rating']) ?> / 5 (<?= (int)($revStats['c'] ?? 0) ?> reviews)</div>
          </div>
        </div>
        <p style="color:var(--ink-soft);margin-top:12px"><?= h($mess['description']) ?></p>
        <div class="info-list" style="margin-top:14px">
          <div class="row">👤 <b>Owner:</b>&nbsp;<?= h($mess['owner_name']) ?> · <?= h($mess['owner_contact']) ?></div>
          <div class="row">🏷️ <b>Type:</b>&nbsp;<?= h($mess['type']) ?> · <?= h($mess['location_area']) ?>, Pune</div>
        </div>
        <div class="fac-row">
          <?php foreach (explode(',', $mess['facilities']) as $f): ?><span class="fac"><?= h(trim($f)) ?></span><?php endforeach; ?>
        </div>
      </div>

      <div class="panel">
        <h3>Weekly Menu</h3>
        <table class="menu-table">
          <thead><tr><th>Day</th><th>Lunch</th><th>Dinner</th></tr></thead>
          <tbody>
          <?php foreach ($days as $d):
            $lunch = ''; $dinner = '';
            foreach (($menu[$d] ?? []) as $item) {
                if ($item['meal_type'] === 'Lunch') $lunch = $item['item_name'];
                if ($item['meal_type'] === 'Dinner') $dinner = $item['item_name'];
            }
            if ($lunch === '' && $dinner === '') { $lunch = 'Home-style thali'; $dinner = 'Home-style thali'; }
          ?>
            <tr><td><b><?= $d ?></b></td><td><?= h($lunch) ?></td><td><?= h($dinner) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <h3>Ratings &amp; Reviews</h3>
        <?php if (mysqli_num_rows($revRes) === 0): ?>
          <p style="color:var(--ink-soft);font-size:.9rem">No reviews yet — be the first to try this mess and share feedback!</p>
        <?php else: mysqli_data_seek($revRes, 0); while ($r = mysqli_fetch_assoc($revRes)): ?>
          <div class="review-item">
            <div style="display:flex;justify-content:space-between"><b><?= h($r['full_name']) ?></b><span class="stars"><?= str_repeat('★', (int)$r['rating']) ?></span></div>
            <p style="color:var(--ink-soft);font-size:.88rem;margin:4px 0 0"><?= h($r['review']) ?></p>
          </div>
        <?php endwhile; endif; ?>
      </div>
    </div>

    <div>
      <div class="panel sticky-cta">
        <div class="price" style="font-size:1.6rem">₹<?= number_format($mess['price_per_month']) ?><span> /month</span></div>
        <p style="font-size:.82rem;color:var(--ink-soft)">2 meals/day · <?= h($mess['veg_type']) ?></p>
        <a href="membership.php?mess_id=<?= (int)$mess['id'] ?>" class="btn btn-primary btn-block" style="margin-top:10px">Join Now</a>
        <button type="button" class="btn btn-outline btn-block" style="margin-top:10px" onclick="document.getElementById('enquireModal').classList.add('open')">Enquire Now</button>

        <div id="map"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal-bg <?= $enquirySent ? 'open' : '' ?>" id="enquireModal">
  <div class="modal-box">
    <span class="close" onclick="document.getElementById('enquireModal').classList.remove('open')">✕</span>
    <?php if ($enquirySent): ?>
      <h3>✅ Enquiry sent!</h3>
      <p style="color:var(--ink-soft);font-size:.9rem"><?= h($mess['owner_name']) ?> will get back to you soon on your registered phone number.</p>
      <button class="btn btn-primary btn-block" onclick="document.getElementById('enquireModal').classList.remove('open')">Close</button>
    <?php else: ?>
      <h3>Enquire about <?= h($mess['name']) ?></h3>
      <form method="post">
        <div class="field">
          <label>Your message</label>
          <textarea name="enquire_message" rows="4" placeholder="e.g. Is there a discount for a 2-person group? Do you deliver to hostel X?" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send Enquiry</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map', {zoomControl:false, dragging:false, scrollWheelZoom:false}).setView([<?= $mess['latitude'] ?>, <?= $mess['longitude'] ?>], 14);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap'}).addTo(map);
L.marker([<?= $mess['latitude'] ?>, <?= $mess['longitude'] ?>]).addTo(map).bindPopup(<?= json_encode($mess['name']) ?>).openPopup();
</script>
</body>
</html>
