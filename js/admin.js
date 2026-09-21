/**
 * ============================================================
 * Proyecto   : InventaPlus - Sistema de Gestión de Equipos
 * Evidencia  : GA7-220501096-AA4-EV03 - Componente front-end
 * Archivo    : js/admin.js
 * Descripción: Lógica del panel de administración. Controla la
 *              navegación entre secciones, el dashboard de
 *              indicadores y los módulos CRUD de usuarios,
 *              equipos y asignaciones consumiendo la API PHP
 *              mediante Fetch API, con tablas interactivas de
 *              DataTables y notificaciones emergentes.
 * ============================================================
 */

/**
 * Agrega un parámetro de cache-busting a una URL para forzar
 * peticiones frescas al servidor (evita respuestas cacheadas).
 * @param {string} url - URL del endpoint.
 * @returns {string} URL con parámetro único de tiempo.
 */
function noCache(url) {
    const sep = url.includes('?') ? '&' : '?';
    return url + sep + '_=' + Date.now();
}

// Opciones globales de Fetch: deshabilita la caché del navegador
const fetchOpts = { cache: 'no-store' };

// Punto de entrada: inicializa todos los módulos del panel
document.addEventListener('DOMContentLoaded', function() {
    checkSession();          // Valida que exista sesión activa
    setupNavigation();       // Configura el menú lateral
    setupLogout();           // Configura el botón de cierre de sesión
    loadDashboardData();     // Carga indicadores del dashboard
    setupUsuarioForm();      // Formulario CRUD de usuarios
    setupEquipoForm();       // Formulario CRUD de equipos
    setupAsignacionForm();   // Formulario de asignación de equipos
});

/* ─── SESIÓN Y NAVEGACIÓN ───────────────────────────────── */

/**
 * Consulta al servidor (check_session.php) si hay sesión activa.
 * Si no la hay redirige al login; si existe muestra el nombre
 * del administrador en la barra superior.
 */
async function checkSession() {
    try {
        const response = await fetch(noCache('check_session.php'), fetchOpts);
        const data = await response.json();
        if (!data.logged_in) {
            window.location.href = 'index.html';
            return;
        }
        const el = document.getElementById('adminName');
        if (el && data.admin.nombre) el.textContent = data.admin.nombre;
    } catch (error) {
        console.error('Error checking session:', error);
        window.location.href = 'index.html';
    }
}

/**
 * Activa la navegación por secciones del panel: al hacer clic en
 * un enlace del sidebar oculta todas las secciones y muestra la
 * seleccionada, cargando sus datos correspondientes.
 */
function setupNavigation() {
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    const sections = document.querySelectorAll('.content-section');

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            // Obtiene la sección destino del atributo data-section
            const target = this.dataset.section;

            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            sections.forEach(s => s.classList.add('hidden'));

            const el = document.getElementById(target + '-section');
            if (el) {
                el.classList.remove('hidden');
                // Carga los datos propios de cada sección
                switch (target) {
                    case 'dashboard': loadDashboardData(); break;
                    case 'usuarios': loadUsuarios(); break;
                    case 'equipos': loadEquipos(); break;
                    case 'asignaciones': loadAsignaciones(); break;
                }
            }
        });
    });
}

/** Cierra la sesión en el servidor (logout.php) y vuelve al login. */
function setupLogout() {
    const btn = document.getElementById('logoutBtn');
    if (btn) {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            try { await fetch('logout.php', { method: 'POST' }); } catch (e) {}
            window.location.href = 'index.html';
        });
    }
}

/* ─── TABLAS Y COMPONENTES REUTILIZABLES ────────────────── */

/**
 * Pobla o actualiza una tabla DataTable con filas HTML generadas.
 * Si la tabla ya está inicializada solo refresca los datos;
 * si es la primera vez la crea con configuración en español.
 * @param {string} id   - Selector CSS de la tabla (ej: '#usuariosTable').
 * @param {Array}  rows - Arreglo de filas; cada fila es un arreglo de celdas <td>.
 */
function updateTable(id, rows) {
    if ($.fn.dataTable.isDataTable(id)) {
        const t = $(id).DataTable();
        t.clear();
        t.rows.add(rows).draw();
    } else {
        // Primera carga: construye el tbody manualmente e inicializa DataTables
        const tbody = document.querySelector(id + ' tbody');
        tbody.innerHTML = '';
        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = r.join('');
            tbody.appendChild(tr);
        });
        $(id).DataTable({
            language: dtSpanish,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            order: [],
            destroy: true
        });
    }
}

/**
 * Variante de updateTable que acepta opciones adicionales de
 * DataTables (por ejemplo, ordenamiento inicial personalizado).
 * @param {string} id   - Selector CSS de la tabla.
 * @param {Array}  rows - Filas a insertar.
 * @param {object} opts- Opciones extra para DataTables.
 */
function updateTableWithOptions(id, rows, opts) {
    if ($.fn.dataTable.isDataTable(id)) {
        const t = $(id).DataTable();
        t.clear();
        t.rows.add(rows).draw();
    } else {
        const tbody = document.querySelector(id + ' tbody');
        tbody.innerHTML = '';
        rows.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = r.join('');
            tbody.appendChild(tr);
        });
        $(id).DataTable(Object.assign({
            language: dtSpanish,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
            destroy: true
        }, opts));
    }
}

// Localización en español para los textos de DataTables
const dtSpanish = {
    "sProcessing": "Procesando...",
    "sLengthMenu": "Mostrar _MENU_ registros",
    "sZeroRecords": "No se encontraron resultados",
    "sEmptyTable": "Ningún dato disponible",
    "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
    "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
    "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
    "sSearch": "Buscar:",
    "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" },
    "oAria": { "sSortAscending": ": orden ascendente", "sSortDescending": ": orden descendente" }
};

/**
 * Convierte una fecha ISO a formato legible regional (es-CO),
 * incluyendo hora y minutos.
 * @param {string|Date} d - Fecha a formatear.
 * @returns {string} Fecha formateada (dd/mm/aaaa, hh:mm am/pm).
 */
function formatDate(d) {
    return new Date(d).toLocaleString('es-CO', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit'
    });
}

/**
 * Devuelve la clase Bootstrap del badge según el estado del equipo.
 * @param {string} e - Estado ('disponible', 'asignado', 'mantenimiento').
 * @returns {string} Clase CSS de color para el badge.
 */
function getEstadoBadgeClass(e) {
    return e === 'disponible' ? 'bg-success' : e === 'asignado' ? 'bg-warning' : e === 'mantenimiento' ? 'bg-danger' : 'bg-secondary';
}

/**
 * Traduce el estado técnico del equipo a una etiqueta legible.
 * @param {string} e - Estado técnico almacenado en la BD.
 * @returns {string} Etiqueta en español para mostrar al usuario.
 */
function getEstadoLabel(e) {
    return e === 'disponible' ? 'Disponible' : e === 'asignado' ? 'Asignado' : e === 'mantenimiento' ? 'Mantenimiento' : e;
}

/**
 * Muestra una notificación flotante auto-cerrable (5 segundos).
 * Componente reutilizado por todos los módulos del panel.
 * @param {string} type    - 'success' o 'error'.
 * @param {string} message - Mensaje a mostrar al administrador.
 */
function showAlert(type, message) {
    const c = document.getElementById('alertContainer');
    const id = 'alert-' + Date.now();
    const cls = type === 'error' ? 'alert-danger' : 'alert-success';
    c.insertAdjacentHTML('beforeend', `<div id="${id}" class="alert ${cls} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`);
    setTimeout(() => { const a = document.getElementById(id); if (a) new bootstrap.Alert(a).close(); }, 5000);
}

/* ─── DASHBOARD (HU-10) ─────────────────────────────────── */

/**
 * Carga en paralelo (Promise.all) los tres listados base y calcula
 * los indicadores: total usuarios, total equipos, asignaciones
 * activas y equipos disponibles. Además muestra las asignaciones
 * recientes con acción rápida de devolución.
 */
async function loadDashboardData() {
    try {
        // Tres peticiones simultáneas optimizan el tiempo de carga
        const [ur, er, ar] = await Promise.all([
            fetch(noCache('usuarios.php'), fetchOpts),
            fetch(noCache('equipos.php'), fetchOpts),
            fetch(noCache('asignaciones.php'), fetchOpts)
        ]);
        const usuarios = await ur.json();
        const equipos = await er.json();
        const asignaciones = await ar.json();

        // Indicadores numéricos de las tarjetas superiores
        document.getElementById('totalUsuarios').textContent = usuarios.length;
        document.getElementById('totalEquipos').textContent = equipos.length;
        document.getElementById('asignacionesActivas').textContent = asignaciones.filter(a => a.estado_asignacion === 'activa').length;
        document.getElementById('equiposDisponibles').textContent = equipos.filter(e => e.estado === 'disponible').length;

        // Tabla de asignaciones recientes con badge de estado
        const rows = asignaciones.map(a => [
            `<td>${a.nombre_usuario}</td>`,
            `<td>${a.tipo_equipo} - ${a.placa}</td>`,
            `<td>${formatDate(a.fecha_asignacion)}</td>`,
            `<td><span class="badge ${a.estado_asignacion === 'activa' ? 'bg-warning' : 'bg-success'}">${a.estado_asignacion === 'activa' ? 'Activa' : 'Devuelta'}</span></td>`,
            `<td>${a.estado_asignacion === 'activa' ? '<button class="btn btn-sm btn-success" onclick="devolverEquipo(' + a.id + ')"><i class="bi bi-check-circle"></i> Devolver</button>' : '<span class="text-muted">Devuelto</span>'}</td>`
        ]);

        updateTableWithOptions('#recentAssignmentsTable', rows, { order: [[2, 'desc']] });
    } catch (error) {
        console.error('Error loading dashboard data:', error);
    }
}

/* ─── USUARIOS (HU-02 a HU-05) ──────────────────────────── */

/** Consulta y renderiza el listado completo de usuarios. */
async function loadUsuarios() {
    try {
        const response = await fetch(noCache('usuarios.php'), fetchOpts);
        const usuarios = await response.json();

        // Construye una fila por usuario con acciones editar/eliminar
        const rows = usuarios.map(u => [
            `<td>${u.id}</td>`,
            `<td>${u.identificacion}</td>`,
            `<td>${u.nombre}</td>`,
            `<td>${u.correo_sena}</td>`,
            `<td>${u.telefono}</td>`,
            `<td>${u.area}</td>`,
            `<td><button class="btn btn-sm btn-primary" onclick="editUsuario(${u.id})"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-danger" onclick="deleteUsuario(${u.id})"><i class="bi bi-trash"></i></button></td>`
        ]);

        updateTable('#usuariosTable', rows);
    } catch (error) {
        console.error('Error loading usuarios:', error);
    }
}

/**
 * Configura el formulario modal de usuarios: decide si la operación
 * es creación (POST) o actualización (PUT) según exista el campo
 * oculto usuarioId, y limpia el formulario al cerrar el modal.
 */
function setupUsuarioForm() {
    const form = document.getElementById('usuarioForm');
    const saveBtn = document.getElementById('saveUsuario');
    const modal = document.getElementById('usuarioModal');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            // Recolecta los datos digitados en el formulario
            const usuarioId = document.getElementById('usuarioId').value;
            const data = {
                identificacion: document.getElementById('identificacion').value,
                nombre: document.getElementById('nombre').value,
                correo_sena: document.getElementById('correo_sena').value,
                telefono: document.getElementById('telefono').value,
                area: document.getElementById('area').value
            };
            // Si hay ID cargado se trata de una edición (PUT)
            if (usuarioId) data.id = usuarioId;

            try {
                const response = await fetch('usuarios.php', {
                    method: usuarioId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                // Verifica la respuesta del servidor antes de refrescar
                if (response.ok && result.message.includes(usuarioId ? 'Actualizado' : 'Registrado')) {
                    showAlert('success', result.message);
                    const instance = bootstrap.Modal.getInstance(modal);
                    if (instance) instance.hide();
                    await loadUsuarios();
                    await loadDashboardData();
                } else {
                    showAlert('error', result.message || 'Error al guardar usuario');
                }
            } catch (error) {
                console.error('Error saving usuario:', error);
                showAlert('error', 'Error de conexión');
            }
        });
    }

    // Al cerrar el modal se restablece el formulario a modo "nuevo"
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            form.reset();
            document.getElementById('usuarioId').value = '';
            document.getElementById('usuarioModalTitle').textContent = 'Nuevo Usuario';
        });
    }
}

/**
 * Consulta un usuario por su id y precarga sus datos en el
 * formulario modal para su edición (HU-04).
 * @param {number} id - Identificador del usuario a editar.
 */
async function editUsuario(id) {
    try {
        const response = await fetch(noCache('usuarios.php?id=' + id), fetchOpts);
        const usuario = await response.json();
        if (response.ok) {
            // Precarga los campos del modal con los datos actuales
            document.getElementById('usuarioId').value = usuario.id;
            document.getElementById('identificacion').value = usuario.identificacion;
            document.getElementById('nombre').value = usuario.nombre;
            document.getElementById('correo_sena').value = usuario.correo_sena;
            document.getElementById('telefono').value = usuario.telefono;
            document.getElementById('area').value = usuario.area;
            document.getElementById('usuarioModalTitle').textContent = 'Editar Usuario';
            new bootstrap.Modal(document.getElementById('usuarioModal')).show();
        } else {
            showAlert('error', 'Error al cargar usuario');
        }
    } catch (error) {
        console.error('Error loading usuario:', error);
        showAlert('error', 'Error de conexión');
    }
}

/**
 * Elimina un usuario previa confirmación del administrador (HU-05).
 * El backend bloquea la eliminación si tiene asignaciones activas.
 * @param {number} id - Identificador del usuario a eliminar.
 */
async function deleteUsuario(id) {
    if (!confirm('¿Está seguro de eliminar este usuario?')) return;
    try {
        const response = await fetch('usuarios.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await response.json();
        if (response.ok && result.message.includes('Eliminado')) {
            showAlert('success', result.message);
            await loadUsuarios();
            await loadDashboardData();
        } else {
            showAlert('error', result.message || 'Error al eliminar usuario');
        }
    } catch (error) {
        console.error('Error deleting usuario:', error);
        showAlert('error', 'Error de conexión');
    }
}

/* ─── EQUIPOS (HU-06 y HU-07) ───────────────────────────── */

/** Consulta y renderiza el inventario completo de equipos. */
async function loadEquipos() {
    try {
        const response = await fetch(noCache('equipos.php'), fetchOpts);
        const equipos = await response.json();

        const rows = equipos.map(e => {
            // Traduce los flags booleanos a etiquetas de accesorios
            const acc = [];
            if (e.tiene_mouse) acc.push('Mouse');
            if (e.tiene_teclado) acc.push('Teclado');
            if (e.tiene_cargador) acc.push('Cargador');
            if (e.tiene_rj45_tipo_c) acc.push('RJ45-C');

            return [
                `<td>${e.id}</td>`,
                `<td>${e.tipo_equipo === 'equipo_mesa' ? 'Equipo de Mesa' : 'Portátil'}</td>`,
                `<td>${e.serial}</td>`,
                `<td>${e.placa}</td>`,
                `<td>${acc.join(', ') || 'Ninguno'}</td>`,
                `<td><span class="badge ${getEstadoBadgeClass(e.estado)}">${getEstadoLabel(e.estado)}</span></td>`,
                `<td><button class="btn btn-sm btn-primary" onclick="editEquipo(${e.id})"><i class="bi bi-pencil"></i></button> <button class="btn btn-sm btn-danger" onclick="deleteEquipo(${e.id})"><i class="bi bi-trash"></i></button></td>`
            ];
        });

        updateTable('#equiposTable', rows);
    } catch (error) {
        console.error('Error loading equipos:', error);
    }
}

/**
 * Configura el formulario modal de equipos: creación (POST) o
 * edición (PUT), incluyendo checkboxes de accesorios y selector
 * de estado. Limpia el formulario al cerrarse el modal.
 */
function setupEquipoForm() {
    const form = document.getElementById('equipoForm');
    const saveBtn = document.getElementById('saveEquipo');
    const modal = document.getElementById('equipoModal');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            const equipoId = document.getElementById('equipoId').value;
            const data = {
                tipo_equipo: document.getElementById('tipo_equipo').value,
                serial: document.getElementById('serial').value,
                placa: document.getElementById('placa').value,
                tiene_mouse: document.getElementById('tiene_mouse').checked,
                tiene_teclado: document.getElementById('tiene_teclado').checked,
                tiene_cargador: document.getElementById('tiene_cargador').checked,
                tiene_rj45_tipo_c: document.getElementById('tiene_rj45_tipo_c').checked,
                estado: document.getElementById('estado').value
            };
            if (equipoId) data.id = equipoId;

            try {
                const response = await fetch('equipos.php', {
                    method: equipoId ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (response.ok && result.message.includes(equipoId ? 'Actualizado' : 'Registrado')) {
                    showAlert('success', result.message);
                    const instance = bootstrap.Modal.getInstance(modal);
                    if (instance) instance.hide();
                    await loadEquipos();
                    await loadDashboardData();
                } else {
                    showAlert('error', result.message || 'Error al guardar equipo');
                }
            } catch (error) {
                console.error('Error saving equipo:', error);
                showAlert('error', 'Error de conexión');
            }
        });
    }

    // Restablece el modal a modo "nuevo" al cerrarlo
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            form.reset();
            document.getElementById('equipoId').value = '';
            document.getElementById('equipoModalTitle').textContent = 'Nuevo Equipo';
        });
    }
}

/**
 * Consulta un equipo por id y precarga el formulario modal para
 * su edición, incluyendo el estado de los accesorios (HU-07).
 * @param {number} id - Identificador del equipo a editar.
 */
async function editEquipo(id) {
    try {
        const response = await fetch(noCache('equipos.php?id=' + id), fetchOpts);
        const equipo = await response.json();
        if (response.ok) {
            document.getElementById('equipoId').value = equipo.id;
            document.getElementById('tipo_equipo').value = equipo.tipo_equipo;
            document.getElementById('serial').value = equipo.serial;
            document.getElementById('placa').value = equipo.placa;
            document.getElementById('tiene_mouse').checked = equipo.tiene_mouse;
            document.getElementById('tiene_teclado').checked = equipo.tiene_teclado;
            document.getElementById('tiene_cargador').checked = equipo.tiene_cargador;
            document.getElementById('tiene_rj45_tipo_c').checked = equipo.tiene_rj45_tipo_c;
            document.getElementById('estado').value = equipo.estado;
            document.getElementById('equipoModalTitle').textContent = 'Editar Equipo';
            new bootstrap.Modal(document.getElementById('equipoModal')).show();
        } else {
            showAlert('error', 'Error al cargar equipo');
        }
    } catch (error) {
        console.error('Error loading equipo:', error);
        showAlert('error', 'Error de conexión');
    }
}

/**
 * Elimina un equipo previa confirmación (HU-07). El servidor
 * rechaza la operación si el equipo tiene asignaciones activas.
 * @param {number} id - Identificador del equipo a eliminar.
 */
async function deleteEquipo(id) {
    if (!confirm('¿Está seguro de eliminar este equipo?')) return;
    try {
        const response = await fetch('equipos.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await response.json();
        if (response.ok && result.message.includes('Eliminado')) {
            showAlert('success', result.message);
            await loadEquipos();
            await loadDashboardData();
        } else {
            showAlert('error', result.message || 'Error al eliminar equipo');
        }
    } catch (error) {
        console.error('Error deleting equipo:', error);
        showAlert('error', 'Error de conexión');
    }
}

/* ─── ASIGNACIONES (HU-08 y HU-09) ──────────────────────── */

/** Consulta y renderiza el historial de asignaciones con su estado. */
async function loadAsignaciones() {
    try {
        const response = await fetch(noCache('asignaciones.php'), fetchOpts);
        const asignaciones = await response.json();

        const rows = asignaciones.map(a => [
            `<td>${a.id}</td>`,
            `<td>${a.nombre_usuario}</td>`,
            `<td>${a.tipo_equipo} - ${a.placa}</td>`,
            `<td>${formatDate(a.fecha_asignacion)}</td>`,
            `<td>${a.fecha_devolucion ? formatDate(a.fecha_devolucion) : '-'}</td>`,
            `<td><span class="badge ${a.estado_asignacion === 'activa' ? 'bg-warning' : 'bg-success'}">${a.estado_asignacion === 'activa' ? 'Activa' : 'Devuelta'}</span></td>`,
            `<td>${a.estado_asignacion === 'activa' ? '<button class="btn btn-sm btn-success" onclick="devolverEquipo(' + a.id + ')"><i class="bi bi-check-circle"></i> Devolver</button>' : '<span class="text-muted">Devuelto</span>'}</td>`
        ]);

        updateTable('#asignacionesTable', rows);
    } catch (error) {
        console.error('Error loading asignaciones:', error);
    }
}

/**
 * Configura el modal de nueva asignación (HU-08): envía usuario y
 * equipo seleccionados vía POST; recarga los selectores cada vez
 * que el modal se abre y lo reinicia al cerrarse.
 */
function setupAsignacionForm() {
    const saveBtn = document.getElementById('saveAsignacion');
    const modal = document.getElementById('asignacionModal');

    if (saveBtn) {
        saveBtn.addEventListener('click', async function() {
            const data = {
                usuario_id: document.getElementById('asignacion_usuario_id').value,
                equipo_id: document.getElementById('asignacion_equipo_id').value
            };
            try {
                const response = await fetch('asignaciones.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (response.ok && result.message.includes('Registrado')) {
                    showAlert('success', result.message);
                    const instance = bootstrap.Modal.getInstance(modal);
                    if (instance) instance.hide();
                    await loadAsignaciones();
                    await loadDashboardData();
                } else {
                    showAlert('error', result.message || 'Error al realizar asignación');
                }
            } catch (error) {
                console.error('Error creating asignacion:', error);
                showAlert('error', 'Error de conexión');
            }
        });
    }

    if (modal) {
        // Cada apertura recarga los dropdowns con datos vigentes
        modal.addEventListener('show.bs.modal', async function() {
            await loadAsignacionDropdowns();
        });
        modal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('asignacionForm').reset();
        });
    }
}

/**
 * Llena los selectores del modal de asignación: todos los
 * usuarios registrados y únicamente los equipos con estado
 * 'disponible' (regla de negocio RN-01).
 */
async function loadAsignacionDropdowns() {
    try {
        // Selector de usuarios
        const usuariosResponse = await fetch(noCache('usuarios.php'), fetchOpts);
        const usuarios = await usuariosResponse.json();
        const usuarioSelect = document.getElementById('asignacion_usuario_id');
        usuarioSelect.innerHTML = '<option value="">Seleccionar...</option>';
        usuarios.forEach(u => {
            usuarioSelect.innerHTML += `<option value="${u.id}">${u.nombre} - ${u.identificacion}</option>`;
        });

        // Selector de equipos: solo disponibles para asignación
        const equiposResponse = await fetch(noCache('equipos.php'), fetchOpts);
        const equipos = await equiposResponse.json();
        const disponibles = equipos.filter(e => e.estado === 'disponible');
        const equipoSelect = document.getElementById('asignacion_equipo_id');
        equipoSelect.innerHTML = '<option value="">Seleccionar...</option>';
        disponibles.forEach(e => {
            equipoSelect.innerHTML += `<option value="${e.id}">${e.tipo_equipo} - ${e.placa} (${e.serial})</option>`;
        });
    } catch (error) {
        console.error('Error loading dropdowns:', error);
    }
}

/**
 * Registra la devolución de un equipo (HU-09): marca la asignación
 * como 'devuelta' vía PUT y libera el equipo en el backend.
 * @param {number} id - Identificador de la asignación activa.
 */
async function devolverEquipo(id) {
    if (!confirm('¿Está seguro de marcar este equipo como devuelto?')) return;
    try {
        const response = await fetch('asignaciones.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, devolver: true })
        });
        const result = await response.json();
        if (response.ok && result.message.includes('Actualizado')) {
            showAlert('success', result.message);
            await loadAsignaciones();
            await loadDashboardData();
        } else {
            showAlert('error', result.message || 'Error al devolver equipo');
        }
    } catch (error) {
        console.error('Error returning equipo:', error);
        showAlert('error', 'Error de conexión');
    }
}
