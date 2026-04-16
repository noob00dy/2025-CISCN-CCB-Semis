<?php
$path = (isset($_GET['path']) && $_GET['path'] !== '') ? $_GET['path'] : 'index.php';
echo filemtime("index.php");
?>
