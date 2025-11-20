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
    const mailInput = document.querySelector('input[name="mail"]')
    const mensajeError = document.getElementById('mensajeError');

    // 2. Escucha el evento 'submit' del formulario
    form.addEventListener('submit', function(event) {

        event.preventDefault();
        mensajeError.textContent = '';


        if (pass1.value !== pass2.value) {

            mensajeError.textContent = 'Las contraseñas no coinciden.';
            return;
        }
        verificarMailExistente(mailInput.value).then(function(emailExiste) {
            if (emailExiste){
                mensajeError.textContent = 'El mail ya está registrado.';
                return;
            }
            form.submit();
        });
    });

    function validarMailExistente(email, callback) {
        fetch('/login/existeMail', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
            .then(response => responde.json())
    }

    // Guardar inicialización del mapa si existe el contenedor
    const mapEl = document.getElementById('map'); // usa el id real que tengas
    if (mapEl) {
        try {
            // ...tu código de inicialización del mapa (Leaflet u otro)...
            const map = L.map('map').setView([0,0], 2); // ejemplo, sustituir por tu código
            // resto de configuración del mapa...
        } catch (e) {
            console.error('Error inicializando mapa:', e);
        }
    } else {
        console.debug('Mapa: contenedor #map no encontrado, inicialización omitida.');
    }

    // Guardar addEventListener con comprobación del elemento
    const miBoton = document.getElementById('mi-boton'); // reemplaza por el id real
    if (miBoton) {
        miBoton.addEventListener('click', function(){ /* ... */ });
    } else {
        console.debug('registro.js: elemento #mi-boton no encontrado, listener omitido.');
    }

    // ejemplo para un botón; reemplaza '#mi-boton' por el id real que uses
    const miBoton2 = document.getElementById('mi-boton2');
    if (miBoton2) {
        miBoton2.addEventListener('click', function (e) {
            // ... tu lógica ...
        });
    } else {
        console.debug('registro.js: elemento #mi-boton2 no encontrado — listener omitido.');
    }

    // Otras inicializaciones que dependan del DOM...
    const boton = document.getElementById('mi-boton'); // usa el id real
    if (boton) {
        boton.addEventListener('click', function (e) {
            // ...tu lógica...
        });
    } else {
        console.debug('registro.js: elemento #mi-boton no encontrado, listener omitido.');
    }
});