<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

$servicios = $db->query("SELECT * FROM servicios ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos - Gestión de Turnos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">Gestión de Turnos</div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="clientes.php">Clientes</a>
            <a href="turnos.php" class="active">Turnos</a>
            <a href="logout.php" class="nav-logout">Cerrar Sesión</a>
        </div>
    </nav>
    <div class="container">
        <h1>Gestión de Turnos</h1>

        <div class="row">
            <div class="col">
                <h2>Asignar Turno</h2>
                <form id="form-asignar">
                    <div class="form-group">
                        <label for="buscar_cliente">Buscar Cliente (DNI o Nombre)</label>
                        <input type="text" id="buscar_cliente" placeholder="Escriba para buscar..." autocomplete="off">
                        <div id="resultados-cliente" class="dropdown-results"></div>
                        <input type="hidden" id="cliente_id" name="cliente_id">
                    </div>
                    <div class="form-group">
                        <label for="cliente_seleccionado">Cliente Seleccionado</label>
                        <p id="cliente_seleccionado" class="text-muted">Ninguno</p>
                    </div>
                    <div class="form-group">
                        <label for="servicio_id">Servicio</label>
                        <select id="servicio_id" name="servicio_id" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($servicios as $s): ?>
                                <option value="<?= $s['id'] ?>" data-prefijo="<?= htmlspecialchars($s['prefijo']) ?>">
                                    <?= htmlspecialchars($s['nombre']) ?> (<?= htmlspecialchars($s['prefijo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn-asignar" disabled>Asignar Turno</button>
                    <div id="asignar-msg" class="alert" style="display:none"></div>
                </form>
            </div>
            <div class="col">
                <h2>Cola de Espera</h2>
                <div id="cola-container">
                    <p class="text-muted">Cargando...</p>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/admin.js"></script>
    <script>
        let clienteSeleccionado = null;
        let timeoutBusqueda = null;

        const inputBuscar = document.getElementById('buscar_cliente');
        const resultados = document.getElementById('resultados-cliente');
        const clienteSeleccionadoEl = document.getElementById('cliente_seleccionado');
        const clienteIdInput = document.getElementById('cliente_id');
        const btnAsignar = document.getElementById('btn-asignar');
        const formAsignar = document.getElementById('form-asignar');
        const asignarMsg = document.getElementById('asignar-msg');
        const colaContainer = document.getElementById('cola-container');

        inputBuscar.addEventListener('input', function() {
            clearTimeout(timeoutBusqueda);
            const q = this.value.trim();
            if (q.length < 2) {
                resultados.innerHTML = '';
                return;
            }
            timeoutBusqueda = setTimeout(() => {
                fetch('../admin/api/clientes.php?q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        resultados.innerHTML = '';
                        if (data && data.length) {
                            data.forEach(c => {
                                resultados.innerHTML += `<div class="dropdown-item" onclick="seleccionarCliente(${c.id}, '${escHtml(c.nombre)}', '${escHtml(c.dni)}')">
                                    <strong>${escHtml(c.nombre)}</strong> - DNI: ${escHtml(c.dni)}
                                </div>`;
                            });
                        } else {
                            resultados.innerHTML = '<div class="dropdown-item text-muted">Sin resultados</div>';
                        }
                    });
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#buscar_cliente')) {
                resultados.innerHTML = '';
            }
        });

        function seleccionarCliente(id, nombre, dni) {
            clienteSeleccionado = { id, nombre, dni };
            clienteIdInput.value = id;
            clienteSeleccionadoEl.innerHTML = `<strong>${escHtml(nombre)}</strong> (DNI: ${escHtml(dni)})`;
            resultados.innerHTML = '';
            inputBuscar.value = nombre;
            validarForm();
        }

        function validarForm() {
            btnAsignar.disabled = !(clienteSeleccionado && document.getElementById('servicio_id').value);
        }

        document.getElementById('servicio_id').addEventListener('change', validarForm);

        formAsignar.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!clienteSeleccionado) return;

            btnAsignar.disabled = true;
            btnAsignar.textContent = 'Asignando...';

            fetch('api/turnos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'asignar',
                    cliente_id: clienteSeleccionado.id,
                    servicio_id: document.getElementById('servicio_id').value
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    mostrarMsg(asignarMsg, 'Turno ' + res.turno.numero_turno + ' asignado', 'success');
                    formAsignar.reset();
                    clienteSeleccionado = null;
                    clienteIdInput.value = '';
                    clienteSeleccionadoEl.innerHTML = 'Ninguno';
                    btnAsignar.disabled = true;
                    cargarCola();
                } else {
                    mostrarMsg(asignarMsg, res.error || 'Error al asignar', 'error');
                }
            })
            .finally(() => {
                btnAsignar.textContent = 'Asignar Turno';
            });
        });

        function cargarCola() {
            Promise.all([
                fetch('api/turnos.php?estado=pendiente').then(r => r.json()),
                fetch('api/turnos.php?estado=llamado').then(r => r.json())
            ]).then(([pendientes, llamado]) => {
                let html = '';

                if (llamado && llamado.id) {
                    html += `<div class="card" style="margin-bottom:16px;border-left:4px solid #1a73e8">
                        <h3 style="margin-bottom:8px;color:#1a73e8">Turno Llamado</h3>
                        <p style="font-size:20px;font-weight:700">${escHtml(llamado.numero_turno)} - ${escHtml(llamado.cliente)}</p>
                        <p style="color:#666">${escHtml(llamado.servicio)}</p>
                        <div style="margin-top:8px">
                            <button class="btn btn-sm btn-success" onclick="accionTurno(${llamado.id}, 'atender')">Atender</button>
                            <button class="btn btn-sm btn-danger" onclick="accionTurno(${llamado.id}, 'cancelar')">Cancelar</button>
                        </div>
                    </div>`;
                }

                if (pendientes.length) {
                    html += '<table class="table"><thead><tr><th>Turno</th><th>Cliente</th><th>Servicio</th><th>Desde</th><th>Acción</th></tr></thead><tbody>';
                    pendientes.forEach(t => {
                        html += `<tr>
                            <td><strong>${escHtml(t.numero_turno)}</strong></td>
                            <td>${escHtml(t.cliente)}</td>
                            <td>${escHtml(t.servicio)}</td>
                            <td>${t.desde}</td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="accionTurno(${t.id}, 'llamar')">Llamar</button>
                                <button class="btn btn-sm btn-danger" onclick="accionTurno(${t.id}, 'cancelar')">Cancelar</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    colaContainer.innerHTML = html;
                } else if (!llamado || !llamado.id) {
                    colaContainer.innerHTML = '<p class="text-muted">No hay turnos en espera</p>';
                } else {
                    colaContainer.innerHTML = html;
                }
            });
        }

        function accionTurno(id, action) {
            fetch('api/turnos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, turno_id: id })
            })
            .then(r => r.json())
            .then(res => {
                if (res.ok) {
                    cargarCola();
                } else {
                    alert(res.error || 'Error');
                }
            });
        }

        function mostrarMsg(el, texto, tipo) {
            el.textContent = texto;
            el.className = 'alert alert-' + tipo;
            el.style.display = 'block';
            setTimeout(() => el.style.display = 'none', 4000);
        }

        cargarCola();
        setInterval(cargarCola, 10000);
    </script>
</body>
</html>
