<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/backend/config/database.php';

try {
    $pdo = getPDO();
    echo "<h2>Connexion PDO OK</h2>";

    $users = $pdo->query('SELECT id, username, email, role FROM users')->fetchAll();
    echo "<p>Nombre d'utilisateurs : " . count($users) . "</p>";
    echo "<pre>";
    print_r($users);
    echo "</pre>";
} catch (Exception $e) {
    echo "<h2>Erreur</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
