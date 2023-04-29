<?php
$pdo = new PDO('mysql:dbname=swcombine-prospecting-results;host=database', 'swcdemo', 'swcdemo', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$query = $pdo->query('SHOW VARIABLES like "version"');

$row = $query->fetch();

echo 'MySQL version:' . $row['Value'];
