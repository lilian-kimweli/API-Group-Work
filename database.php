<?php
//create database connection
$servername = "localhost";
$username = "root";
$password = "faith";
$dbname = "bluebell";

//Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

//Check connection
if ($conn->connect_error){
    die("Connection failed:". $conn->connect_error);
}
?>