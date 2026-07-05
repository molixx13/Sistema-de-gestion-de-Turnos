# Sistema de Gestión de Turnos

Aplicación web para la gestión integral de turnos y colas de espera. Proporciona una interfaz administrativa para crear y gestionar turnos, junto con una pantalla pública de visualización en tiempo real mediante WebSocket.

## Características

- Interfaz de administración para la gestión de turnos
- Pantalla pública de visualización en tiempo real
- Comunicación bidireccional mediante WebSocket (Socket.IO)
- Sistema de autenticación para administradores
- Cola de espera dinámica
- Diseño responsivo y accesible

## Requisitos del Sistema

- PHP 7.4 o superior
- Node.js 12 o superior
- Servidor web (Apache, Nginx)
- Base de datos (MySQL recomendado)
- Navegador moderno (Chrome, Firefox, Safari, Edge)

## Estructura del Proyecto

```
Sistema-de-gestion-de-Turnos/
├── admin/              Interfaz administrativa
├── assets/             Recursos estáticos (CSS, imágenes)
├── config/             Archivos de configuración
├── pantalla/           Pantalla pública de visualización
├── websocket/          Servidor WebSocket (Node.js)
├── index.php           Punto de entrada
└── CONTRIBUTING.md     Guía de contribución
```

## Instalación

### 1. Clonar el Repositorio

```bash
git clone https://github.com/molixx13/Sistema-de-gestion-de-Turnos.git
cd Sistema-de-gestion-de-Turnos
```

### 2. Configurar Backend PHP

Crear archivo de configuración:

```bash
cp config/example.php config/config.php
```

Actualizar las credenciales de base de datos en `config/config.php`.

### 3. Configurar Servidor WebSocket

Instalar dependencias de Node.js:

```bash
cd websocket
npm install
```

Crear archivo `.env` con la configuración necesaria:

```
PORT=3001
HOST=localhost
```

Iniciar el servidor WebSocket:

```bash
npm start
```

### 4. Configurar Servidor Web

Asegurar que el servidor web apunta al directorio raíz del proyecto.

Para desarrollo local con PHP:

```bash
php -S localhost:8000
```

## Uso

### Panel de Administración

1. Navegar a `http://localhost:8000/admin/login.php`
2. Ingresar credenciales de administrador
3. Desde el dashboard, crear y gestionar turnos
4. Los cambios se reflejan en tiempo real en la pantalla pública

### Pantalla Pública

1. Navegar a `http://localhost:8000/pantalla/`
2. Se conecta automáticamente al servidor WebSocket
3. Muestra turnos en tiempo real
4. Indicador de estado de conexión

## Stack Tecnológico

- **Backend**: PHP 73.6%
- **Frontend**: JavaScript 3.5%
- **Estilos**: CSS 15.7%
- **Comunicación**: Socket.IO, WebSocket

## Flujo de Comunicación

```
Admin Panel → PHP Backend → Database
                    ↓
               WebSocket Server
                    ↑
Public Display ← Socket.IO ← Real-time Updates
```

## API WebSocket

### Eventos Emitidos

**actualizacion**
Enviado cuando cambia el estado de los turnos.

```javascript
socket.on('actualizacion', function(data) {
    // data.llamado: turno actual {numero, cliente, servicio}
    // data.cola: array de turnos pendientes
});
```

**estado_inicial**
Enviado al conectarse para sincronizar estado.

```javascript
socket.on('estado_inicial', function(data) {
    // Mismo formato que 'actualizacion'
});
```

## Seguridad

- Autenticación obligatoria para el panel administrativo
- Sesiones PHP para gestión de usuarios
- Validación de entrada en todas las operaciones
- Prepared statements para prevenir inyecciones SQL
- Sanitización de datos HTML en frontend

## Contribuir

Las contribuciones son bienvenidas. Revisar `CONTRIBUTING.md` para:
- Reportar bugs
- Sugerir mejoras
- Enviar pull requests
- Estándares de código

Pasos básicos:

```bash
git checkout -b feature/mi-funcionalidad
# Realizar cambios
git commit -m "feat: descripción clara del cambio"
git push origin feature/mi-funcionalidad
```

## Licencia

Este proyecto está disponible bajo licencia abierta. Consultar archivo LICENSE para más detalles.

## Soporte

Para reportar problemas o solicitar ayuda:
- Abrir un issue en el repositorio
- Incluir detalles del problema y pasos para reproducir
- Proporcionar información del sistema

## Roadmap Futuro

- Integración con múltiples servicios
- Generación de reportes
- Sistema de notificaciones
- Persistencia de datos históricos
- Temas personalizables
- Multi-idioma

## Autores

Desarrollado por molixx13

## Changelog

### v1.0.0 (2026-07-05)
- Versión inicial del sistema
- Funcionalidad básica de gestión de turnos
- Panel administrativo
- Pantalla pública en tiempo real
