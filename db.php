<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "travel_planner_db";

$conn = mysqli_connect($host, $user, $pass, $db_name);

if (!$conn) {
    die("❌ فشل الاتصال: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
