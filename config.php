<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pedido";

/* $servername = "localhost";
$username = "kekaca97_pedido_jjstore";
$password = "pedido123.789";
$dbname = "kekaca97_pedido_jjstore"; */

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
