<?php $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); $pdo->exec('DROP DATABASE IF EXISTS db_ged_test'); $pdo->exec('CREATE DATABASE db_ged_test'); echo 'OK';
