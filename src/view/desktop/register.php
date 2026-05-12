<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$desktop_passwordStrength = 0;
$desktop_strengthMessage = '';
$desktop_showStrengthBars = false;

if (!function_exists('desktop_checkPasswordStrength')) {
    function desktop_checkPasswordStrength($password) {
        $strength = 0;
        
        // Check length
        if (strlen($password) >= 8) $strength++;
        if (strlen($password) >= 12) $strength++;
        
        // Check for lowercase
        if (preg_match('/[a-z]/', $password)) $strength++;
        
        // Check for uppercase
        if (preg_match('/[A-Z]/', $password)) $strength++;
        
        // Check for numbers
        if (preg_match('/[0-9]/', $password)) $strength++;
        
        // Check for special characters
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?]/', $password)) $strength++;
        
        return $strength;
    }
}

if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $desktop_passwordStrength = desktop_checkPasswordStrength($password);
    $showStrengthBars = true;
    
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';

    // Validation
    $error = null;
    $field = null;

    // Check if email already exists
    $checkEmailSql = "SELECT user_id FROM users WHERE email = ?";
    $checkStmt = mysqli_prepare($conn, $checkEmailSql);
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    $emailExists = mysqli_stmt_num_rows($checkStmt) > 0;
    mysqli_stmt_close($checkStmt);

    if ($emailExists) {
        $error = 'Email already registered';
        $field = 'email';
    } else if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
        $field = 'confirm_password';
    } else if ($desktop_passwordStrength < 3) {
        $error = 'Password is too weak';
        $field = 'password';
    }

    if ($error) {
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $error, 'field' => $field]);
            exit();
        }
        // echo "<script>alert('$error');</script>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);

        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);
            $_SESSION['user_id'] = $user_id;
            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Registration successful!', 'redirect' => '?page=pickanartist']);
                exit();
            }
            echo "<script>
                alert('Registration successful! Please select your favorite artists.');
                window.location.href = '?page=pickanartist';
            </script>";
            exit();
        } else {
            if ($isAjax) {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($conn)]);
                exit();
            }
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>

<video autoplay muted loop playsinline class="absolute inset-0 w-full h-screen object-cover z-0">
    <source src="./asset/image/login.mp4" type="video/mp4">
</video>
<div class="absolute inset-0 bg-black/40 z-5"></div>
<div class="w-full h-full flex flex-col items-center justify-center relative z-10">
    <div class="bg-black/50 backdrop-blur-3xl w-[40%] h-[75%] border border-white/10 shadow-2xl flex flex-col rounded-2xl p-12">
        <logo class="h-[35%] shrink-0 w-full flex flex-col items-center justify-center p-8 pb-20">
            <img src="./asset/logo/register.png" alt="register" class="min-h-24 max-h-24">
        </logo>
        <form action="" method="post" id="desktop_registerForm" data-ajax-form="true" class="flex flex-col w-full grow justify-start items-center px-12">
            <div class="flex flex-col items-center justify-start w-[85%] gap-4">
                <div class="w-full space-y-2">
                    <input type="text" id="desktop_email" name="email" placeholder="email" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none placeholder:text-zinc-600">
                </div>
                <div class="w-full space-y-2">
                    <input type="password" id="desktop_passwordInput" name="password" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none placeholder:text-zinc-600">
                    <!-- Password Strength Bars -->
                </div>
                <div class="grid grid-cols-3 gap-4 w-[94%]" style="height: 18px;">
                    <div class="border border-primary rounded-full flex items-center justify-start" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="desktop_strengthBar1" class="rounded-full" style="height: 100%; width: 0%; background-color: #ff6b9d;"></div>
                    </div>
                    <div class="border border-[#ffde59] rounded-full flex items-center justify-start" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="desktop_strengthBar2" class="rounded-full" style="height: 100%; width: 0%; background-color: #ffde59;"></div>
                    </div>
                    <div class="border border-[#7ed957] rounded-full flex items-center justify-start" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="desktop_strengthBar3" class="rounded-full" style="height: 100%; width: 0%; background-color: #7ed957;"></div>
                    </div>
                </div>
                <p id="desktop_strengthMessage" class="text-[10px] font-bold uppercase tracking-widest text-center h-4 opacity-0 hidden"></p>
                <div class="w-full space-y-2">
                    <input type="password" id="desktop_confirm_password" name="confirm_password" placeholder="confirm password" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none placeholder:text-zinc-600">
                </div>
                <div class="flex items-center justify-between w-full">
                    <a href="?page=login" class="group flex items-center translate-x-6 text-zinc-400  transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5transition-transform duration-300">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                            <span class="text-sm font-medium ml-1.5">Back to <span class="text-primary group-hover:underline">sign in</span></span>
                    </a>
                    <button type="submit" id="desktop_registerBtn" class="px-10 py-3 bg-primary hover:bg-primary/90 text-white rounded-full font-bold text-lg">
                        <span class="font-ballmer translate-y-0.5 inline-block">sign up</span>
                    </button>
                </div>
                <div class="w-full h-px bg-linear-to-r from-transparent via-white/10 to-transparent"></div>
                <p class="text-zinc-500 text-xs text-center">By signing up, you agree to our <a href="#" class="text-zinc-300 hover:text-white underline transition-all">Terms of Service</a></p>
            </div>
        </form>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>

<script>
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

    function desktop_checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        return strength;
    }
    
    function desktop_updatePasswordStrength() {
        const passwordInput = document.getElementById('desktop_passwordInput');
        const bar1 = document.getElementById('desktop_strengthBar1');
        const bar2 = document.getElementById('desktop_strengthBar2');
        const bar3 = document.getElementById('desktop_strengthBar3');
        const message = document.getElementById('desktop_strengthMessage');
        
        const strength = desktop_checkPasswordStrength(passwordInput.value);
        
        // Count before vs after
        const before1 = bar1.style.width === '100%' ? 1 : 0;
        const before2 = bar2.style.width === '100%' ? 1 : 0;
        const before3 = bar3.style.width === '100%' ? 1 : 0;
        const beforeCount = before1 + before2 + before3;
        let target1 = '0%';
        let target2 = '0%';
        let target3 = '0%';
        
        if (passwordInput.value !== '') {
            message.classList.add('opacity-100');
            message.classList.remove('opacity-0');
            
            if (strength > 4) {
                message.textContent = 'Strong Password';
                message.style.color = '#7ed957';
                target1 = '100%'; target2 = '100%'; target3 = '100%';
                passwordInput.style.borderColor = '';
                passwordInput.style.boxShadow = '';
            } else if (strength > 2) {
                message.textContent = 'Medium Password';
                message.style.color = '#ffde59';
                target1 = '100%'; target2 = '100%'; target3 = '0%';
                passwordInput.style.borderColor = '';
                passwordInput.style.boxShadow = '';
            } else {
                message.textContent = 'Weak Password';
                message.style.color = '#ff6b9d';
                target1 = '100%'; target2 = '0%'; target3 = '0%';
                passwordInput.style.borderColor = '#ef4444';
                passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
            }
        } else {
            message.classList.add('opacity-0');
            message.classList.remove('opacity-100');
            message.textContent = '';
            target1 = '0%'; target2 = '0%'; target3 = '0%';
            passwordInput.style.borderColor = '';
            passwordInput.style.boxShadow = '';
        }
        
        // Set widths
        bar1.style.width = target1;
        bar2.style.width = target2;
        bar3.style.width = target3;
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        const passwordInputField = document.getElementById('desktop_passwordInput');
        if (passwordInputField) {
            passwordInputField.addEventListener('input', desktop_updatePasswordStrength);
        }
        const form = document.getElementById('desktop_registerForm');
        const emailInput = document.getElementById('desktop_email');
        const passwordInput = document.getElementById('desktop_passwordInput');
        const confirmInput = document.getElementById('desktop_confirm_password');

        if (form && emailInput && passwordInput && confirmInput) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
            const submitBtn = document.getElementById('desktop_registerBtn');
            const shakeBtn = () => shakeEl(submitBtn);
            let isValid = true;

                [emailInput, passwordInput, confirmInput].forEach(input => {
                    if (!input.value.trim()) {
                        input.style.borderColor = '#ef4444';
                        input.style.boxShadow = '0 0 0 1px #ef4444';
                        isValid = false;
                    } else {
                        input.style.borderColor = '';
                        input.style.boxShadow = '';
                    }
                });

                // Check password strength (minimum 3)
                if (isValid && desktop_checkPasswordStrength(passwordInput.value) < 3) {
                    passwordInput.style.borderColor = '#ef4444';
                    passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                    confirmInput.style.borderColor = '#ef4444';
                    confirmInput.style.boxShadow = '0 0 0 1px #ef4444';
                    isValid = false;
                    shakeBtn();
                }

                // Check confirm password
                if (isValid && passwordInput.value !== confirmInput.value) {
                    passwordInput.style.borderColor = '#ef4444';
                    passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                    confirmInput.style.borderColor = '#ef4444';
                    confirmInput.style.boxShadow = '0 0 0 1px #ef4444';
                    isValid = false;
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
                            alert(data.message);
                            window.location.href = data.redirect;
                        } else {
                            if (data.field === 'email') {
                                emailInput.style.borderColor = '#ef4444';
                                emailInput.style.boxShadow = '0 0 0 1px #ef4444';
                                emailInput.blur();
                                shakeBtn();
                            } else if (data.field === 'password' || data.field === 'confirm_password') {
                                passwordInput.style.borderColor = '#ef4444';
                                passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                                confirmInput.style.borderColor = '#ef4444';
                                confirmInput.style.boxShadow = '0 0 0 1px #ef4444';
                                passwordInput.blur();
                                confirmInput.blur();
                                shakeBtn();
                            } else {
                                alert(data.error || 'Registration failed');
                                shakeBtn();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            });

            [emailInput, passwordInput, confirmInput].forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '';
                        this.style.boxShadow = '';
                    }
                });
            });
        }
    });
</script>

