const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
app.use(express.json());

const server = http.createServer(app);
const io = new Server(server, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

let ultimoEstado = {
    llamado: null,
    cola: []
};

io.on('connection', (socket) => {
    console.log('Cliente conectado:', socket.id);
    socket.emit('estado_inicial', ultimoEstado);

    socket.on('disconnect', () => {
        console.log('Cliente desconectado:', socket.id);
    });
});

app.post('/notificar', (req, res) => {
    const data = req.body;
    if (!data) {
        return res.status(400).json({ ok: false, error: 'Datos requeridos' });
    }

    ultimoEstado = {
        llamado: data.llamado || null,
        cola: data.cola || []
    };

    io.emit('actualizacion', ultimoEstado);
    console.log('Notificación enviada a', io.engine.clientsCount, 'clientes');
    res.json({ ok: true });
});

app.get('/estado', (req, res) => {
    res.json(ultimoEstado);
});

const PORT = 3001;
server.listen(PORT, '0.0.0.0', () => {
    console.log('Servidor WebSocket corriendo en http://0.0.0.0:' + PORT);
});
