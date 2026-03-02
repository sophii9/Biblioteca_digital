<?php
// ajax_geo_demo.php  —  Handler AJAX para Demo BD (Vistas)
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

$db  = new Conexion($opciones);
$pdo = $db->con;

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {

        // ── VISTA 1: ventas con geolocalización ──────────────────────────
        case 'vista_ventas_geo':
            $stmt = $pdo->query("SELECT * FROM vista_ventas_geolocalizacion ORDER BY fecha_venta DESC");
            echo json_encode(['ok' => true, 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ── VISTA 2: resumen por ciudad ───────────────────────────────────
        case 'vista_resumen_ciudad':
            $stmt = $pdo->query("SELECT * FROM vista_resumen_ventas_por_ciudad ORDER BY total_ventas DESC");
            echo json_encode(['ok' => true, 'datos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        default:
            echo json_encode(['ok' => false, 'datos' => ['mensaje' => 'Acción no reconocida']]);
    }

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'datos' => ['mensaje' => 'Error BD: ' . $e->getMessage()]]);
}