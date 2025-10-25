<?php
session_start();

// Handle Login
if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    if(isset($_SESSION['users'][$username]) && $_SESSION['users'][$username] == $password){
        $_SESSION['logged_in'] = $username;
        echo "<script>alert('Login Successful!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Invalid Credentials!');</script>";
    }
}
?>

<form method="POST">
    <h2>Login</h2>
    <input type="text" name="username" placeholder="Enter Username" required><br>
    <input type="password" name="password" placeholder="Enter Password" required><br>
    <button type="submit" name="login">Login</button>
</form>

<a href="register.php">Create Account</a>
