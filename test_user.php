<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/backend/models/User.php';

$userModel = new User();

echo "<h2>Test findByEmail</h2>";
$user = $userModel->findByEmail('patient@congovital.cd');
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h2>Test login</h2>";
$result = $userModel->login('patient@congovital.cd', 'password');
echo "<pre>";
print_r($result);
echo "</pre>";
