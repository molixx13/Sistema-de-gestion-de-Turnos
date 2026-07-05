<?php
session_start();
if (!isset($_SESSION['admin'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance()->getConnection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $q = $_GET['q'] ?? $_GET['dni'] ?? '';
    if ($q === '') {
        echo json_encode(null);
        exit;
    }
    $stmt = $db->prepare("SELECT * FROM clientes WHERE dni LIKE ? OR nombre LIKE ? ORDER BY nombre ASC LIMIT 10");
    $stmt->execute(["%$q%", "%$q%"]);
    $clientes = $stmt->fetchAll();
    echo json_encode($clientes);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || empty($data['nombre'])) {
        echo json_encode(['ok' => false, 'error' => 'El nombre es obligatorio']);
        exit;
    }

    $id = $data['id'] ?? null;
    $nombre = trim($data['nombre']);
    $telefono = trim($data['telefono'] ?? '');
    $email = trim($data['email'] ?? '');
    $dni = trim($data['dni'] ?? '');
    $direccion = trim($data['direccion'] ?? '');

    if ($id) {
        $stmt = $db->prepare("UPDATE clientes SET nombre=?, telefono=?, email=?, dni=?, direccion=? WHERE id=?");
        $stmt->execute([$nombre, $telefono, $email, $dni, $direccion, $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO clientes (nombre, telefono, email, dni, direccion) VALUES (?,?,?,?,?)");
        $stmt->execute([$nombre, $telefono, $email, $dni, $direccion]);
    }

    echo json_encode(['ok' => true, 'id' => $id ?: $db->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
