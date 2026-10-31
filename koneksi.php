<?php
  $databaseConfig = require __DIR__ . "/config/database.php";
  $conn = new mysqli(
    $databaseConfig['host'],
    $databaseConfig['user'],
    $databaseConfig['pass'],
    $databaseConfig['name'],
    $databaseConfig['port']
  );
  if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
  }

  $conn->set_charset($databaseConfig['charset']);
?>