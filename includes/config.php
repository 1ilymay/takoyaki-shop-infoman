<?php
$db_host = "localhost";
$db_username = "root";
$db_passwd = "";
$db_name = "takoyaki_shop";

$conn = null;

$conn = mysqli_connect($db_host, $db_username, $db_passwd, $db_name);

if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
} else {
    error_log("Database connection failed: " . mysqli_connect_error());
    $conn = false;
}

function filterBadWords($text) {
    $foul_words = ['bobo', 'b0b0', 'g@go', 'gago', 'tanga', 'puta', 'putangina', 'shit', 'fuck', 'fuk'];
    
    if (empty($foul_words) || empty($text)) return $text;
    
    $pattern = '/' . implode('|', array_map('preg_quote', $foul_words)) . '/i';
    
    return preg_replace_callback($pattern, function($matches) {
        return str_repeat('*', mb_strlen($matches[0]));
    }, $text);
}