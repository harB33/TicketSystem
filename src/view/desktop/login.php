<?php
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';

    $sql = "SELECT user_id, password_hash FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => '?page=pickanartist']);
            exit();
        }
        header('Location: ?page=pickanartist');
        exit();
    } else {
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
            exit();
        }
        echo "<script>
        document.addEventListener('DOMContentLoaded', () => {
            const passwordInput = document.getElementById('desktop_password');
            if (passwordInput) {
                passwordInput.style.borderColor = '#ef4444';
                passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
            }
        });
        </script>";
    }
}
?>
<video autoplay muted loop playsinline class="absolute inset-0 w-full h-screen object-cover z-0">
    <source src="./asset/video/login.mp4" type="video/mp4">
</video>
<div class="absolute inset-0 bg-black/60 z-10"></div>
<div class="w-full h-full flex flex-col items-center justify-center relative z-10">
    <div class="bg-black/50 backdrop-blur-3xl w-[40%] h-[75%] border border-white/10 shadow-2xl flex flex-col rounded-2xl p-12">
        <logo class="h-[45%] w-full flex flex-col items-center justify-center p-12 pb-24">
            <img src="./asset/logo/logo.png" alt="Logo" class="min-w-48 max-w-48">
        </logo>
        <form action="" method="post" id="desktop_loginForm" data-ajax-form="true" class="flex flex-col w-full h-[55%] justify-start items-center px-12">
            <div class="flex flex-col items-center justify-start w-[85%] gap-4">
                <div class="w-full space-y-2">
                    <!-- <label for="email" class="text-xs font-bold uppercase tracking-widest text-zinc-400 ml-4">Email Address</label> -->
                    <input type="text" id="desktop_email" name="email" placeholder="email" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none placeholder:text-zinc-600">
                </div>
                <div class="w-full space-y-2">
                    <!-- <label for="password" class="text-xs font-bold uppercase tracking-widest text-zinc-400 ml-4">Password</label> -->
                    <input type="password" id="desktop_password" name="password" placeholder="password" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none placeholder:text-zinc-600">
                </div>
                <div class="flex items-center justify-between w-full">
                    <label class="flex items-center cursor-pointer group translate-x-6">
                        <div class="relative">
                            <input type="checkbox" checked="checked" class="peer hidden" />
                            <div class="w-5 h-5 border-2 border-white/20 rounded-full peer-checked:bg-primary peer-checked:border-primary transition-all duration-300"></div>
                            <svg class="absolute inset-0 w-5 h-5 text-black scale-0 peer-checked:scale-100 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="ml-3 text-sm font-medium text-zinc-400 group-hover:text-zinc-200 transition-colors">Remember me</span>
                    </label>
                    <button type="submit" class="px-8 py-3 bg-primary text-white rounded-full font-bold text-lg">
                        <span class="font-ballmer translate-y-0.5 inline-block">Sign in</span>
                    </button>
                </div>
                <div class="w-full h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                <p class="text-zinc-500 font-medium">Don't have an account? <a href="?page=register" class="text-primary hover:text-primary/80 hover:underline transition-all">Sign up</a></p>
            </div>
        </form>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('desktop_loginForm');
    const emailInput = document.getElementById('desktop_email');
    const passwordInput = document.getElementById('desktop_password');

    if (form && emailInput && passwordInput) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            let isValid = true;

            if (!emailInput.value.trim()) {
                emailInput.style.borderColor = '#ef4444';
                emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                isValid = false;
            } else {
                emailInput.style.borderColor = '';
                emailInput.style.boxShadow = '';
            }

            if (!passwordInput.value.trim()) {
                passwordInput.style.borderColor = '#ef4444';
                passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                isValid = false;
            } else {
                passwordInput.style.borderColor = '';
                passwordInput.style.boxShadow = '';
            }

            if (isValid) {
                const formData = new FormData(form);
                formData.append('ajax', 'true');

                fetch(window.location.href + (window.location.href.includes('?') ? '&' : '?') + 'ajax=true', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        emailInput.style.borderColor = '#ef4444';
                        emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                        passwordInput.style.borderColor = '#ef4444';
                        passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                        passwordInput.value = '';
                        passwordInput.blur();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });

        emailInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });

        passwordInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
    }
});
</script>