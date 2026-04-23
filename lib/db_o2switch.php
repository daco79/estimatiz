<?php
require_once __DIR__ . '/../config.php';

function db_o2switch(): PDO {
    return get_pdo();
}
