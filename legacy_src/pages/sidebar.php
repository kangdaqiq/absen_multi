<?php
// sidebar.php
// Letakkan file ini di include/ atau langsung echo di layout utama.
// Otomatis menambahkan class `active` pada menu yang sedang dibuka

$current_page = basename($_SERVER['PHP_SELF']);

/**
 * Helper kecil untuk menentukan kelas active / collapsed / show
 * - is_active(mixed $pages): 'active' or ''
 * - collapse_show(mixed $pages): 'show' or '' (untuk div.collapse)
 * - collapsed_class(mixed $pages): '' or 'collapsed' (untuk a.nav-link)
 * - aria_expanded(mixed $pages): 'true' or 'false'
 * Parameter $pages bisa string (satu file) atau array (beberapa file)
 */
function is_active($pages) {
    global $current_page;
    if (is_array($pages)) return in_array($current_page, $pages) ? 'active' : '';
    return $current_page === $pages ? 'active' : '';
}

function collapse_show($pages) {
    global $current_page;
    if (is_array($pages)) return in_array($current_page, $pages) ? 'show' : '';
    return $current_page === $pages ? 'show' : '';
}

function collapsed_class($pages) {
    global $current_page;
    if (is_array($pages)) return in_array($current_page, $pages) ? '' : 'collapsed';
    return $current_page === $pages ? '' : 'collapsed';
}

function aria_expanded($pages) {
    global $current_page;
    if (is_array($pages)) return in_array($current_page, $pages) ? 'true' : 'false';
    return $current_page === $pages ? 'true' : 'false';
}

// Kelompok halaman untuk collapse
// $enroll_pages deleted
$absensi_pages = ['absensi.php', 'rekap.php'];
$pengaturan_pages = ['kelas.php', 'devices.php', 'jadwal.php'];
?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand (Large Logo Only) -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
        <div class="sidebar-brand-icon d-flex align-items-center justify-content-center"
             style="width: 70px; height: 70px;">
            <img src="../img/logo.png"
                 alt="Logo"
                 style="width: 100%; height: auto;">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item <?= is_active('dashboard.php') ?>">
        <a class="nav-link" href="dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">Menu</div>

    <li class="nav-item <?= is_active('data-siswa.php') ?>">
        <a class="nav-link" href="data-siswa.php">
            <i class="fas fa-users"></i>
            <span>Data Siswa</span>
        </a>
    </li>

    <li class="nav-item <?= is_active('data-guru.php') ?>">
        <a class="nav-link" href="data-guru.php">
            <i class="fas fa-user-tie"></i>
            <span>Data Guru</span>
        </a>
    </li>

<!-- Absensi collapse -->
<li class="nav-item <?= is_active($absensi_pages) ?>">
  <a class="nav-link <?= collapsed_class($absensi_pages) ?>" href="#" data-toggle="collapse" data-target="#collapseAbsensi"
     aria-expanded="<?= aria_expanded($absensi_pages) ?>" aria-controls="collapseAbsensi">
    <i class="fas fa-calendar-check"></i>
    <span>Absensi</span>
  </a>

  <div id="collapseAbsensi" class="collapse <?= collapse_show($absensi_pages) ?>" aria-labelledby="headingAbsensi" data-parent="#accordionSidebar">
    <div class="bg-white py-2 collapse-inner rounded">
      <a class="collapse-item <?= is_active('absensi.php') ?>" href="absensi.php">Absensi</a>
      <a class="collapse-item <?= is_active('rekap.php') ?>" href="rekap.php">Rekap Absensi</a>
    </div>
  </div>
</li>



    <!-- Pengaturan collapse -->
    <li class="nav-item <?= is_active($pengaturan_pages) ?>">
      <a class="nav-link <?= collapsed_class($pengaturan_pages) ?>" href="#" data-toggle="collapse" data-target="#collapsePengaturan"
         aria-expanded="<?= aria_expanded($pengaturan_pages) ?>" aria-controls="collapsePengaturan">
        <i class="fas fa-cogs"></i>
        <span>Pengaturan</span>
      </a>
      <div id="collapsePengaturan" class="collapse <?= collapse_show($pengaturan_pages) ?>" aria-labelledby="headingPengaturan" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
          <a class="collapse-item <?= is_active('kelas.php') ?>" href="kelas.php">Manajemen Kelas</a>
          <a class="collapse-item <?= is_active('devices.php') ?>" href="devices.php">Manajemen Device</a>
          <a class="collapse-item <?= is_active('jadwal.php') ?>" href="jadwal.php">Jam Masuk / Pulang</a>
        </div>
      </div>
    </li>


    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End Sidebar -->
