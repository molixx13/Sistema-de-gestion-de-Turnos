function escHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function mostrarAlerta(msg, tipo, contenedor) {
    const el = document.createElement('div');
    el.className = 'alert alert-' + tipo;
    el.textContent = msg;
    if (contenedor) {
        contenedor.innerHTML = '';
        contenedor.appendChild(el);
    }
    setTimeout(() => el.remove(), 4000);
}
