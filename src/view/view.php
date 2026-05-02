<mobile-view class="relative z-10 block lg:hidden w-full h-screen bg-transparent">


    <!-- Login Page -->
    <login id="loginPage" class="active">
        <div class="w-full h-screen flex flex-col">
            <?php include __DIR__ . '/mobile/login.php'; ?>
        </div>
    </login>


    <!-- Register Page -->
    <register id="registerPage" class="hidden">
        <div class="w-full h-screen flex flex-col">
            <?php include __DIR__ . '/mobile/register.php'; ?>
        </div>
    </register>

    <!-- Pick an Artist Page -->
    <pickanartist id="pickAnArtistPage" class="hidden">
        <div class="w-full h-screen flex flex-col">
            <?php include __DIR__ . '/mobile/pickanartist.php'; ?>
        </div>
    </pickanartist>

</mobile-view>
<desktop-view>

</desktop-view>

<style>
    login, register, pickanartist {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    login.hidden, register.hidden, pickanartist.hidden {
        display: none !important;
    }
    
    login.active, register.active, pickanartist.active {
        display: flex !important;
    }
</style>

<script>
    function showPage(pageName) {
        const loginPage = document.getElementById('loginPage');
        const registerPage = document.getElementById('registerPage');
        const pickAnArtistPage = document.getElementById('pickAnArtistPage');

        if (pageName === 'login') {
            loginPage.classList.remove('hidden');
            loginPage.classList.add('active');
            registerPage.classList.add('hidden');
            registerPage.classList.remove('active');
            pickAnArtistPage.classList.add('hidden');
            pickAnArtistPage.classList.remove('active');
            window.history.replaceState({ page: 'login' }, 'Login', window.location.pathname.split('/').slice(0, -1).join('/') + '/login');
        } else if (pageName === 'register') {
            registerPage.classList.remove('hidden');
            registerPage.classList.add('active');
            loginPage.classList.add('hidden');
            loginPage.classList.remove('active');
            pickAnArtistPage.classList.add('hidden');
            pickAnArtistPage.classList.remove('active');
            window.history.replaceState({ page: 'register' }, 'Register', window.location.pathname.split('/').slice(0, -1).join('/') + '/register');
        } else if (pageName === 'pickAnArtist') {
            pickAnArtistPage.classList.remove('hidden');
            pickAnArtistPage.classList.add('active');
            loginPage.classList.add('hidden');
            loginPage.classList.remove('active');
            registerPage.classList.add('hidden');
            registerPage.classList.remove('active');
            window.history.replaceState({ page: 'pickAnArtist' }, 'Pick an Artist', window.location.pathname.split('/').slice(0, -1).join('/') + '/pick-an-artist');
        }
    }
    
    // Handle back/forward button
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.page) {
            showPage(event.state.page);
        }
    });
</script>
