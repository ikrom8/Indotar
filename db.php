<?php
// db.php - MySQLi connection
$host = 'localhost';
$db   = 'ptindotar_db';
$user = 'root';
$pass = ''; // isi password MySQL Anda jika ada

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_errno) {
    die("DB Connect Error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
