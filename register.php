<?php
session_start();

// Handle Registration
if(isset($_POST['register'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Save to PHP session
    $_SESSION['users'][$username] = $password;

    echo "<script>alert('Registered Successfully!'); window.location='login.php';</script>";
}
?>

<form method="POST">
    <h2>Register </h2>
    <input type="text" name="username" placeholder="Enter Username" required><br>
    <input type="password" name="password" placeholder="Enter Password" required><br>
    <button type="submit" name="register">Register</button>
</form>

<a href="login.php">Already have an account? Login</a>
