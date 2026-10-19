<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $pdo = getPDO();
    $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo "Connexion OK - Utilisateurs : $count";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
