<?php
session_start();
if(!isset($_SESSION['logged_in'])){
    header("Location: login.php");
}
?>

<h2>Welcome, <?= $_SESSION['logged_in'] ?>!</h2>
<a href="logout.php">Logout</a>
