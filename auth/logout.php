<?php
session_start();
session_unset();
session_destroy();

$reason = $_GET['reason'] ?? '';
$param = $reason === 'timeout' ? '?reason=timeout' : '';

header("location: login.php" . $param);

?>