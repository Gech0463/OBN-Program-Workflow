<?php
require 'config/database.php';
$stmt = db()->query('SELECT * FROM issues');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

