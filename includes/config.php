<?php
$db_host = "localhost";
$db_username = "root";
$db_passwd = "";
$db_name = "takoyaki_shop";

$conn = mysqli_connect($db_host, $db_username, $db_passwd, $db_name);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}