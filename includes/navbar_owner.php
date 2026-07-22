<header class="navbar owner-topbar">
  <div class="container navbar-inner">
    <a href="/mealconnect/owner/dashboard.php" class="logo">
      <span class="mark">
        <svg viewBox="0 0 24 24" fill="none"><path d="M6 3v8a3 3 0 003 3v7M6 3v8M9 3v8M12 3v6a2 2 0 002 2h1v9m0-13V3" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </span>
      <span style="color:#fff">Meal<span class="grad">Connect</span> <small style="font-size:.6em;font-weight:600;color:#c8b8a6">OWNER</small></span>
    </a>
    <div style="flex:1"></div>
    <div style="color:#e8dccc;font-size:.88rem;font-weight:600"><?= h($_SESSION['owner_name'] ?? 'Owner') ?></div>
    <a href="/mealconnect/owner/dashboard.php" class="avatar"><?= h(initials($_SESSION['owner_name'] ?? 'O')) ?></a>
    <a href="/mealconnect/owner/logout.php" class="btn btn-sm btn-outline" style="background:transparent;color:#fff;border-color:#5a4a3d">Logout</a>
  </div>
</header>
