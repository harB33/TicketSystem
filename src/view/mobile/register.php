<?php
require_once (__DIR__ . '/../../ticket_db/connectdb.php');

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

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
                if ($isAjax) {
                    echo json_encode(['success' => true]);
                    exit();
                } else {
                    echo "<script>
                        alert('Registration successful! Please log in.');
                        window.location.href = '?page=login';
                    </script>";
                    exit();
                }
            } else {
                if ($isAjax) {
                    echo json_encode(['error' => 'Database error']);
                    exit();
                } else {
                    echo "Error: " . mysqli_error($conn);
                }
            }
        } else {
            if ($isAjax) {
                echo json_encode(['error' => 'Password too weak']);
                exit();
            } else {
                echo "<script>alert('Password is too weak. Please use at least 3 complexity requirements.');</script>";
            }
        }
    } else {
        if ($isAjax) {
            echo json_encode(['error' => 'Passwords do not match']);
            exit();
        } else {
            echo "<script>alert('Passwords do not match!');</script>";
        }
    }
}
?>

<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./asset/logo/register.png" alt="" class="w-2/3">
</logo>
<form action="" method="post" class="flex flex-col h-[50%] w-full justify-center items-center -translate-y-27.5">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" name="email" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <input type="password" name="password" id="passwordInput" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
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
        <input type="password" name="confirm_password" placeholder="confirm password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
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
                message.style.color = '#7ed957';
            } else if (strength > 3) {
                message.style.color = '#ffde59';
            } else if (strength > 1) {
                message.style.color = '#ff6b9d';
            }
        }
    }
    
    document.getElementById('passwordInput').addEventListener('input', updatePasswordStrength);
</script>
