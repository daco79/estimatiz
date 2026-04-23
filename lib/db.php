<?php
require_once __DIR__ . '/../config.php';

function db(): PDO {
    return get_pdo();
}
