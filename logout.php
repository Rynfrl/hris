<?php
  include "session.php";
  include "koneksi.php";

  if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    $log = $conn->prepare(
      "INSERT INTO activity_logs
          (user_id, action, entity_type, entity_id, summary, ip_address)
          VALUES (?, 'LOGOUT', 'auth', ?, 'Logout berhasil', ?)"
    );

    $log->bind_param(
      "iis",
      $user_id,
      $user_id,
      $ip
    );
    $log->execute();
  }

  session_unset();
  session_destroy();

  header("Location: index.php");
  exit();
?>