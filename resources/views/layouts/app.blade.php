@php
    use Carbon\Carbon;

    $now = Carbon::now();

    // ===== Active/Open states =====
    $onDashboard  = request()->routeIs('dashboard.index');

    // Only expand management menus on their own pages (not report pages)
    $indicatorOpen = request()->routeIs('indicators.*') && !request()->routeIs('indicators.report.*');
    $missionOpen   = request()->routeIs('missions.*')   && !request()->routeIs('missions.report.*');
    $reportOpen    = request()->routeIs('indicators.report.*') || request()->routeIs('missions.report.*');

    // ===== User chip =====
    $authUser = auth()->user();
    $displayName = $authUser->full_name ?? $authUser->username ?? 'User';
    $initials = mb_strtoupper(mb_substr(trim($displayName), 0, 1));
@endphp

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Trang quản trị')</title>

    <link href="{{ asset('backend/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/font-awesome/css/font-awesome.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/css/animate.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/css/custom.css') }}" rel="stylesheet">
    <link href="{{ asset('backend/css/plugins/select2/select2.min.css') }}" rel="stylesheet">

    @stack('styles')
    @vite(['resources/js/app.js'])

    <style>
        :root {
            --sidebar-w: 220px;
            --topbar-h: 64px;
            --footer-h: 48px;
            --bg: #f3f3f4;
            --sidebar-bg: #0b1220;
            --sidebar-bg-2: #0f1a2e;
            --sidebar-text: rgba(226, 232, 240, .92);
            --sidebar-muted: rgba(226, 232, 240, .62);
            --sidebar-border: rgba(148, 163, 184, .16);
            --accent: #38bdf8;
            --accent-2: #1ab394;
            --ring: rgba(56, 189, 248, .35);
        }

        /* ===== Modern Sidebar ===== */
        .navbar-default.navbar-static-side {
            background: radial-gradient(1200px 700px at -10% -10%, rgba(56, 189, 248, .18), transparent 55%),
                radial-gradient(900px 550px at 110% 30%, rgba(26, 179, 148, .14), transparent 55%),
                linear-gradient(180deg, var(--sidebar-bg), var(--sidebar-bg-2));
            border-right: 1px solid var(--sidebar-border);
        }

        .sidebar-collapse {
            scrollbar-width: thin;
            scrollbar-color: rgba(226, 232, 240, .22) transparent;
        }

        .sidebar-collapse::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-collapse::-webkit-scrollbar-thumb {
            background: rgba(226, 232, 240, .20);
            border-radius: 999px;
        }

        .sidebar-collapse::-webkit-scrollbar-thumb:hover {
            background: rgba(226, 232, 240, .28);
        }

        #side-menu .nav-header {
            background: transparent;
            border-bottom: 1px solid var(--sidebar-border);
        }

        #side-menu .profile-element strong {
            color: var(--sidebar-text);
            letter-spacing: .2px;
        }

        #side-menu .logo-element {
            color: var(--sidebar-text);
        }

        #side-menu>li>a {
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            color: var(--sidebar-text);
            font-weight: 650;
            border-radius: 14px;
            margin: 4px 10px;
            transition: transform .15s ease, background .15s ease, box-shadow .15s ease, color .15s ease;
            outline: none;
        }

        #side-menu>li>a i {
            width: 18px;
            text-align: center;
            color: rgba(226, 232, 240, .80);
            transition: transform .15s ease, color .15s ease;
        }

        #side-menu>li>a:hover {
            background: rgba(226, 232, 240, .08);
            box-shadow: 0 10px 24px rgba(2, 6, 23, .25);
            transform: translateY(-1px);
            text-decoration: none;
        }

        #side-menu>li>a:hover i {
            color: #e2e8f0;
            transform: translateX(1px);
        }

        #side-menu>li>a:focus-visible {
            box-shadow: 0 0 0 3px var(--ring);
        }

        /* Active item */
        #side-menu>li.active>a,
        #side-menu>li>a.active,
        #side-menu>li.mm-active>a {
            background: linear-gradient(135deg, rgba(56, 189, 248, .22), rgba(26, 179, 148, .14));
            border: 1px solid rgba(56, 189, 248, .20);
            box-shadow: 0 14px 30px rgba(2, 6, 23, .30);
        }

        #side-menu>li.active>a::before,
        #side-menu>li>a.active::before,
        #side-menu>li.mm-active>a::before {
            content: "";
            position: absolute;
            left: -10px;
            top: 10px;
            bottom: 10px;
            width: 4px;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            box-shadow: 0 0 0 2px rgba(56, 189, 248, .15);
        }

        /* Chevron cho item có submenu */
        #side-menu>li>a.has-submenu::after {
            content: "\f107";
            font-family: FontAwesome;
            margin-left: auto;
            color: rgba(226, 232, 240, .60);
            transition: transform .18s ease, color .18s ease;
        }

        #side-menu>li.mm-active>a.has-submenu::after,
        #side-menu>li.active>a.has-submenu::after {
            transform: rotate(-180deg);
            color: rgba(226, 232, 240, .90);
        }

        /* ===== Submenu (PATCH FIX DEV: always visible block, hide by max-height) ===== */
        #side-menu .nav-second-level {
            margin: 0 10px 8px;
            border-radius: 14px;
            background: rgba(2, 6, 23, .12);
            border: 1px solid var(--sidebar-border);
            overflow: hidden;
            transform-origin: top;

            /* QUAN TRỌNG: tránh app.css (Vite dev) ghi đè display:none */
            display: block !important;

            max-height: 0;
            opacity: 0;
            transform: translateY(-2px);
            transition: max-height .22s ease, opacity .18s ease, transform .18s ease;
            will-change: max-height, opacity, transform;
        }

        /* OPEN (metisMenu newer) */
        #side-menu .nav-second-level.mm-show {
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
        }

        /* OPEN (bootstrap/metisMenu older) */
        #side-menu .nav-second-level.in {
            max-height: 1200px;
            opacity: 1;
            transform: translateY(0);
        }

        #side-menu .nav-second-level>li>a {
            display: flex;
            align-items: center;
            padding: 10px 14px 10px 44px;
            color: var(--sidebar-muted);
            font-weight: 600;
            transition: background .15s ease, color .15s ease, transform .15s ease;
        }

        #side-menu .nav-second-level>li>a:hover {
            background: rgba(226, 232, 240, .08);
            color: var(--sidebar-text);
            transform: translateX(2px);
            text-decoration: none;
        }

        #side-menu .nav-second-level>li.active>a {
            color: var(--sidebar-text);
            background: rgba(56, 189, 248, .12);
        }

        /* ===== Mini navbar ===== */
        body.mini-navbar .navbar-static-side {
            width: 70px;
        }

        body.mini-navbar #page-wrapper {
            margin-left: 70px;
        }

        body.mini-navbar #side-menu>li>a {
            justify-content: center;
            gap: 0;
            margin: 6px 8px;
        }

        body.mini-navbar #side-menu>li>a .nav-label,
        body.mini-navbar #side-menu>li>a.has-submenu::after {
            display: none;
        }

        body.mini-navbar #side-menu .nav-second-level {
            display: none !important;
        }

        /* ===== Mobile overlay backdrop ===== */
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, .45);
            backdrop-filter: blur(2px);
            z-index: 1400;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
        }

        body.sidebar-open .sidebar-backdrop {
            opacity: 1;
            pointer-events: auto;
        }

        /* Sidebar fixed */
        .navbar-static-side {
            position: fixed !important;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-w);
            overflow: hidden;
            z-index: 2000;
        }

        .sidebar-collapse {
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            padding-bottom: 24px;
        }

        /* ===== Layout base ===== */
        #page-wrapper {
            margin-left: var(--sidebar-w);
            position: relative;
            min-height: 100vh;
            padding-top: var(--topbar-h);
            padding-bottom: var(--footer-h);
        }

        body.mini-navbar #page-wrapper {
            margin-left: 70px;
        }

        @media (max-width: 992px) {
            #page-wrapper {
                margin-left: 0 !important;
            }
        }

        .gray-bg {
            background: linear-gradient(180deg, #f8fafc, #f3f3f4);
        }

        /* ===== Premium Topbar ===== */
        .topbar-nav {
            position: fixed !important;
            top: 0;
            left: var(--sidebar-w);
            right: 0;
            height: var(--topbar-h);
            z-index: 1500;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
        }

        body.mini-navbar .topbar-nav {
            left: 70px;
        }

        @media (max-width: 992px) {
            .topbar-nav {
                left: 0 !important;
            }
        }

        .topbar-inner {
            width: 100%;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
            flex-wrap: wrap;
        }

        .topbar-toggle {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.10);
            color: #0f172a;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.08);
        }

        .topbar-toggle:hover {
            background: #f8fafc;
        }

        .topbar-welcome {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .topbar-kicker {
            font-size: 12px;
            color: #64748b;
            letter-spacing: .2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border: 1px solid rgba(15, 23, 42, 0.10);
            border-radius: 999px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(2, 6, 23, 0.06);
        }

        .user-chip .avatar {
            width: 30px;
            height: 30px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #1ab394;
            color: #fff;
            font-weight: 800;
            font-size: 12px;
            flex: 0 0 30px;
        }

        .user-chip .name {
            max-width: 240px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 650;
            color: #0f172a;
        }

        .topbar-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(56, 189, 248, .10);
            border: 1px solid rgba(56, 189, 248, .22);
            color: #0369a1;
            font-weight: 650;
            text-decoration: none;
        }

        .topbar-action:hover {
            background: rgba(56, 189, 248, .14);
            text-decoration: none;
        }

        .topbar-logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.18);
            color: #b91c1c;
            font-weight: 650;
            text-decoration: none;
        }

        .topbar-logout:hover {
            background: rgba(239, 68, 68, 0.12);
            text-decoration: none;
        }

        .wrapper-content {
            padding-top: 16px;
            min-height: calc(100vh - var(--topbar-h) - var(--footer-h));
        }

        @media (max-width: 768px) {
            .topbar-kicker { display: none; }
            .user-chip .name { display: none; }
        }

        /* Responsive: màn nhỏ sidebar overlay */
        @media (max-width: 992px) {
            .navbar-static-side {
                position: fixed !important;
                transform: translateX(-100%);
                transition: transform .2s ease;
            }

            body.mini-navbar .navbar-static-side {
                transform: translateX(0);
            }

            body.sidebar-open .navbar-static-side {
                transform: translateX(0);
            }

            #page-wrapper {
                margin-left: 0 !important;
            }
        }
    
        /* ===== Fixed Footer ===== */
        .footer {
            position: fixed !important;
            left: var(--sidebar-w);
            right: 0;
            bottom: 0;
            height: var(--footer-h);
            line-height: var(--footer-h);
            padding: 0 16px;
            margin: 0 !important;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            z-index: 1490;
        }

        body.mini-navbar .footer {
            left: 70px;
        }

        @media (max-width: 992px) {
            .footer {
                left: 0 !important;
            }
        }

        .footer .pull-right {
            line-height: var(--footer-h);
        }


        /* SweetAlert2 toast: tránh bị header/footer fixed che */
        .swal2-container.swal2-bottom-end.swal2-backdrop-show,
        .swal2-container.swal2-bottom-end.swal2-noanimation {
            bottom: calc(var(--footer-h) + 16px) !important;
            right: 16px !important;
            z-index: 99999 !important;
        }

        .swal2-container.swal2-top-end.swal2-backdrop-show,
        .swal2-container.swal2-top-end.swal2-noanimation {
            top: calc(var(--topbar-h) + 16px) !important;
            right: 16px !important;
            z-index: 99999 !important;
        }

        .swal2-toast {
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18) !important;
        }

    </style>
</head>

<body>
<div id="wrapper">

    {{-- Backdrop cho mobile sidebar --}}
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    {{-- SIDEBAR --}}
    <nav class="navbar-default navbar-static-side" role="navigation">
        <div class="sidebar-collapse">
            <ul class="nav metismenu" id="side-menu">
                <li class="nav-header">
                    <div class="profile-element text-center">
                        <img class="img-circle" src="">
                        <div class="m-t-xs">
                            <strong class="font-bold">IOC Tracker</strong>
                        </div>
                    </div>
                    <div class="logo-element">IOC</div>
                </li>

                <li class="{{ request()->routeIs('dashboard.overview') ? 'active' : '' }}">
                    <a href="{{ route('dashboard.overview') }}">
                        <i class="fa fa-home"></i>
                        <span class="nav-label">Trang chủ</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('agencies.manage') ? 'active' : '' }}">
                    <a href="{{ route('agencies.manage') }}">
                        <i class="fa fa-sitemap"></i>
                        <span class="nav-label">Quản lý Cơ quan</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <a href="{{ route('users.index') }}">
                        <i class="fa fa-users"></i>
                        <span class="nav-label">Quản lý Người dùng</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('resolutions.*') ? 'active' : '' }}">
                    <a href="{{ route('resolutions.index') }}">
                        <i class="fa fa-file-text-o"></i>
                        <span class="nav-label">Quản lý Văn bản</span>
                    </a>
                </li>

                <li class="{{ request()->routeIs('delay-reasons.*') ? 'active' : '' }}">
                    <a href="{{ route('delay-reasons.index') }}">
                        <i class="fa fa-clock-o"></i>
                        <span class="nav-label">Quản lý Nguyên nhân Trễ</span>
                    </a>
                </li>

                {{-- Quản lý Chỉ tiêu --}}
                <li class="{{ $indicatorOpen ? 'active mm-active' : '' }}">
                    <a href="#" class="has-submenu" aria-expanded="{{ $indicatorOpen ? 'true' : 'false' }}">
                        <i class="fa fa-bullseye"></i>
                        <span class="nav-label">Quản lý Chỉ tiêu</span>
                    </a>
                    <ul class="nav nav-second-level mm-collapse {{ $indicatorOpen ? 'mm-show' : '' }}"
                        aria-expanded="{{ $indicatorOpen ? 'true' : 'false' }}">
                        @foreach($resolutions as $resolution)
                            @php
                                $isActive = request()->routeIs('indicators.index')
                                            && optional(request('resolution'))->id == $resolution->id;
                            @endphp
                            <li class="{{ $isActive ? 'active' : '' }}">
                                <a href="{{ route('indicators.index', $resolution) }}">
                                    {{ $resolution->resolution_code }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                {{-- Quản lý Nhiệm vụ --}}
                <li class="{{ $missionOpen ? 'active mm-active' : '' }}">
                    <a href="#" class="has-submenu" aria-expanded="{{ $missionOpen ? 'true' : 'false' }}">
                        <i class="fa fa-tasks"></i>
                        <span class="nav-label">Quản lý Nhiệm vụ</span>
                    </a>
                    <ul class="nav nav-second-level mm-collapse {{ $missionOpen ? 'mm-show' : '' }}"
                        aria-expanded="{{ $missionOpen ? 'true' : 'false' }}">
                        @foreach($resolutions as $resolution)
                            @php
                                $isActive = request()->routeIs('missions.index')
                                            && optional(request('resolution'))->id == $resolution->id;
                            @endphp
                            <li class="{{ $isActive ? 'active' : '' }}">
                                <a href="{{ route('missions.index', $resolution) }}">
                                    {{ $resolution->resolution_code }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

                {{-- Thống kê --}}
                <li class="{{ $reportOpen ? 'active mm-active' : '' }}">
                    <a href="#" class="has-submenu" aria-expanded="{{ $reportOpen ? 'true' : 'false' }}">
                        <i class="fa fa-bar-chart"></i>
                        <span class="nav-label">Thống kê</span>
                    </a>
                    <ul class="nav nav-second-level mm-collapse {{ $reportOpen ? 'mm-show' : '' }}"
                        aria-expanded="{{ $reportOpen ? 'true' : 'false' }}">
                        <li class="{{ request()->routeIs('indicators.report.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('indicators.report.dashboard', default_report_params('indicator')) }}">
                                Thống kê Chỉ tiêu
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('missions.report.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('missions.report.dashboard', default_report_params('mission')) }}">
                                Thống kê Nhiệm vụ
                            </a>
                        </li>
                    </ul>
                </li>

            </ul>
        </div>
    </nav>

    {{-- PAGE --}}
    <div id="page-wrapper" class="gray-bg">

        {{-- TOP BAR --}}
        <div class="row border-bottom">
            <nav class="navbar navbar-static-top white-bg topbar-nav" role="navigation">
                <div class="topbar-inner">

                    <div class="topbar-left">
                        <a class="navbar-minimalize minimalize-styl-2 topbar-toggle" href="#">
                            <i class="fa fa-bars"></i>
                        </a>

                        <div class="topbar-welcome">
                            <div class="topbar-kicker">Chào mừng bạn đến trang quản trị</div>
                        </div>
                    </div>

                    <div class="topbar-right">
                        <div class="user-chip">
                            <div class="avatar">{{ $initials }}</div>
                            <div class="name" title="{{ $displayName }}">{{ $displayName }}</div>
                        </div>

                        <a class="topbar-action" href="{{ route('users.password.form') }}">
                            <i class="fa fa-key"></i> Đổi mật khẩu
                        </a>

                        <a class="topbar-logout" href="#"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fa fa-sign-out"></i> Đăng xuất
                        </a>

                        <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>

                </div>
            </nav>
        </div>

        {{-- CONTENT --}}
        <div class="wrapper wrapper-content">
            @yield('content')
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <div class="pull-right">Sở Khoa học và Công nghệ tỉnh Khánh Hòa</div>
            <div><strong>Copyright</strong> © 2026</div>
        </div>

    </div>
</div>

<script src="{{ asset('backend/js/jquery-3.1.1.min.js') }}"></script>
<script src="{{ asset('backend/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('backend/js/plugins/metisMenu/jquery.metisMenu.js') }}"></script>
<script src="{{ asset('backend/js/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
<script src="{{ asset('backend/js/inspinia.js') }}"></script>
<script src="{{ asset('backend/js/plugins/pace/pace.min.js') }}"></script>
<script src="{{ asset('backend/js/plugins/select2/select2.min.js') }}"></script>

@stack('scripts')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ===== Toast Thông Báo (góc dưới bên phải) =====
    document.addEventListener('DOMContentLoaded', function() {
        @if(session('success'))
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: 'Thành công!',
                text: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        @endif

        @if(session('error'))
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'error',
                title: 'Lỗi!',
                text: "{{ session('error') }}",
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true
            });
        @endif

        @if(session('warning'))
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'warning',
                title: 'Cảnh báo!',
                text: "{{ session('warning') }}",
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        @endif

        @if(session('info'))
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'info',
                title: 'Thông tin',
                text: "{{ session('info') }}",
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        @endif
    });
</script>

<script>
    (function () {
        const $body = $('body');
        const $backdrop = $('.sidebar-backdrop');
        const $sideMenu = $('#side-menu');

        // PATCH: KHÔNG khởi tạo metisMenu ở đây nữa (inspinia.js đã làm)
        // Chỉ làm các phần phụ trợ: chevron + backdrop + reset dashboard

        // Ensure chevron appears on items having submenu
        $sideMenu.find('> li').each(function () {
            const $li = $(this);
            const $a = $li.children('a');
            const $sub = $li.children('ul.nav-second-level');
            if ($sub.length) {
                $a.addClass('has-submenu');
            }
        });

        // If on dashboard and you want to keep all submenus collapsed
        const isDashboard = @json(request()->routeIs('dashboard.index'));
        if (isDashboard) {
            $sideMenu.find('li').removeClass('active mm-active');
            $sideMenu.find('ul.nav-second-level').removeClass('mm-show in');
            $sideMenu.find('a[aria-expanded]').attr('aria-expanded', 'false');
        }

        function isMobileScreen() {
            return window.matchMedia('(max-width: 992px)').matches;
        }

        function isSidebarOpened() {
            return $body.hasClass('mini-navbar');
        }

        function syncBackdrop() {
            $body.toggleClass('sidebar-open', isMobileScreen() && isSidebarOpened());
        }

        $(document).on('click', '.navbar-minimalize', function () {
            setTimeout(syncBackdrop, 0);
        });

        $backdrop.on('click', function () {
            if (!isMobileScreen()) return;
            $body.removeClass('mini-navbar');
            syncBackdrop();
        });
 
        window.addEventListener('resize', syncBackdrop, { passive: true });
        syncBackdrop();
    })();
</script>

</body>
</html>