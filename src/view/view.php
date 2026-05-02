<?php
// Get the current page from GET parameter, default to 'login'
$page = isset($_GET['page']) ? $_GET['page'] : 'login';
?>

<mobile-view class="relative z-10 block lg:hidden w-full h-screen bg-transparent">
    <div class="w-full h-screen flex flex-col">
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
                default:
                    include __DIR__ . '/mobile/login.php';
                    break;
            }
        ?>
    </div>
</mobile-view>

<desktop-view>
</desktop-view>
