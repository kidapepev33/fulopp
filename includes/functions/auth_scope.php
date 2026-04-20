<?php
require_once __DIR__ . '/profile_schema.php';

function ensure_auth_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_auth_user(): array
{
    ensure_auth_session();
    if (!isset($_SESSION['auth_user']) || !is_array($_SESSION['auth_user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Sesion no valida']);
        exit;
    }
    return $_SESSION['auth_user'];
}

function get_user_scope(mysqli $conn, int $userId): array
{
    ensure_profile_schema($conn);

    $scope = [
        'role' => 'chofer',
        'allowed_route_ids' => []
    ];

    if ($userId <= 0) {
        return $scope;
    }

    $userStmt = $conn->prepare("SELECT rol FROM chofer WHERE id = ? LIMIT 1");
    if ($userStmt) {
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $row = $userResult ? $userResult->fetch_assoc() : null;
        if ($row) {
            $scope['role'] = $row['rol'] ?: 'chofer';
        }
    }

    if ($scope['role'] === 'admin') {
        return $scope;
    }

    $routeStmt = $conn->prepare(
        "SELECT ruta_id
         FROM chofer_rutas
         WHERE chofer_id = ?
         ORDER BY id ASC"
    );
    if (!$routeStmt) {
        return $scope;
    }
    $routeStmt->bind_param('i', $userId);
    $routeStmt->execute();
    $routeResult = $routeStmt->get_result();
    while ($row = $routeResult->fetch_assoc()) {
        $routeId = intval($row['ruta_id'] ?? 0);
        if ($routeId > 0) {
            $scope['allowed_route_ids'][] = $routeId;
        }
    }

    $scope['allowed_route_ids'] = array_values(array_unique($scope['allowed_route_ids']));
    return $scope;
}

function user_can_access_route(array $scope, int $routeId): bool
{
    if ($scope['role'] === 'admin') {
        return true;
    }
    return in_array($routeId, $scope['allowed_route_ids'], true);
}

function build_in_clause_int(array $values): string
{
    if (empty($values)) {
        return 'NULL';
    }
    return implode(',', array_map('intval', $values));
}
