<header class="navbar">
  <div class="container navbar-inner">
    <a href="/mealconnect/student/dashboard.php" class="logo">
      <span class="mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 3v8a3 3 0 003 3v7M6 3v8M9 3v8M12 3v6a2 2 0 002 2h1v9m0-13V3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      Meal<span class="grad">Connect</span>
    </a>

    <form class="searchbar" action="/mealconnect/student/dashboard.php" method="get">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b584a" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      <input type="text" name="q" placeholder="Search mess, cloud kitchen or area..." value="<?= h($_GET['q'] ?? '') ?>">
    </form>

    <div class="location-pill">
      📍 <?= h($_GET['area'] ?? 'All over Pune') ?>
    </div>

    <div class="nav-icon" title="Notifications">
      🔔<span class="dot"></span>
    </div>

    <a href="/mealconnect/student/profile.php" class="avatar" title="<?= h($_SESSION['student_name'] ?? 'Student') ?>">
      <?= h(initials($_SESSION['student_name'] ?? 'S')) ?>
    </a>
  </div>
</header>
