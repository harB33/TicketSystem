<?php
// Get the current page from GET parameter, default to 'login'
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

if (isset($isAjax) && $isAjax) {
    switch($page) {
        case 'register':
            include __DIR__ . '/desktop/register.php';
            break;
        case 'login':
        default:
            include __DIR__ . '/desktop/login.php';
            break;
    }
    exit();
}
?>

<?php if (!$isAjax): ?>
<mobile-view class="relative z-10 block lg:hidden w-full h-screen bg-transparent">
    <div class="w-full h-screen flex flex-col relative overflow-hidden">
        <?php
            switch($page) {
                case 'register':
                    include __DIR__ . '/mobile/register.php';
                    break;
                case 'pickanartist':
                    include __DIR__ . '/mobile/pickanartist.php';
                    break;
                case 'pickanarena':
                    include __DIR__ . '/mobile/pickanarena.php';
                    break;
                case 'featured':
                    include __DIR__ . '/mobile/featured.php';
                    break;
                case 'profile':
                    include __DIR__ . '/mobile/profile.php';
                    break;
                case 'mytickets':
                    include __DIR__ . '/mobile/mytickets.php';
                    break;
                case 'event':
                    include __DIR__ . '/mobile/event.php';
                    break;
                default:
                    include __DIR__ . '/mobile/login.php';
                    break;
            }
            
            // Only show navbar on specific pages if needed, but here we'll show it if it's not login/register
            if (!in_array($page, ['login', 'register','pickanartist','pickanarena'])) {
                include __DIR__ . '/mobile/navBar.html';
            }
        ?>
    </div>
</mobile-view>
<?php endif; ?>

<?php if (!$isAjax): ?>
<desktop-view class="relative z-10 hidden lg:block w-full h-screen bg-transparent">
<div class="w-full h-screen flex flex-col relative overflow-hidden">
        <?php
            switch($page) {
                case 'register':
                    include __DIR__ . '/desktop/register.php';
                    break;
                case 'pickanartist':
                    include __DIR__ . '/desktop/pickanartist.php';
                    break;
                case 'pickanarena':
                    include __DIR__ . '/desktop/pickanarena.php';
                    break;
                case 'featured':
                    include __DIR__ . '/desktop/featured.php';
                    break;
                case 'profile':
                    include __DIR__ . '/desktop/profile.php';
                    break;
                case 'mytickets':
                    include __DIR__ . '/desktop/mytickets.php';
                    break;
                // case 'event':
                //     include __DIR__ . '/desktop/event.php';
                //     break;
                default:
                    include __DIR__ . '/desktop/login.php';
                    break;
            }
            
            // Only show navbar on specific pages if needed, but here we'll show it if it's not login/register
            if (!in_array($page, ['login', 'register','pickanartist','pickanarena'])) {
                include __DIR__ . '/mobile/navBar.html';
            }
        ?>
    </div>
</desktop-view>
<?php endif; ?>
