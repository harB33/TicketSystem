<?php
include 'connectdb.php';
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $checkQuery = "SELECT * FROM users WHERE username='$username' OR email='$email'";
    $checkResult = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        echo "Username or email already exists.";
    } else {
        $insertQuery = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$password')";
        if (mysqli_query($conn, $insertQuery)) {
            echo "Registration successful!";
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}
?>