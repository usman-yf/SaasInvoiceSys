<?php
// includes/header.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Avoid auth redirect on public auth pages
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page != 'login.php' && $current_page != 'verify.php' && $current_page != '2fa.php') {
    checkAuth();
}

$header_company_name = get_setting($conn, 'company_name', 'Invoice Management System');
$enable_preloader = get_setting($conn, 'enable_preloader', 'yes');
$preloader_duration = get_setting($conn, 'preloader_duration', '500');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?><?= htmlspecialchars($header_company_name) ?></title>
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%237c3aed' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolygon points='13 2 3 14 12 14 11 22 21 10 12 10 13 2'/%3E%3C/svg%3E" type="image/svg+xml">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            900: '#4c1d95',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(0, 0, 0, 0.05)',
                        'float': '0 8px 30px rgba(0, 0, 0, 0.08)',
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
    </script>
    <style>
        /* Premium Select Styling */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2224%22%20height%3D%2224%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%236b7280%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2em 1.2em;
            padding-right: 2.5rem !important;
        }
        
        .sidebar-minimized .sidebar-text {
            display: none;
        }
        .sidebar-minimized #sidebar {
            width: 5rem;
        }
        .sidebar-minimized #logo-text {
            display: none;
        }
        .sidebar-minimized #main-menu-label {
            display: none;
        }
        .sidebar-minimized #admin-label {
            display: none;
        }
        .sidebar-minimized nav a {
            justify-content: center;
            padding-left: 0;
            padding-right: 0;
        }
        .sidebar-minimized nav a i {
            margin-right: 0 !important;
        }
        .sidebar-minimized .logout-text {
            display: none;
        }
        .sidebar-minimized .logout-btn {
            justify-content: center;
        }
        .sidebar-minimized .logout-btn i {
            margin-right: 0 !important;
        }
    </style>
    <?php if ($enable_preloader === 'yes'): ?>
    <script>
        // Premium Preloader Logic
        window.addEventListener('load', function() {
            const preloader = document.getElementById('premium-preloader');
            if (preloader) {
                setTimeout(() => {
                    preloader.style.opacity = '0';
                    preloader.style.pointerEvents = 'none';
                    setTimeout(() => {
                        preloader.remove();
                    }, 500); // wait for fade transition
                }, <?= (int)$preloader_duration ?>); // minimum duration before hiding
            }
        });
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-50 text-gray-800 antialiased font-sans flex h-screen overflow-hidden">
<?php if ($enable_preloader === 'yes'): ?>
<!-- Premium Preloader -->
<div id="premium-preloader" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-gray-50 transition-opacity duration-500">
    <div class="relative flex items-center justify-center w-24 h-24 mb-6">
        <div class="absolute inset-0 border-4 border-brand-100 rounded-full"></div>
        <div class="absolute inset-0 border-4 border-brand-600 rounded-full border-t-transparent animate-spin" style="animation-duration: 1s;"></div>
        <div class="w-12 h-12 bg-brand-50 rounded-full flex items-center justify-center animate-pulse">
            <i data-lucide="zap" class="w-6 h-6 text-brand-600"></i>
        </div>
    </div>
    <div class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-brand-700 to-brand-500 tracking-wide animate-pulse"><?= htmlspecialchars($header_company_name) ?></div>
</div>
<?php endif; ?>
<?php if (isLoggedIn() && $current_page != 'login.php' && $current_page != 'verify.php'): ?>
    
    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white border-r border-gray-100 flex-shrink-0 flex flex-col hidden md:flex h-screen sticky top-0 transition-all duration-300 z-40">
        <div class="h-16 flex items-center px-6 border-b border-gray-100 transition-all">
            <div class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center mr-3 shadow-sm shrink-0">
                <i data-lucide="file-text" class="w-5 h-5 text-white"></i>
            </div>
            <span id="logo-text" class="text-xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-brand-700 to-brand-500 truncate">InvoicePro</span>
        </div>
        
        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto overflow-x-hidden">
            <p id="main-menu-label" class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 truncate">Main Menu</p>
            
            <a href="<?= BASE_URL ?>/index.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= ($current_page == 'index.php') ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3 shrink-0 <?= ($current_page == 'index.php') ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Dashboard</span>
            </a>
            
            <a href="<?= BASE_URL ?>/invoices/list.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/invoices/') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="receipt" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/invoices/') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Invoices</span>
            </a>
            
            <a href="<?= BASE_URL ?>/customers/list.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/customers/') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="users" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/customers/') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Customers</span>
            </a>
            
            <a href="<?= BASE_URL ?>/products/list.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/products/') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="package" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/products/') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Products & Services</span>
            </a>
            
            <a href="<?= BASE_URL ?>/reports.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/reports.php') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="bar-chart-2" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/reports.php') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Reports</span>
            </a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <p id="admin-label" class="px-2 text-xs font-semibold text-gray-400 uppercase tracking-wider mt-6 mb-2 truncate">Administration</p>
            <a href="<?= BASE_URL ?>/users/list.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/users/') !== false && strpos($_SERVER['REQUEST_URI'], 'profile.php') === false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="shield-check" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/users/') !== false && strpos($_SERVER['REQUEST_URI'], 'profile.php') === false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Users</span>
            </a>
            <a href="<?= BASE_URL ?>/settings.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/settings.php') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="settings" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/settings.php') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">Settings</span>
            </a>
            <a href="<?= BASE_URL ?>/logs.php" class="flex items-center px-3 py-2.5 rounded-xl transition-all duration-200 <?= (strpos($_SERVER['REQUEST_URI'], '/logs.php') !== false) ? 'bg-brand-50 text-brand-700 font-medium' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                <i data-lucide="activity" class="w-5 h-5 mr-3 shrink-0 <?= (strpos($_SERVER['REQUEST_URI'], '/logs.php') !== false) ? 'text-brand-600' : 'text-gray-400' ?>"></i>
                <span class="sidebar-text truncate">System Logs</span>
            </a>
            <?php endif; ?>
        </nav>
        
        <div class="p-4 border-t border-gray-100">
            <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-3 py-2.5 rounded-xl text-red-600 hover:bg-red-50 transition-all duration-200 logout-btn">
                <i data-lucide="log-out" class="w-5 h-5 mr-3 shrink-0"></i>
                <span class="font-medium logout-text truncate">Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="flex-1 flex flex-col min-w-0 bg-gray-50/50 overflow-y-auto relative">
        <!-- Header -->
        <header class="h-16 shrink-0 bg-white/30 backdrop-blur-md border-b border-brand-100/50 shadow-md shadow-brand-500/5 sticky top-0 z-30 flex items-center justify-between px-6 sm:px-8 transition-all">
            <div class="flex items-center">
                <!-- Sidebar Toggle Desktop -->
                <button id="desktopSidebarToggle" class="hidden md:flex mr-4 w-10 h-10 rounded-full items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors focus:outline-none">
                    <i data-lucide="menu" class="w-5 h-5"></i>
                </button>
                <!-- Mobile menu button -->
                <button class="md:hidden mr-4 text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <div class="relative hidden sm:block">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <input type="text" placeholder="Search anything..." class="pl-10 pr-4 py-2 bg-gray-50 border-none rounded-full text-sm focus:ring-2 focus:ring-brand-500/20 focus:bg-white w-64 transition-all outline-none">
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <div class="relative">
                    <button id="notificationToggle" class="w-10 h-10 rounded-full flex items-center justify-center text-gray-500 hover:bg-gray-100 transition-colors relative focus:outline-none">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <?php 
                        $user_id = $_SESSION['user_id'] ?? 0;
                        $notif_cnt_sql = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = $user_id AND is_read = 0");
                        $notif_cnt = $notif_cnt_sql ? mysqli_fetch_assoc($notif_cnt_sql)['cnt'] : 0;
                        if($notif_cnt > 0): ?>
                        <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-float border border-gray-100 z-50 overflow-hidden">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                            <h3 class="font-bold text-gray-900">Notifications</h3>
                            <?php if($notif_cnt > 0): ?>
                            <button onclick="markNotificationsRead()" class="text-xs text-brand-600 hover:text-brand-800 font-medium focus:outline-none">Mark all read</button>
                            <?php endif; ?>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <?php
                            $notif_res = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC LIMIT 5");
                            if($notif_res && mysqli_num_rows($notif_res) > 0):
                                while($notif = mysqli_fetch_assoc($notif_res)):
                                    $n_title = stripslashes(htmlspecialchars($notif['title']));
                                    $n_msg = stripslashes(htmlspecialchars($notif['message']));
                                    
                                    $n_icon = 'bell';
                                    $n_color = 'text-gray-500';
                                    $n_bg = 'bg-gray-100';
                                    
                                    $t_lower = strtolower($notif['title']);
                                    if(strpos($t_lower, 'payment') !== false) { 
                                        $n_icon = 'banknote'; $n_color = 'text-emerald-600'; $n_bg = 'bg-emerald-50'; 
                                    } elseif(strpos($t_lower, 'email') !== false) { 
                                        $n_icon = 'mail'; $n_color = 'text-purple-600'; $n_bg = 'bg-purple-50'; 
                                    } elseif(strpos($t_lower, 'invoice') !== false) { 
                                        $n_icon = 'receipt'; $n_color = 'text-brand-600'; $n_bg = 'bg-brand-50'; 
                                    } elseif(strpos($t_lower, 'customer') !== false) { 
                                        $n_icon = 'users'; $n_color = 'text-blue-600'; $n_bg = 'bg-blue-50'; 
                                    } elseif(strpos($t_lower, 'product') !== false) { 
                                        $n_icon = 'package'; $n_color = 'text-orange-600'; $n_bg = 'bg-orange-50'; 
                                    } elseif(strpos($t_lower, 'verification approved') !== false) { 
                                        $n_icon = 'shield-check'; $n_color = 'text-emerald-600'; $n_bg = 'bg-emerald-50'; 
                                    } elseif(strpos($t_lower, 'verification revoked') !== false) { 
                                        $n_icon = 'shield-alert'; $n_color = 'text-red-600'; $n_bg = 'bg-red-50'; 
                                    } elseif(strpos($t_lower, 'user') !== false || strpos($t_lower, 'login') !== false) { 
                                        $n_icon = 'user-cog'; $n_color = 'text-cyan-600'; $n_bg = 'bg-cyan-50'; 
                                    } elseif(strpos($t_lower, 'setting') !== false) { 
                                        $n_icon = 'settings'; $n_color = 'text-slate-600'; $n_bg = 'bg-slate-100'; 
                                    } elseif(strpos($t_lower, 'zatca') !== false) { 
                                        if (strpos($t_lower, 'cleared') !== false || strpos($t_lower, 'reported') !== false) {
                                            $n_icon = 'shield-check'; $n_color = 'text-green-600'; $n_bg = 'bg-green-50';
                                        } elseif (strpos($t_lower, 'rejected') !== false || strpos($t_lower, 'error') !== false) {
                                            $n_icon = 'shield-alert'; $n_color = 'text-red-600'; $n_bg = 'bg-red-50';
                                        } else {
                                            $n_icon = 'file-code'; $n_color = 'text-purple-600'; $n_bg = 'bg-purple-50';
                                        }
                                    }
                                    $notif_classes = $notif['is_read'] 
                                        ? 'opacity-75 bg-white hover:bg-gray-50 border-gray-50' 
                                        : 'unread-notif bg-brand-50 shadow-sm hover:bg-brand-100 border-brand-100';
                            ?>
                            <div id="notif-<?= $notif['id'] ?>" onclick="markSingleNotificationRead(<?= $notif['id'] ?>)" class="cursor-pointer p-4 border-b transition-colors flex items-start space-x-3 <?= $notif_classes ?>">
                                <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center <?= $n_bg ?>">
                                    <i data-lucide="<?= $n_icon ?>" class="w-4 h-4 <?= $n_color ?>"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 leading-tight"><?= $n_title ?></p>
                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed"><?= $n_msg ?></p>
                                    <p class="text-[10px] font-medium text-gray-400 mt-2 uppercase tracking-wider"><?= date('M d, h:i A', strtotime($notif['created_at'])) ?></p>
                                </div>
                            </div>
                            <?php endwhile; else: ?>
                            <div class="p-8 text-center text-gray-500 text-sm flex flex-col items-center">
                                <i data-lucide="bell-off" class="w-8 h-8 text-gray-300 mb-2"></i>
                                No notifications yet
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="h-8 w-px bg-gray-200 mx-2"></div>
                
                <div class="relative">
                    <button id="profileToggle" class="flex items-center space-x-3 hover:opacity-80 transition-opacity focus:outline-none">
                        <div class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-sm uppercase">
                            <?= substr(htmlspecialchars($_SESSION['username'] ?? 'U'), 0, 1) ?>
                        </div>
                        <div class="hidden md:block text-sm text-left">
                            <p class="font-medium text-gray-700 leading-none mb-1"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></p>
                            <p class="text-gray-500 text-xs leading-none capitalize"><?= htmlspecialchars($_SESSION['role'] ?? 'User') ?></p>
                        </div>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
                    </button>
                    
                    <!-- Profile Dropdown -->
                    <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-float border border-gray-100 z-50 overflow-hidden py-1">
                        <a href="<?= BASE_URL ?>/users/profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                            <i data-lucide="user" class="w-4 h-4 mr-3 text-gray-400"></i> Profile Settings
                        </a>
                        <div class="h-px bg-gray-100 my-1"></div>
                        <a href="<?= BASE_URL ?>/auth/logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                            <i data-lucide="log-out" class="w-4 h-4 mr-3 text-red-400"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Page Content -->
        <main class="flex-1 p-6 sm:p-8">
<?php endif; ?>
