/*document.addEventListener('DOMContentLoaded', function() {

    const loginForm = document.getElementById('registroForm');
    const contraseña = document.getElementById('contraseña');
    const confirmarContraseña = document.getElementById('confirmarContraseña');
    const mensajeError = document.getElementById('mensajeError');

    form.addEventListener('submit', function(event) {
        if (contraseña.value !== confirmarContraseña.value) {
            mensajeError.textContent = 'Las contraseñas no coinciden.';
            event.preventDefault();
        }else {
            mensajeError.textContent = '';
        }
    })
})*/

document.addEventListener('DOMContentLoaded', function() {

    // 1. Selecciona los elementos que necesitas
    const form = document.getElementById('registroForm');
    const pass1 = document.getElementById('contraseña');
    const pass2 = document.getElementById('confirmarContraseña');
    const mensajeError = document.getElementById('mensajeError');

    // 2. Escucha el evento 'submit' del formulario
    form.addEventListener('submit', function(event) {

        // 3. Compara los valores de las contraseñas
        if (pass1.value !== pass2.value) {

            // 4. Si no coinciden:
            // Muestra el mensaje de error
            mensajeError.textContent = 'Las contraseñas no coinciden.';

            // ¡IMPORTANTE! Evita que el formulario se envíe
            event.preventDefault();

        } else {
            // Si sí coinciden, limpia cualquier mensaje de error anterior
            mensajeError.textContent = '';
        }
    });
});