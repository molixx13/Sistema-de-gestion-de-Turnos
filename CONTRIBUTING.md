# Guía de Contribución

## Código de Conducta

Todos los participantes deben ser respetuosos, inclusivos y constructivos.

- Usa lenguaje inclusivo y respetuoso
- Sé receptivo a críticas constructivas
- Enfócate en lo mejor para la comunidad

## Reportar Bugs

### Antes de Reportar

- Verifica si el bug ya ha sido reportado
- Intenta reproducir el problema en diferentes navegadores
- Recopila información del sistema

### Información a Incluir

- Título descriptivo del problema
- Descripción detallada de qué sucedió
- Pasos para reproducir
- Comportamiento esperado
- Información del sistema (SO, navegador, PHP version, Node.js)
- Screenshots si es aplicable

Ejemplo:
```
Titulo: WebSocket no recibe actualizaciones en Firefox

Descripcion:
La pantalla de turnos no actualiza cuando se asigna un nuevo turno desde Firefox.

Pasos:
1. Acceder a pantalla pública en Firefox
2. Crear turno desde admin en Chrome
3. Pantalla en Firefox no se actualiza

Esperado:
La pantalla debería actualizar automáticamente
```

## Sugerir Mejoras

- Describe el problema que resuelve
- Explica la solución propuesta
- Menciona alternativas consideradas
- Explica el impacto

## Pull Requests

### Preparación

```bash
git clone https://github.com/tu-usuario/Sistema-de-gestion-de-Turnos.git
cd Sistema-de-gestion-de-Turnos
git checkout -b feature/nombre-descriptivo
```

### Commits

Usar Conventional Commits:

```
feat:     Nueva funcionalidad
fix:      Corrección de bug
docs:     Cambios de documentación
style:    Formato, indentación
refactor: Reorganización de código
test:     Pruebas
chore:    Dependencias, configuración
```

Ejemplos:
```bash
git commit -m "feat(turnos): agregar validación de cliente"
git commit -m "fix(websocket): corregir reconexión"
git commit -m "docs(readme): actualizar instalación"
```

### Crear Pull Request

```bash
git push origin feature/nombre-descriptivo
```

En GitHub, proporcionar:
- Descripción de cambios
- Tipo de cambio (bug fix, feature, etc.)
- Cómo se ha probado
- Checklist de verificación

## Estándares de Código

### PHP

```php
<?php
class ClienteManager {
    private $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Busca clientes
     * 
     * @param string $search Término de búsqueda
     * @param int $limit Límite de resultados
     * @return array Array de clientes
     */
    public function search(string $search, int $limit = 10): array {
        $search = "%{$search}%";
        $stmt = $this->db->prepare(
            "SELECT * FROM clientes 
             WHERE nombre LIKE ? OR dni LIKE ? 
             LIMIT ?"
        );
        $stmt->execute([$search, $search, $limit]);
        return $stmt->fetchAll();
    }
}
```

Buenas prácticas:
- Usar type hints
- Documentar con PHPDoc
- Nombrar significativamente
- Usar prepared statements
- Validar entrada

### JavaScript

```javascript
/**
 * Actualiza la pantalla de turnos
 * @param {Object} data - Datos del estado
 * @param {Object} data.llamado - Turno actual
 * @param {Array} data.cola - Cola pendiente
 */
function actualizarPantalla(data) {
    if (!data) return;
    
    if (data.llamado) {
        renderTurnoActual(data.llamado);
    }
    renderCola(data.cola || []);
}
```

Buenas prácticas:
- Usar const/let, no var
- Usar arrow functions
- Mantener funciones pequeñas
- Comentarios significativos

### CSS

```css
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 16px;
}
```

## Timeline de Revisión

- 24h: Primera revisión
- 48h: Feedback si se requieren cambios
- 1 semana: Decisión final

## Criterios de Revisión

- Código limpio y legible
- Cambios limitados y enfocados
- Documentación actualizada
- Tests si es aplicable
- Sin conflictos con main
- Commits bien nombrados
- Sigue estándares del proyecto

## Areas donde Puedo Ayudar

Fácil (Principiantes):
- Mejorar documentación
- Corregir typos
- Agregar comentarios
- Tests básicos

Medio:
- Arreglar bugs
- Refactorizar código
- Agregar validaciones
- Mejorar UI/UX

Avanzado:
- Nuevas características
- Integración de sistemas
- Seguridad
- Performance

## Recursos

- Git: https://git-scm.com/doc
- PHP PSR: https://www.php-fig.org/
- MDN Docs: https://developer.mozilla.org/
- Socket.IO: https://socket.io/docs/
