<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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
            echo json_encode(['success' => true, 'redirect' => '?page=featured']);
            exit();
        }
        header('Location: ?page=featured');
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
            const passwordInput = document.getElementById('password');
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
<div class="absolute inset-0 bg-black/75 z-5"></div>
<logo class="relative z-10 h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./asset/logo/logo.png" alt="" class="w-1/2">
</logo>
<form action="" method="post" id="loginForm" class="relative z-10 flex flex-col h-[50%] w-full justify-center items-center">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" id="email" name="email" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg text-[#525252] font-bold bg-[#919191]">
        <input type="password" id="password" name="password" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg text-[#525252] font-bold bg-[#919191]">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center">
                <input type="checkbox" checked="checked" class="checkbox border checkbox-primary rounded-full" style="border-radius: 100% !important; box-shadow: none !important;" />
                <span class="ml-2 text-sm opacity-75">Remember me</span>
            </div>
            <button type="submit" id="mobile_loginBtn" class=" p-2 border border-primary rounded-full w-1/2 bg-primary"><p class="font-ballmer text-lg translate-y-1">sign in</p></button>
        </div>
        <p class="opacity-75">Don't have an account? <a href="?page=register" class="text-primary">Sign up</a></p>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    const shakeEl = (el) => {
        el.animate([
            { transform: 'translateX(0)' },
            { transform: 'translateX(-7px)' },
            { transform: 'translateX(7px)' },
            { transform: 'translateX(-5px)' },
            { transform: 'translateX(5px)' },
            { transform: 'translateX(0)' }
        ], { duration: 400, easing: 'ease' });
    };

    if (form && emailInput && passwordInput) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const submitBtn = document.getElementById('mobile_loginBtn');
            const shakeBtn = () => shakeEl(submitBtn);
            let isValid = true;

            if (!emailInput.value.trim()) {
                emailInput.style.borderColor = '#ef4444';
                emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                isValid = false;
            } else {
                emailInput.style.borderColor = '';
                emailInput.style.boxShadow = '';
            }

            if (!isValid) {
                shakeBtn();
                return;
            }

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
                    passwordInput.style.borderColor = '#ef4444';
                    passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                    passwordInput.value = '';
                    passwordInput.blur();
                    shakeBtn();
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
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