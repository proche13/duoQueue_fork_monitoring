<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=cs4116;charset=utf8', 'root', '');
    echo 'Connection successful';
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>