<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=secure_laravel_app','root','');
$stmt = $pdo->query("SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='secure_laravel_app' AND TABLE_NAME='users'");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r){
    echo $r['COLUMN_NAME'].' '.$r['DATA_TYPE'].' '.$r['COLUMN_TYPE']."\n";
}
