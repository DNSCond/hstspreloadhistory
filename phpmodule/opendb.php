<?php $pdo = new PDO('sqlite:' . __DIR__ . '/hsts_history.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
function getPDO(): PDO
{
    global $pdo;
    return $pdo;
}
