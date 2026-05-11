<?php
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$passwordStrength = 0;
$strengthMessage = '';
$showStrengthBars = false;

if (!function_exists('checkPasswordStrength')) {
    function checkPasswordStrength($password) {
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
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $passwordStrength = checkPasswordStrength($password);
    $showStrengthBars = true;
    
    if ($passwordStrength <= 2) {
        $strengthMessage = 'Weak Password';
    } elseif ($passwordStrength <= 4) {
        $strengthMessage = 'Medium Password';
    } else {
        $strengthMessage = 'Strong Password';
    }

    if ($password === $confirm_password) {
        if ($passwordStrength >= 3) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                echo "<script>
                    alert('Registration successful! Please log in.');
                    window.location.href = '?page=login';
                </script>";
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        } else {
            echo "<script>alert('Password is too weak. Please use at least 3 complexity requirements.');</script>";
        }
    } else {
        echo "<script>alert('Passwords do not match!');</script>";
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
        <form action="" method="post" id="registerForm" class="flex flex-col w-full grow justify-start items-center px-12">
            <div class="flex flex-col items-center justify-start w-[85%] gap-4">
                <div class="w-full space-y-2">
                    <input type="text" id="email" name="email" placeholder="email" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none transition-all duration-300 placeholder:text-zinc-600">
                </div>
                <div class="w-full space-y-2">
                    <input type="password" id="passwordInput" name="password" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none transition-all duration-300 placeholder:text-zinc-600">
                    <!-- Password Strength Bars -->
                </div>
                <div class="grid grid-cols-3 gap-4 w-[94%]" style="height: 18px;">
                    <div class="border border-primary rounded-full flex items-center justify-start transition-all duration-200" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="strengthBar1" class="rounded-full" style="height: 100%; width: 0%; background-color: #ff6b9d; transition: width 0.4s ease-in-out;"></div>
                    </div>
                    <div class="border border-[#ffde59] rounded-full flex items-center justify-start transition-all duration-200" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="strengthBar2" class="rounded-full" style="height: 100%; width: 0%; background-color: #ffde59; transition: width 0.4s ease-in-out;"></div>
                    </div>
                    <div class="border border-[#7ed957] rounded-full flex items-center justify-start transition-all duration-200" style="height: 100%; width: 100%; padding: 2px; box-sizing: border-box;">
                        <div id="strengthBar3" class="rounded-full" style="height: 100%; width: 0%; background-color: #7ed957; transition: width 0.4s ease-in-out;"></div>
                    </div>
                </div>
                <p id="strengthMessage" class="text-[10px] font-bold uppercase tracking-widest text-center h-4 hidden"></p>
                <div class="w-full space-y-2">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="confirm password" 
                        class="px-6 py-4 rounded-full w-full text-lg text-white font-medium bg-white/5 border border-white/10 focus:bg-white/10 focus:border-primary/50 focus:ring-1 focus:ring-primary/50 outline-none transition-all duration-300 placeholder:text-zinc-600">
                </div>
                <div class="flex items-center justify-between w-full">
                    <a href="?page=login" class="group flex items-center translate-x-6 text-zinc-400  transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5transition-transform duration-300">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                            <span class="text-sm font-medium ml-1.5">Back to <span class="text-primary group-hover:underline">sign in</span></span>
                    </a>
                    <button type="submit" class="px-10 py-3 bg-primary hover:bg-primary/90 text-white rounded-full font-bold text-lg">
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
    function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password)) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) strength++;
        return strength;
    }
    
    function updatePasswordStrength() {
        const passwordInput = document.getElementById('passwordInput');
        const bar1 = document.getElementById('strengthBar1');
        const bar2 = document.getElementById('strengthBar2');
        const bar3 = document.getElementById('strengthBar3');
        const message = document.getElementById('strengthMessage');
        
        const strength = checkPasswordStrength(passwordInput.value);
        
        // Count before vs after
        const before1 = bar1.style.width === '100%' ? 1 : 0;
        const before2 = bar2.style.width === '100%' ? 1 : 0;
        const before3 = bar3.style.width === '100%' ? 1 : 0;
        const beforeCount = before1 + before2 + before3;
        
        let target1 = '0%';
        let target2 = '0%';
        let target3 = '0%';
        
        if (passwordInput.value !== '') {
            if (strength > 1) target1 = '100%';
            if (strength > 3) target2 = '100%';
            if (strength > 4) target3 = '100%';
        }
        
        const afterCount = (target1 === '100%' ? 1 : 0) + (target2 === '100%' ? 1 : 0) + (target3 === '100%' ? 1 : 0);
        
        // Determine transition delays based on direction (Filling vs Emptying)
        if (afterCount >= beforeCount) {
            // Forward (Filling): Pink first, then Yellow, then Green
            bar1.style.transition = 'width 0.3s ease-in-out 0s';
            bar2.style.transition = 'width 0.3s ease-in-out 0.15s';
            bar3.style.transition = 'width 0.3s ease-in-out 0.3s';
        } else {
            // Reverse (Emptying): Green first, then Yellow, then Pink
            bar1.style.transition = 'width 0.3s ease-in-out 0.3s';
            bar2.style.transition = 'width 0.3s ease-in-out 0.15s';
            bar3.style.transition = 'width 0.3s ease-in-out 0s';
        }
        
        // Set widths
        bar1.style.width = target1;
        bar2.style.width = target2;
        bar3.style.width = target3;
        
        // Message & Colors
        message.textContent = '';
        message.style.color = '';
        
        if (passwordInput.value !== '') {
            if (strength > 4) {
                message.textContent = 'Strong Password';
                message.style.color = '#7ed957';
            } else if (strength > 3) {
                message.textContent = 'Medium Password';
                message.style.color = '#ffde59';
            } else if (strength > 1) {
                message.textContent = 'Weak Password';
                message.style.color = '#ff6b9d';
            }
        }
    }
    
    document.getElementById('passwordInput').addEventListener('input', updatePasswordStrength);

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('registerForm');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('passwordInput');
        const confirmInput = document.getElementById('confirm_password');

        if (form && emailInput && passwordInput && confirmInput) {
            form.addEventListener('submit', function(e) {
                let isValid = true;

                [emailInput, passwordInput, confirmInput].forEach(input => {
                    if (!input.value.trim()) {
                        input.style.outline = '2px solid #ef4444';
                        isValid = false;
                    } else {
                        input.style.outline = 'none';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }
            }, true);

            [emailInput, passwordInput, confirmInput].forEach(input => {
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.outline = 'none';
                    }
                });
            });
        }
    });
</script>

