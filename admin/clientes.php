<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$clientes = $db->query("SELECT * FROM clientes ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Gestión de Turnos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Gestión de Turnos</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="clientes.php" class="active">Clientes</a>
            <a href="turnos.php">Turnos</a>
            <a href="logout.php" class="nav-logout">Cerrar Sesión</a>
        </div>
    </nav>
    <div class="container">
        <h1>Gestión de Clientes</h1>
        <div class="row">
            <div class="col">
                <h2>Buscar / Registrar Cliente</h2>
                <form id="form-cliente">
                    <div class="form-group">
                        <label for="dni">DNI</label>
                        <input type="text" id="dni" name="dni" maxlength="20" placeholder="Buscar por DNI">
                    </div>
                    <div class="form-group">
                        <label for="nombre">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" id="telefono" name="telefono">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <textarea id="direccion" name="direccion" rows="2"></textarea>
                    </div>
                    <input type="hidden" id="cliente_id" name="cliente_id" value="">
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="btn-guardar">Guardar Cliente</button>
                        <button type="button" class="btn btn-secondary" id="btn-limpiar" style="display:none">Nuevo</button>
                    </div>
                    <div id="cliente-msg" class="alert" style="display:none"></div>
                </form>
            </div>
            <div class="col">
                <h2>Clientes Registrados</h2>
                <input type="text" id="buscar-lista" placeholder="Filtrar por nombre o DNI..." class="form-control">
                <div class="table-responsive">
                    <table class="table" id="tabla-clientes">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Nombre</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clientes as $c): ?>
                            <tr data-id="<?= $c['id'] ?>" data-nombre="<?= htmlspecialchars($c['nombre']) ?>" data-telefono="<?= htmlspecialchars($c['telefono']) ?>" data-email="<?= htmlspecialchars($c['email']) ?>" data-dni="<?= htmlspecialchars($c['dni']) ?>" data-direccion="<?= htmlspecialchars($c['direccion']) ?>">
                                <td><?= htmlspecialchars($c['dni']) ?></td>
                                <td><?= htmlspecialchars($c['nombre']) ?></td>
                                <td><?= htmlspecialchars($c['telefono']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-edit" onclick="editarCliente(this)">Editar</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/admin.js"></script>
    <script>
        const form = document.getElementById('form-cliente');
        const msg = document.getElementById('cliente-msg');
        const limpiarBtn = document.getElementById('btn-limpiar');

        document.getElementById('dni').addEventListener('blur', function() {
            const dni = this.value.trim();
            if (dni.length >= 3) {
                fetch('api/clientes.php?dni=' + encodeURIComponent(dni))
                    .then(r => r.json())
                    .then(data => {
                        if (data) {
                            llenarForm(data);
                        }
                    });
            }
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const data = {
                id: document.getElementById('cliente_id').value,
                nombre: document.getElementById('nombre').value,
                telefono: document.getElementById('telefono').value,
                email: document.getElementById('email').value,
                dni: document.getElementById('dni').value,
                direccion: document.getElementById('direccion').value
            };

            fetch('api/clientes.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    mostrarMsg('Cliente guardado correctamente', 'success');
                    form.reset();
                    document.getElementById('cliente_id').value = '';
                    limpiarBtn.style.display = 'none';
                    setTimeout(() => location.reload(), 800);
                } else {
                    mostrarMsg(res.error || 'Error al guardar', 'error');
                }
            });
        });

        limpiarBtn.addEventListener('click', function() {
            form.reset();
            document.getElementById('cliente_id').value = '';
            limpiarBtn.style.display = 'none';
            document.getElementById('dni').focus();
        });

        function llenarForm(c) {
            document.getElementById('cliente_id').value = c.id;
            document.getElementById('dni').value = c.dni || '';
            document.getElementById('nombre').value = c.nombre || '';
            document.getElementById('telefono').value = c.telefono || '';
            document.getElementById('email').value = c.email || '';
            document.getElementById('direccion').value = c.direccion || '';
            limpiarBtn.style.display = 'inline-block';
        }

        function editarCliente(btn) {
            const tr = btn.closest('tr');
            llenarForm({
                id: tr.dataset.id,
                dni: tr.dataset.dni,
                nombre: tr.dataset.nombre,
                telefono: tr.dataset.telefono,
                email: tr.dataset.email,
                direccion: tr.dataset.direccion
            });
        }

        function mostrarMsg(texto, tipo) {
            msg.textContent = texto;
            msg.className = 'alert alert-' + tipo;
            msg.style.display = 'block';
            setTimeout(() => msg.style.display = 'none', 3000);
        }

        document.getElementById('buscar-lista').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#tabla-clientes tbody tr').forEach(tr => {
                const text = tr.textContent.toLowerCase();
                tr.style.display = text.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
