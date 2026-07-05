<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$hoy = date('Y-m-d');

$totalHoy = $db->prepare("SELECT COUNT(*) FROM turnos WHERE DATE(created_at) = ?");
$totalHoy->execute([$hoy]);
$totalHoy = $totalHoy->fetchColumn();

$pendientes = $db->prepare("SELECT COUNT(*) FROM turnos WHERE estado = 'pendiente'");
$pendientes->execute();
$pendientes = $pendientes->fetchColumn();

$atendidosHoy = $db->prepare("SELECT COUNT(*) FROM turnos WHERE DATE(created_at) = ? AND estado = 'atendido'");
$atendidosHoy->execute([$hoy]);
$atendidosHoy = $atendidosHoy->fetchColumn();

$totalClientes = $db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

$ultimosTurnos = $db->query("
    SELECT t.numero_turno, t.estado, t.created_at,
           c.nombre AS cliente, s.nombre AS servicio
    FROM turnos t
    JOIN clientes c ON c.id = t.cliente_id
    JOIN servicios s ON s.id = t.servicio_id
    ORDER BY t.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestión de Turnos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Gestión de Turnos</div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="clientes.php">Clientes</a>
            <a href="turnos.php">Turnos</a>
            <a href="logout.php" class="nav-logout">Cerrar Sesión</a>
        </div>
    </nav>
    <div class="container">
        <h1>Dashboard</h1>
        <div class="cards">
            <div class="card">
                <div class="card-number"><?= $totalHoy ?></div>
                <div class="card-label">Turnos Hoy</div>
            </div>
            <div class="card card-warning">
                <div class="card-number"><?= $pendientes ?></div>
                <div class="card-label">En Espera</div>
            </div>
            <div class="card card-success">
                <div class="card-number"><?= $atendidosHoy ?></div>
                <div class="card-label">Atendidos Hoy</div>
            </div>
            <div class="card card-info">
                <div class="card-number"><?= $totalClientes ?></div>
                <div class="card-label">Total Clientes</div>
            </div>
        </div>
        <h2>Últimos Turnos</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Turno</th>
                    <th>Cliente</th>
                    <th>Servicio</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ultimosTurnos as $t): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($t['numero_turno']) ?></strong></td>
                    <td><?= htmlspecialchars($t['cliente']) ?></td>
                    <td><?= htmlspecialchars($t['servicio']) ?></td>
                    <td>
                        <span class="badge badge-<?= $t['estado'] ?>">
                            <?= ucfirst($t['estado']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m H:i', strtotime($t['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
