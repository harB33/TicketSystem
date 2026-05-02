<?php
include (__DIR__ . '/../../ticket_db/connectdb.php');

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
                echo "<script>alert('Registration successful! Please log in.');</script>";
                header('Location: ?page=login');
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

<logo class="h-[50%] w-full flex flex-col items-center justify-center">
    <img src="./logo/register.png" alt="" class="w-2/3">
</logo>
<form action="" method="post" class="flex flex-col h-[50%] w-full justify-center items-center -translate-y-27.5">
    <div class="flex flex-col items-center justify-start h-full w-[80%] gap-4">
        <input type="text" name="email" placeholder="email" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <input type="password" name="password" id="passwordInput" placeholder="password" class="px-6 py-4 rounded-full w-full text-lg font-bold text-[#525252] bg-[#919191]">
        <div class="grid grid-cols-3 gap-4 w-[94%]">
            <div id="strengthBar1" class="h-full w-full flex items-center justify-center border border-primary rounded-full p-2 transition-all duration-200">
            </div>
            <div id="strengthBar2" class="h-full w-full flex items-center justify-center border border-[#ffde59] rounded-full p-2 transition-all duration-200">
            </div>
            <div id="strengthBar3" class="h-full w-full flex items-center justify-center border border-[#7ed957] rounded-full p-2 transition-all duration-200">
            </div>
        </div>
        <p id="strengthMessage" class="text-center text-sm font-bold text-gray-500"></p>
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
        
        // Reset all bars
        bar1.style.backgroundColor = '';
        bar2.style.backgroundColor = '';
        bar3.style.backgroundColor = '';
        message.textContent = '';
        message.style.color = '';
        
        if (passwordInput.value === '') {
            return;
        }
        
        // Weak (1-2)
        if (strength > 1) {
            bar1.style.backgroundColor = '#ff6b9d';
            message.textContent = 'Weak Password';
            message.style.color = '#ff6b9d';
        }
        
        // Medium (3-4)
        if (strength > 3) {
            bar2.style.backgroundColor = '#ffde59';
            message.textContent = 'Medium Password';
            message.style.color = '#ffde59';
        }
        
        // Strong (5+)
        if (strength > 4) {
            bar3.style.backgroundColor = '#7ed957';
            message.textContent = 'Strong Password';
            message.style.color = '#7ed957';
        }
    }
    
    document.getElementById('passwordInput').addEventListener('input', updatePasswordStrength);
</script>
