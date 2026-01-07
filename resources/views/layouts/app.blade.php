<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistem Absensi">
    <meta name="author" content="">

    <title>@yield('title', 'Sistem Absensi')</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('css/sb-admin-2.min.css') }}" rel="stylesheet">
    <!-- DataTables -->
    <link href="{{ asset('vendor/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet">

    @stack('styles')
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
                <div class="sidebar-brand-text">Sistem Absensi</div>
            </a>

            <hr class="sidebar-divider my-0">

            <!-- Dashboard -->
            <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <hr class="sidebar-divider">

            <div class="sidebar-heading">Menu</div>

            <!-- Data Siswa -->
            @if(in_array(auth()->user()->role, ['admin', 'teacher']))
                <li class="nav-item {{ request()->routeIs('siswa.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('siswa.index') }}">
                        <i class="fas fa-users"></i>
                        <span>Data Siswa</span>
                    </a>
                </li>

                <!-- Data Guru -->
                <li class="nav-item {{ request()->routeIs('guru.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('guru.index') }}">
                        <i class="fas fa-user-tie"></i>
                        <span>Data Guru</span>
                    </a>
                </li>

                <!-- Broadcast WA -->
                <li class="nav-item {{ request()->routeIs('broadcast.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('broadcast.index') }}">
                        <i class="fab fa-whatsapp"></i>
                        <span>Broadcast WA</span>
                    </a>
                </li>
            @endif

            <!-- Absence Report (Admin Only) -->
            @if(auth()->user()->role === 'admin')
                <li class="nav-item {{ request()->routeIs('absence-report.*') ? 'active' : '' }}">
                    <a class="nav-link" href="{{ route('absence-report.index') }}">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Laporan Absensi</span>
                    </a>
                </li>
            @endif

            <!-- Absensi Collapse -->
            @php
                $isAbsensiActive = request()->routeIs('absensi.*') ||
                    request()->routeIs('absensi-guru.*') ||
                    request()->routeIs('rekap.*') ||
                    request()->routeIs('rekap-guru.*');
            @endphp
            <li class="nav-item {{ $isAbsensiActive ? 'active' : '' }}">
                <a class="nav-link {{ $isAbsensiActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse"
                    data-target="#collapseAbsensi" aria-expanded="true" aria-controls="collapseAbsensi">
                    <i class="fas fa-calendar-check"></i>
                    <span>Absensi</span>
                </a>
                <div id="collapseAbsensi" class="collapse {{ $isAbsensiActive ? 'show' : '' }}"
                    aria-labelledby="headingAbsensi" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <a class="collapse-item {{ request()->routeIs('absensi.*') ? 'active' : '' }}"
                            href="{{ route('absensi.index') }}">Absensi Siswa</a>
                        <a class="collapse-item {{ request()->routeIs('absensi-guru.*') ? 'active' : '' }}"
                            href="{{ route('absensi-guru.index') }}">Absensi Guru</a>
                        <a class="collapse-item {{ request()->routeIs('rekap.index') ? 'active' : '' }}"
                            href="{{ route('rekap.index') }}">Rekap Siswa</a>
                        <a class="collapse-item {{ request()->routeIs('rekap-guru.*') ? 'active' : '' }}"
                            href="{{ route('rekap-guru.index') }}">Rekap Guru</a>
                    </div>
                </div>
            </li>


            <!-- Master Data Collapse -->
            @if(auth()->user()->role === 'admin')
                @php
                    $isMasterActive = request()->routeIs('kelas.*') ||
                        request()->routeIs('mapel.*') ||
                        request()->routeIs('jadwal-pelajaran.*');
                @endphp
                <li class="nav-item {{ $isMasterActive ? 'active' : '' }}">
                    <a class="nav-link {{ $isMasterActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse"
                        data-target="#collapseMaster" aria-expanded="true" aria-controls="collapseMaster">
                        <i class="fas fa-database"></i>
                        <span>Master Data</span>
                    </a>
                    <div id="collapseMaster" class="collapse {{ $isMasterActive ? 'show' : '' }}"
                        aria-labelledby="headingMaster" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item {{ request()->routeIs('kelas.*') ? 'active' : '' }}"
                                href="{{ route('kelas.index') }}">Kelas</a>
                            <a class="collapse-item {{ request()->routeIs('mapel.*') ? 'active' : '' }}"
                                href="{{ route('mapel.index') }}">Mata Pelajaran</a>
                            <a class="collapse-item {{ request()->routeIs('jadwal-pelajaran.*') ? 'active' : '' }}"
                                href="{{ route('jadwal-pelajaran.index') }}">Jadwal Pelajaran</a>
                        </div>
                    </div>
                </li>

                <!-- Konfigurasi Collapse -->
                @php
                    $isKonfigurasiActive = request()->routeIs('jadwal.*') ||
                        request()->routeIs('hari-libur.*') ||
                        request()->routeIs('devices.*') ||
                        request()->routeIs('settings.*');
                @endphp
                <li class="nav-item {{ $isKonfigurasiActive ? 'active' : '' }}">
                    <a class="nav-link {{ $isKonfigurasiActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse"
                        data-target="#collapseKonfigurasi" aria-expanded="true" aria-controls="collapseKonfigurasi">
                        <i class="fas fa-cogs"></i>
                        <span>Konfigurasi</span>
                    </a>
                    <div id="collapseKonfigurasi" class="collapse {{ $isKonfigurasiActive ? 'show' : '' }}"
                        aria-labelledby="headingKonfigurasi" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item {{ request()->routeIs('jadwal.*') ? 'active' : '' }}"
                                href="{{ route('jadwal.index') }}">Jam Masuk/Pulang</a>
                            <a class="collapse-item {{ request()->routeIs('hari-libur.*') ? 'active' : '' }}"
                                href="{{ route('hari-libur.index') }}">Hari Libur</a>
                            <a class="collapse-item {{ request()->routeIs('devices.*') ? 'active' : '' }}"
                                href="{{ route('devices.index') }}">Device</a>
                            <a class="collapse-item {{ request()->routeIs('settings.*') ? 'active' : '' }}"
                                href="{{ route('settings.index') }}">Pengaturan Umum</a>
                        </div>
                    </div>
                </li>

                <!-- Sistem Collapse -->
                @php
                    $isSistemActive = request()->routeIs('users.*') ||
                        request()->routeIs('backups.*') ||
                        request()->routeIs('whatsapp-logs.*') ||
                        request()->routeIs('api-logs.*');
                @endphp
                <li class="nav-item {{ $isSistemActive ? 'active' : '' }}">
                    <a class="nav-link {{ $isSistemActive ? '' : 'collapsed' }}" href="#" data-toggle="collapse"
                        data-target="#collapseSistem" aria-expanded="true" aria-controls="collapseSistem">
                        <i class="fas fa-server"></i>
                        <span>Sistem</span>
                    </a>
                    <div id="collapseSistem" class="collapse {{ $isSistemActive ? 'show' : '' }}"
                        aria-labelledby="headingSistem" data-parent="#accordionSidebar">
                        <div class="bg-white py-2 collapse-inner rounded">
                            <a class="collapse-item {{ request()->routeIs('users.*') ? 'active' : '' }}"
                                href="{{ route('users.index') }}">Manajemen User</a>
                            <a class="collapse-item {{ request()->routeIs('backups.*') ? 'active' : '' }}"
                                href="{{ route('backups.index') }}">Backup & Restore</a>
                            <a class="collapse-item {{ request()->routeIs('whatsapp-logs.*') ? 'active' : '' }}"
                                href="{{ route('whatsapp-logs.index') }}">Log WhatsApp</a>
                            <a class="collapse-item {{ request()->routeIs('api-logs.*') ? 'active' : '' }}"
                                href="{{ route('api-logs.index') }}">Log API</a>
                        </div>
                    </div>
                </li>
            @endif

            <hr class="sidebar-divider d-none d-md-block">

            <!-- Logo at Bottom -->
            <div class="text-center d-none d-md-block mb-3">
                <img src="{{ asset('img/logo.png') }}" alt="Logo Sekolah" style="width: 80px; height: 80px; object-fit: contain; opacity: 0.9;">
                <div class="mt-2">
                    <small class="text-white-50">SMK Assuniyah Tumijajar</small>
                </div>
            </div>

            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 shadow">

                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h5 class="font-weight-bold mb-0 ml-3">Sistem Absensi SMK Assuniyah Tumijajar</h5>

                    <ul class="navbar-nav ml-auto">

                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    {{ auth()->user()->full_name }} ({{ ucfirst(auth()->user()->role) }})
                                </span>
                                <img class="img-profile rounded-circle"
                                    src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->full_name) }}">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    @yield('content')

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>&copy; {{ date('Y') }} SMK Assuniyah Tumijajar - developed with ❤️ by KangDaQiQ</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Yakin ingin keluar?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Yakin ingin keluar?</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('js/sb-admin-2.min.js') }}"></script>

    <!-- Page level plugins -->
    <script src="{{ asset('vendor/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <script>
        $(document).ready(function () {
            // Auto-close alerts after 3 seconds
            window.setTimeout(function () {
                $(".alert").fadeTo(500, 0).slideUp(500, function () {
                    $(this).remove();
                });
            }, 3000);
        });
    </script>

    @stack('scripts')

</body>

</html>