<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_student_login();

$q = trim($_GET['q'] ?? '');
$area = trim($_GET['area'] ?? '');
$chip = trim($_GET['chip'] ?? 'All');

$where = [];
if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    $where[] = "(name LIKE '%$qEsc%' OR location_area LIKE '%$qEsc%' OR type LIKE '%$qEsc%' OR tags LIKE '%$qEsc%')";
}
if ($area !== '' && $area !== 'All over Pune') {
    $areaEsc = mysqli_real_escape_string($conn, $area);
    $where[] = "location_area = '$areaEsc'";
}
switch ($chip) {
    case 'Pure Veg Mess': $where[] = "veg_type='Pure Veg' AND type='Mess'"; break;
    case 'Non-Veg Special': $where[] = "veg_type='Non-Veg Special'"; break;
    case 'Cloud Kitchens': $where[] = "type='Cloud Kitchen'"; break;
    case 'Small Startups': $where[] = "type='Startup'"; break;
    case 'Budget Friendly': $where[] = "budget_friendly=1"; break;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$result = mysqli_query($conn, "SELECT * FROM mess $whereSql ORDER BY rating DESC");

$areasRes = mysqli_query($conn, "SELECT DISTINCT location_area FROM mess ORDER BY location_area");
$countRes = mysqli_query($conn, "SELECT COUNT(*) c FROM mess");
$totalMess = mysqli_fetch_assoc($countRes)['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<title>Browse Mess &amp; Cloud Kitchens — MealConnect</title>
<?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/navbar_student.php'; ?>

<section class="hero" style="padding:34px 0 10px">
  <div class="container">
    <h1 style="font-size:1.9rem">Mess &amp; Cloud Kitchens all over Pune</h1>
    <p style="color:var(--ink-soft);max-width:640px">Not just one locality — <?= (int)$totalMess ?> verified listings across Narhe, Katraj, Hinjewadi, Wakad, Kothrud, Chakan, Hadapsar and more.</p>
  </div>
</section>

<div class="container">
  <form method="get" class="chip-row" id="chipForm">
    <input type="hidden" name="q" value="<?= h($q) ?>">
    <input type="hidden" name="area" value="<?= h($area) ?>">
    <?php foreach (['All','Pure Veg Mess','Non-Veg Special','Cloud Kitchens','Small Startups','Budget Friendly'] as $c): ?>
      <button type="submit" name="chip" value="<?= h($c) ?>" class="chip <?= $chip === $c ? 'active' : '' ?>"><?= h($c) ?></button>
    <?php endforeach; ?>
  </form>

  <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:6px">
    <input type="hidden" name="chip" value="<?= h($chip) ?>">
    <select name="area" onchange="this.form.submit()" style="padding:9px 14px;border-radius:20px;border:1.5px solid var(--line);font-size:.85rem">
      <option value="">All over Pune (every locality)</option>
      <?php mysqli_data_seek($areasRes, 0); while ($a = mysqli_fetch_assoc($areasRes)): ?>
        <option value="<?= h($a['location_area']) ?>" <?= $area === $a['location_area'] ? 'selected' : '' ?>><?= h($a['location_area']) ?></option>
      <?php endwhile; ?>
    </select>
    <?php if ($q || $area || $chip !== 'All'): ?>
      <a href="dashboard.php" style="font-size:.82rem;color:var(--coral);font-weight:600">Clear filters ✕</a>
    <?php endif; ?>
  </form>

  <div class="section-title">
    <h2 style="font-size:1.2rem;margin:0"><?= mysqli_num_rows($result) ?> results <?= $area ? 'in ' . h($area) : 'across Pune' ?></h2>
  </div>

  <div class="grid" id="messGrid">
    <?php if (mysqli_num_rows($result) === 0): ?>
      <p style="color:var(--ink-soft)">No mess found. Try clearing filters or searching another area.</p>
    <?php endif; ?>
    <?php while ($m = mysqli_fetch_assoc($result)): ?>
      <a href="mess_detail.php?id=<?= (int)$m['id'] ?>" class="mess-card">
        <div class="thumb">
          <img src="<?= h($m['cover_image']) ?>" alt="<?= h($m['name']) ?>" loading="lazy" onerror="this.onerror=null;this.src='/mealconnect/assets/images/food-thali-1.svg'">
          <span class="badge <?= badge_class($m['veg_type']) ?>"><?= h($m['veg_type']) ?></span>
          <span class="rating-pill">★ <?= h($m['rating']) ?></span>
        </div>
        <div class="body">
          <h3><?= h($m['name']) ?></h3>
          <div class="loc">📍 <?= h($m['location_area']) ?>, Pune · <?= h($m['type']) ?></div>
          <div class="tag-row">
            <?php foreach (array_slice(explode(',', $m['tags']), 0, 2) as $t): ?>
              <span class="tag"><?= h(trim($t)) ?></span>
            <?php endforeach; ?>
          </div>
          <div class="price-row">
            <div class="price">₹<?= number_format($m['price_per_month']) ?><span>/month</span></div>
            <span class="btn btn-sm btn-outline">View →</span>
          </div>
        </div>
      </a>
    <?php endwhile; ?>
  </div>

  <div class="section-title"><h2 style="font-size:1.2rem;margin:0">📍 All mess locations across Pune</h2></div>
  <div id="bigmap"></div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('bigmap').setView([18.5308, 73.8500], 11);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors', maxZoom: 18
}).addTo(map);

const gradIcon = L.divIcon({
  className: '',
  html: '<div style="width:26px;height:26px;border-radius:50% 50% 50% 0;background:linear-gradient(135deg,#e8620c,#ff3d68);transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,.3)"><span style="transform:rotate(45deg);font-size:12px">🍱</span></div>',
  iconSize:[26,26], iconAnchor:[13,26]
});

const messPoints = [
<?php
mysqli_data_seek($result, 0);
$allRes = mysqli_query($conn, "SELECT id, name, location_area, latitude, longitude, price_per_month FROM mess $whereSql");
while ($p = mysqli_fetch_assoc($allRes)):
?>
  {id:<?= (int)$p['id'] ?>, name:<?= json_encode($p['name']) ?>, area:<?= json_encode($p['location_area']) ?>, lat:<?= $p['latitude'] ?>, lng:<?= $p['longitude'] ?>, price:<?= (int)$p['price_per_month'] ?>},
<?php endwhile; ?>
];

messPoints.forEach(p => {
  L.marker([p.lat, p.lng], {icon: gradIcon}).addTo(map)
    .bindPopup(`<b>${p.name}</b><br>${p.area}, Pune<br>₹${p.price}/month<br><a href="mess_detail.php?id=${p.id}">View details →</a>`);
});
</script>
</body>
</html>
