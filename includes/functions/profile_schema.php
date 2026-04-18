<?php

function schema_table_exists(mysqli $conn, string $tableName): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $tableName);
    $stmt->execute();
    $result = $stmt->get_result();
    return (bool)($result && $result->fetch_row());
}

function schema_column_exists(mysqli $conn, string $tableName, string $columnName): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $tableName, $columnName);
    $stmt->execute();
    $result = $stmt->get_result();
    return (bool)($result && $result->fetch_row());
}

function schema_foreign_key_exists(mysqli $conn, string $tableName, string $constraintName): bool
{
    $stmt = $conn->prepare(
        "SELECT 1 FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY' LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $tableName, $constraintName);
    $stmt->execute();
    $result = $stmt->get_result();
    return (bool)($result && $result->fetch_row());
}

function ensure_profile_schema(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS vehiculos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            placa VARCHAR(20) NOT NULL,
            capacidad INT UNSIGNED NOT NULL,
            estado VARCHAR(40) NOT NULL,
            codigo_interno VARCHAR(80) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_vehiculos_placa (placa),
            UNIQUE KEY uq_vehiculos_codigo (codigo_interno)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $conn->query(
        "CREATE TABLE IF NOT EXISTS vehiculo_rutas (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            vehiculo_id INT UNSIGNED NOT NULL,
            ruta_id INT(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_vehiculo_ruta (vehiculo_id, ruta_id),
            KEY idx_vehiculo_ruta_ruta (ruta_id),
            CONSTRAINT fk_vehiculo_ruta_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE CASCADE,
            CONSTRAINT fk_vehiculo_ruta_ruta FOREIGN KEY (ruta_id) REFERENCES rutas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    if (!schema_column_exists($conn, 'chofer', 'apellidos')) {
        $conn->query("ALTER TABLE chofer ADD COLUMN apellidos VARCHAR(255) NULL AFTER nombre");
    }

    if (!schema_column_exists($conn, 'chofer', 'rol')) {
        $conn->query("ALTER TABLE chofer ADD COLUMN rol VARCHAR(30) NOT NULL DEFAULT 'chofer' AFTER password");
    }

    if (!schema_column_exists($conn, 'chofer', 'vehiculo_id')) {
        $conn->query("ALTER TABLE chofer ADD COLUMN vehiculo_id INT UNSIGNED NULL AFTER rol");
    }

    if (!schema_foreign_key_exists($conn, 'chofer', 'fk_chofer_vehiculo')) {
        $conn->query("ALTER TABLE chofer ADD INDEX idx_chofer_vehiculo (vehiculo_id)");
        $conn->query("ALTER TABLE chofer ADD CONSTRAINT fk_chofer_vehiculo FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE SET NULL");
    }
}

