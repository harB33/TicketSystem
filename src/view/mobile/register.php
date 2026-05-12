<?php
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$passwordStrength = 0;
$strengthMessage = '';
$showStrengthBars = false;

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

if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $passwordStrength = checkPasswordStrength($password);
    $showStrengthBars = true;

    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';

    // Check if email already exists
    $checkEmailSql = "SELECT user_id FROM users WHERE email = ?";
    $checkStmt = mysqli_prepare($conn, $checkEmailSql);
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    mysqli_stmt_store_result($checkStmt);
    $emailExists = mysqli_stmt_num_rows($checkStmt) > 0;
    mysqli_stmt_close($checkStmt);

    if ($emailExists) {
        if ($isAjax) {
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Email already registered', 'field' => 'email']);
            exit();
        }
        echo "<script>alert('Email already registered!');</script>";
    } else if ($password === $confirm_password) {
        if ($passwordStrength >= 3) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password_hash) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ss", $email, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                if ($isAjax) {
                    ob_clean();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Registration successful!', 'redirect' => '?page=login']);
                    exit();
                }
                echo "<script>
                    alert('Registration successful! Please log in.');
                    window.location.href = '?page=login';
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
}
?>

<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./asset/logo/register.png" alt="" class="w-2/3">
</logo>
<form action="" method="post" id="registerForm" class="flex flex-col h-[50%] w-full justify-center items-center -translate-y-27.5">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" id="emailInput" name="email" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191] transition-all duration-300">
        <input type="password" name="password" id="passwordInput" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191] transition-all duration-300">
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
        <p id="strengthMessage" class="text-center text-sm font-bold text-gray-500 absolute hidden"></p>
        <input type="password" id="confirmInput" name="confirm_password" placeholder="confirm password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191] transition-all duration-300">
        <div class="flex items-center justify-end w-full">
            <button type="submit" class=" p-2 border border-primary rounded-full w-1/2 bg-primary"><p class="font-ballmer text-lg translate-y-1">sign up</p></button>
        </div>
        <p class="opacity-75">Already have an account? <a href="?page=login" class="text-primary">Sign in</a></p>
        </div>
    </div>
</form>

<script>
    function checkPasswordStrength(password) {
        let strength = 0;
        
        // Check length
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        
        // Check for lowercase
        if (/[a-z]/.test(password)) strength++;
        
        // Check for uppercase
        if (/[A-Z]/.test(password)) strength++;
        
        // Check for numbers
        if (/[0-9]/.test(password)) strength++;
        
        // Check for special characters
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
            if (strength > 4) {
                passwordInput.style.borderColor = '';
                passwordInput.style.boxShadow = '';
                message.style.color = '#7ed957';
            } else if (strength > 2) { // Minimum 3 requirements
                passwordInput.style.borderColor = '';
                passwordInput.style.boxShadow = '';
                message.style.color = '#ffde59';
            } else {
                passwordInput.style.borderColor = '#ef4444';
                passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                message.style.color = '#ff6b9d';
            }
            if (strength > 1) target1 = '100%';
            if (strength > 3) target2 = '100%';
            if (strength > 4) target3 = '100%';
        } else {
            passwordInput.style.borderColor = '';
            passwordInput.style.boxShadow = '';
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
    }
    
    document.getElementById('passwordInput').addEventListener('input', updatePasswordStrength);

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('registerForm');
        const emailInput = document.getElementById('emailInput');
        const passwordInput = document.getElementById('passwordInput');
        const confirmInput = document.getElementById('confirmInput');

        if (form && emailInput && passwordInput && confirmInput) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
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
                if (isValid && checkPasswordStrength(passwordInput.value) < 3) {
                    passwordInput.style.borderColor = '#ef4444';
                    passwordInput.style.boxShadow = '0 0 0 1px #ef4444';
                    isValid = false;
                }

                // Check confirm password
                if (isValid && passwordInput.value !== confirmInput.value) {
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
                                emailInput.focus();
                            } else {
                                alert(data.error || 'Registration failed');
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
                        if (this.id !== 'passwordInput' || checkPasswordStrength(this.value) >= 3) {
                            this.style.borderColor = '';
                            this.style.boxShadow = '';
                        }
                    }
                });
            });
        }
    });
</script>
