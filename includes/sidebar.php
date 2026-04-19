<?php
// includes/sidebar.php
// Usage: include with $activePage set to current page name
// e.g. $activePage = 'dashboard';
$role     = getUserRole();
$userName = getUserName();
$unread   = function_exists('getUnreadCount') ? getUnreadCount(getUserId()) : 0;

$navByRole = [
    'student' => [
        ['icon'=>'fa-home',        'label'=>'Dashboard',     'href'=>'student-dashboard.php', 'key'=>'dashboard'],
        ['icon'=>'fa-plus-circle', 'label'=>'New Request',   'href'=>'new-request.php',       'key'=>'new-request'],
        ['icon'=>'fa-list',        'label'=>'My Requests',   'href'=>'my-requests.php',       'key'=>'my-requests'],
        ['icon'=>'fa-bell',        'label'=>'Notifications', 'href'=>'notifications.php',     'key'=>'notifications', 'badge'=>true],
        ['icon'=>'fa-star',        'label'=>'Feedback',      'href'=>'feedback.php',          'key'=>'feedback'],
        ['icon'=>'fa-user',        'label'=>'Profile',       'href'=>'profile.php',           'key'=>'profile'],
        ['icon'=>'fa-sign-out-alt','label'=>'Logout',        'href'=>'../logout.php',         'key'=>''],
    ],
    'staff' => [
        ['icon'=>'fa-home',        'label'=>'Dashboard',     'href'=>'staff-dashboard.php',   'key'=>'dashboard'],
        ['icon'=>'fa-tasks',       'label'=>'My Tasks',      'href'=>'staff-requests.php',    'key'=>'staff-requests'],
        ['icon'=>'fa-bell',        'label'=>'Notifications', 'href'=>'notifications.php',     'key'=>'notifications', 'badge'=>true],
        ['icon'=>'fa-user',        'label'=>'Profile',       'href'=>'profile.php',           'key'=>'profile'],
        ['icon'=>'fa-sign-out-alt','label'=>'Logout',        'href'=>'../logout.php',         'key'=>''],
    ],
    'admin' => [
        ['icon'=>'fa-tachometer-alt','label'=>'Dashboard',    'href'=>'admin-dashboard.php',  'key'=>'dashboard'],
        ['icon'=>'fa-list-alt',      'label'=>'All Requests', 'href'=>'all-requests.php',     'key'=>'all-requests'],
        ['icon'=>'fa-user-check',    'label'=>'Assign',       'href'=>'assign-requests.php',  'key'=>'assign-requests'],
        ['icon'=>'fa-users',         'label'=>'Users',        'href'=>'manage-users.php',     'key'=>'manage-users'],
        ['icon'=>'fa-bell',          'label'=>'Notifications','href'=>'notifications.php',    'key'=>'notifications', 'badge'=>true],
        ['icon'=>'fa-chart-bar',     'label'=>'Reports',      'href'=>'reports.php',          'key'=>'reports'],
        ['icon'=>'fa-user-cog',      'label'=>'Profile',      'href'=>'profile.php',          'key'=>'profile'],
        ['icon'=>'fa-sign-out-alt',  'label'=>'Logout',       'href'=>'../logout.php',        'key'=>''],
    ],
];

$navKey  = in_array($role, ['department_admin','super_admin']) ? 'admin' : $role;
$navItems = $navByRole[$navKey] ?? $navByRole['student'];

$gradients = [
    'student' => 'linear-gradient(135deg,#1e3c72,#2a5298)',
    'staff'   => 'linear-gradient(135deg,#1a2a4a,#1e3c72)',
    'admin'   => 'linear-gradient(135deg,#1a1a2e,#1e3c72)',
];
$sidebarBg = $gradients[$navKey] ?? $gradients['student'];
$subtitle  = match($navKey) {
    'admin'  => ($role==='super_admin' ? 'Super Admin' : 'Dept. Admin'),
    'staff'  => 'Staff Member',
    default  => 'Student',
};
?>
<div class="sidebar" style="background:<?= $sidebarBg ?>">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../images/logo.jpeg" alt="Campus Sheba">
        </div>
        <h2><?= htmlspecialchars($userName) ?></h2>
        <p><?= $subtitle ?></p>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
        <a href="<?= $item['href'] ?>"
           class="<?= ($activePage ?? '') === $item['key'] ? 'active' : '' ?>">
            <i class="fas <?= $item['icon'] ?>"></i>
            <span>
                <?= $item['label'] ?>
                <?php if (!empty($item['badge']) && $unread > 0): ?>
                <span class="notif-count"><?= $unread ?></span>
                <?php endif; ?>
            </span>
        </a>
        <?php endforeach; ?>
    </nav>
</div>
