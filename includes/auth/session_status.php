<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$loggedIn = isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user']);

$response = ['logged_in' => $loggedIn];
if ($loggedIn) {
    $response['user'] = $_SESSION['auth_user'];
}

echo json_encode($response);
