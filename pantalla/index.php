<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantalla de Turnos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="pantalla-body">
    <h1>Sistema de Turnos</h1>
    <p class="pantalla-sub">Espere su turno</p>

    <div id="turno-actual" class="turno-actual">
        <div class="sin-turno">No hay turnos llamados</div>
    </div>

    <div id="cola-espera" class="cola-espera">
        <h2>Turnos en Espera</h2>
        <div id="cola-items" class="cola-items">
            <p style="color:#484f58;text-align:center">Sin turnos en espera</p>
        </div>
    </div>

    <div id="conexion-status" class="conexion-status offline">Desconectado</div>

    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    <script>
        const socket = io('http://localhost:3001', {
            reconnection: true,
            reconnectionDelay: 1000,
            reconnectionAttempts: Infinity
        });

        const statusEl = document.getElementById('conexion-status');
        const turnoActualEl = document.getElementById('turno-actual');
        const colaItemsEl = document.getElementById('cola-items');

        socket.on('connect', function() {
            statusEl.className = 'conexion-status online';
            statusEl.textContent = 'Conectado';
        });

        socket.on('disconnect', function() {
            statusEl.className = 'conexion-status offline';
            statusEl.textContent = 'Desconectado';
        });

        socket.on('connect_error', function() {
            statusEl.className = 'conexion-status offline';
            statusEl.textContent = 'Error de conexión';
        });

        socket.on('actualizacion', function(data) {
            actualizarPantalla(data);
        });

        socket.on('estado_inicial', function(data) {
            actualizarPantalla(data);
        });

        function actualizarPantalla(data) {
            if (data.llamado) {
                turnoActualEl.innerHTML = `
                    <div class="turno-numero">${escHtml(data.llamado.numero)}</div>
                    <div class="turno-cliente">${escHtml(data.llamado.cliente)}</div>
                    <div class="turno-servicio">${escHtml(data.llamado.servicio)}</div>
                `;
            } else {
                turnoActualEl.innerHTML = `<div class="sin-turno">No hay turnos llamados</div>`;
            }

            if (data.cola && data.cola.length > 0) {
                let html = '';
                data.cola.forEach(function(t) {
                    html += `
                        <div class="cola-item">
                            <span class="num">${escHtml(t.numero)}</span>
                            <span class="cli">${escHtml(t.cliente)}</span>
                            <span class="ser">${escHtml(t.servicio)}</span>
                        </div>
                    `;
                });
                colaItemsEl.innerHTML = html;
            } else {
                colaItemsEl.innerHTML = '<p style="color:#484f58;text-align:center">Sin turnos en espera</p>';
            }
        }

        function escHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>
