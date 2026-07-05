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
    $estado = $_GET['estado'] ?? '';
    if ($estado === 'pendiente') {
        $stmt = $db->query("
            SELECT t.id, t.numero_turno, t.estado, t.created_at,
                   c.nombre AS cliente, s.nombre AS servicio
            FROM turnos t
            JOIN clientes c ON c.id = t.cliente_id
            JOIN servicios s ON s.id = t.servicio_id
            WHERE t.estado IN ('pendiente')
            ORDER BY t.created_at ASC
        ");
        $turnos = $stmt->fetchAll();
        foreach ($turnos as &$t) {
            $t['desde'] = time() - strtotime($t['created_at']) < 60
                ? 'Ahora'
                : date('H:i', strtotime($t['created_at']));
        }
        echo json_encode($turnos);
        exit;
    }

    if ($estado === 'llamado') {
        $stmt = $db->query("
            SELECT t.id, t.numero_turno, t.estado, t.llamado_at,
                   c.nombre AS cliente, s.nombre AS servicio
            FROM turnos t
            JOIN clientes c ON c.id = t.cliente_id
            JOIN servicios s ON s.id = t.servicio_id
            WHERE t.estado = 'llamado'
            ORDER BY t.llamado_at DESC
            LIMIT 1
        ");
        $llamado = $stmt->fetch();
        echo json_encode($llamado ?: null);
        exit;
    }

    echo json_encode([]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['action'])) {
        echo json_encode(['ok' => false, 'error' => 'Acción requerida']);
        exit;
    }

    $action = $data['action'];

    try {
        if ($action === 'asignar') {
            $cliente_id = (int)($data['cliente_id'] ?? 0);
            $servicio_id = (int)($data['servicio_id'] ?? 0);

            if (!$cliente_id || !$servicio_id) {
                echo json_encode(['ok' => false, 'error' => 'Cliente y servicio requeridos']);
                exit;
            }

            $db->beginTransaction();

            $stmt = $db->prepare("UPDATE servicios SET contador = contador + 1 WHERE id = ?");
            $stmt->execute([$servicio_id]);

            $stmt = $db->prepare("SELECT prefijo, contador FROM servicios WHERE id = ?");
            $stmt->execute([$servicio_id]);
            $servicio = $stmt->fetch();

            $numero = $servicio['prefijo'] . '-' . str_pad($servicio['contador'], 3, '0', STR_PAD_LEFT);

            $stmt = $db->prepare("INSERT INTO turnos (cliente_id, servicio_id, numero_turno, estado) VALUES (?, ?, ?, 'pendiente')");
            $stmt->execute([$cliente_id, $servicio_id, $numero]);
            $turno_id = $db->lastInsertId();

            $db->commit();

            notificarWebSocket($db);

            echo json_encode([
                'ok' => true,
                'turno' => [
                    'id' => $turno_id,
                    'numero_turno' => $numero,
                ]
            ]);
            exit;
        }

        if ($action === 'llamar') {
            $turno_id = (int)($data['turno_id'] ?? 0);
            if (!$turno_id) {
                echo json_encode(['ok' => false, 'error' => 'Turno requerido']);
                exit;
            }

            $stmt = $db->prepare("UPDATE turnos SET estado = 'llamado', llamado_at = NOW() WHERE id = ? AND estado = 'pendiente'");
            $stmt->execute([$turno_id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['ok' => false, 'error' => 'El turno ya no está pendiente']);
                exit;
            }

            notificarWebSocket($db);

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'atender') {
            $turno_id = (int)($data['turno_id'] ?? 0);
            $stmt = $db->prepare("UPDATE turnos SET estado = 'atendido', atendido_at = NOW() WHERE id = ? AND estado = 'llamado'");
            $stmt->execute([$turno_id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['ok' => false, 'error' => 'El turno no está en estado llamado']);
                exit;
            }

            notificarWebSocket($db);

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'cancelar') {
            $turno_id = (int)($data['turno_id'] ?? 0);
            $stmt = $db->prepare("UPDATE turnos SET estado = 'cancelado' WHERE id = ? AND estado = 'pendiente'");
            $stmt->execute([$turno_id]);

            if ($stmt->rowCount() === 0) {
                echo json_encode(['ok' => false, 'error' => 'El turno no está pendiente']);
                exit;
            }

            notificarWebSocket($db);

            echo json_encode(['ok' => true]);
            exit;
        }

        if ($action === 'finalizar_llamado') {
            $turno_id = (int)($data['turno_id'] ?? 0);
            $stmt = $db->prepare("UPDATE turnos SET estado = 'atendido', atendido_at = NOW() WHERE id = ? AND estado = 'llamado'");
            $stmt->execute([$turno_id]);

            notificarWebSocket($db);

            echo json_encode(['ok' => true]);
            exit;
        }

        echo json_encode(['ok' => false, 'error' => 'Acción desconocida']);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'error' => 'Método no permitido']);

function notificarWebSocket(PDO $db) {
    $llamado = $db->query("
        SELECT t.numero_turno, c.nombre AS cliente, s.nombre AS servicio
        FROM turnos t
        JOIN clientes c ON c.id = t.cliente_id
        JOIN servicios s ON s.id = t.servicio_id
        WHERE t.estado = 'llamado'
        ORDER BY t.llamado_at DESC
        LIMIT 1
    ")->fetch();

    $cola = $db->query("
        SELECT t.numero_turno, c.nombre AS cliente, s.nombre AS servicio
        FROM turnos t
        JOIN clientes c ON c.id = t.cliente_id
        JOIN servicios s ON s.id = t.servicio_id
        WHERE t.estado = 'pendiente'
        ORDER BY t.created_at ASC
    ")->fetchAll();

    $payload = json_encode([
        'llamado' => $llamado ?: null,
        'cola' => $cola
    ]);

    try {
        $ch = curl_init('http://localhost:3001/notificar');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_CONNECTTIMEOUT => 1,
        ]);
        curl_exec($ch);
        curl_close($ch);
    } catch (Exception $e) {
        // WebSocket server no disponible, ignorar
    }
}
