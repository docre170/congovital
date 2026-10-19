<?php
session_start();
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json; charset=utf-8');

// Autoriser uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

// Lire les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['email']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email et mot de passe requis.']);
    exit;
}

// Authentification
$userModel = new User();
$result = $userModel->login($input['email'], $input['password']);

// Enregistrer la session si succès
if ($result['success']) {
    $_SESSION['user_id']  = $result['user']['id'];
    $_SESSION['role']     = $result['user']['role'];
    $_SESSION['username'] = $result['user']['username'];
}

http_response_code($result['success'] ? 200 : 401);
echo json_encode($result);
