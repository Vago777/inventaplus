/**
 * ============================================================
 * Proyecto   : InventaPlus - Sistema de Gestión de Equipos
 * Evidencia  : GA7-220501096-AA4-EV03 - Componente front-end
 * Archivo    : js/login.js
 * Descripción: Lógica del componente front-end para el módulo
 *              de autenticación. Valida el formulario del login,
 *              consume el endpoint PHP (login.php / check_session.php)
 *              mediante Fetch API y controla la retroalimentación
 *              visual (alertas e indicador de carga).
 * ============================================================
 */

// Se ejecuta cuando el DOM está completamente cargado,
// garantizando que los elementos HTML existan antes de manipularlos.
document.addEventListener('DOMContentLoaded', function() {

    // Referencias a los elementos del formulario de inicio de sesión
    const loginForm = document.getElementById('loginForm');
    const errorAlert = document.getElementById('errorAlert');
    const successAlert = document.getElementById('successAlert');
    const loginText = document.getElementById('loginText');
    const loginSpinner = document.getElementById('loginSpinner');

    // Verifica al cargar la página si ya existe una sesión activa.
    // Si el servidor responde logged_in = true se redirige directo
    // al panel de administración (evita loguearse dos veces).
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (data.logged_in) {
                window.location.href = 'admin.html';
            }
        })
        .catch(error => {
            console.error('Error checking session:', error);
        });

    // Intercepta el envío del formulario para procesarlo con Fetch
    // en lugar del envío tradicional (recarga de página).
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            // Evita que el navegador recargue la página
            e.preventDefault();

            hideAlerts();
            showLoading(true);

            // Obtiene los valores digitados por el administrador
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                // Petición asíncrona POST al backend en formato JSON
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password
                    })
                });

                const data = await response.json();

                // Login exitoso: notifica y redirige al panel tras 1.5 s
                if (response.ok && data.success) {
                    showAlert('success', 'Login exitoso. Redirigiendo...');
                    setTimeout(() => {
                        window.location.href = 'admin.html';
                    }, 1500);
                } else {
                    // Credenciales inválidas o error devuelto por el servidor
                    showAlert('error', data.message || 'Error en el login');
                }
            } catch (error) {
                // Fallo de red o del servidor
                console.error('Error:', error);
                showAlert('error', 'Error de conexión. Intente nuevamente.');
            } finally {
                // Siempre reactiva el botón, haya fallado o no la petición
                showLoading(false);
            }
        });
    }

    /**
     * Muestra una alerta en pantalla según el tipo recibido.
     * @param {string} type    - 'success' para éxito, cualquier otro valor muestra error.
     * @param {string} message - Texto que se le comunica al administrador.
     */
    function showAlert(type, message) {
        hideAlerts();
        if (type === 'error') {
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
        } else {
            successAlert.textContent = message;
            successAlert.style.display = 'block';
        }
    }

    /** Oculta ambas alertas antes de mostrar una nueva. */
    function hideAlerts() {
        errorAlert.style.display = 'none';
        successAlert.style.display = 'none';
    }

    /**
     * Muestra u oculta el spinner de carga del botón de login
     * para impedir envíos duplicados durante la petición.
     * @param {boolean} show - true muestra el spinner, false lo oculta.
     */
    function showLoading(show) {
        if (show) {
            loginText.classList.add('d-none');
            loginSpinner.classList.remove('d-none');
        } else {
            loginText.classList.remove('d-none');
            loginSpinner.classList.add('d-none');
        }
    }
});
