<?php
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql = "SELECT user_id, password_hash FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        if ($isAjax) {
            echo json_encode(['success' => true]);
            exit();
        } else {
            header('Location: ?page=pickanartist');
            exit();
        }
    } else {
        if ($isAjax) {
            echo json_encode(['error' => 'Invalid email or password']);
            exit();
        } else {
            echo "<script>alert('Invalid email or password!');</script>";
            echo "<script>
            document.addEventListener('DOMContentLoaded', () => {
                const passwordInput = document.getElementById('password');
                if (passwordInput) {
                    passwordInput.style.outline = '2px solid #ef4444';
                }
            });
            </script>";
        }
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
            <button type="submit" class=" p-2 border border-primary rounded-full w-1/2 bg-primary"><p class="font-ballmer text-lg translate-y-1">sign in</p></button>
        </div>
        <p class="opacity-75">Don't have an account? <a href="?page=register" class="text-primary">Sign up</a></p>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (form && emailInput && passwordInput) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;

            if (!emailInput.value.trim()) {
                emailInput.style.outline = '2px solid #ef4444';
                isValid = false;
            } else {
                emailInput.style.outline = 'none';
            }

            if (!passwordInput.value.trim()) {
                passwordInput.style.outline = '2px solid #ef4444';
                isValid = false;
            } else {
                passwordInput.style.outline = 'none';
            }

            if (!isValid) {
                return;
            }

            const formData = new FormData(form);
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '?page=pickanartist';
                } else {
                    emailInput.style.outline = '2px solid #ef4444';
                    passwordInput.style.outline = '2px solid #ef4444';
                }
            })
            .catch(error => console.error('Error:', error));
        });

        emailInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.outline = 'none';
            }
        });

        passwordInput.addEventListener('input', function() {
            if (this.value.trim()) {
                this.style.outline = 'none';
            }
        });
    }
});
</script>