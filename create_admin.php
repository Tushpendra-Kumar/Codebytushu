<?php
require 'config/app.php';
require 'config/database.php';
require 'includes/functions.php';

$pdo = db();

$email = 'tushpendrakumar@gmail.com';
$password = 'Tush@2196';
$hash = hashPassword($password);

// Check if user exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Update existing user to ensure password, role, status are correct
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, role = "admin", status = "active", email_verified = 1 WHERE email = ?');
    $stmt->execute([$hash, $email]);
    echo "Admin account updated successfully.";
} else {
    // Insert new user
    $stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, password_hash, role, status, email_verified) VALUES (?, ?, ?, ?, "admin", "active", 1)');
    $stmt->execute(['Tushpendra Kumar', 'tushpendrakumar', $email, $hash]);
    echo "Admin account created successfully.";
}
