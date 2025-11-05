document.addEventListener('DOMContentLoaded', function () {
  try {
    if (typeof L === 'undefined') {
      console.debug('Leaflet (L) no cargado — se omite inicialización del mapa.');
      return;
    }
    const mapEl = document.getElementById('map');
    if (!mapEl) {
      console.debug('mapaRegistro: contenedor #map no encontrado — inicialización omitida.');
      return;
    }
    const map = L.map(mapEl).setView([0,0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    // ...resto de inicialización...
  } catch (err) {
    console.error('mapaRegistro: error inicializando mapa', err);
  }
});

// 1. Inicializar el mapa (centrado en Buenos Aires)
const map = L.map('mapa').setView([-34.6699, -58.5635], 14);


// 2. Añadir la capa de OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// 3. Variable para el marcador y referencias a inputs
let marker;
const inputLatitud = document.getElementById('latitud');
const inputLongitud = document.getElementById('longitud');

// 4. Escuchar clics en el mapa
map.on('click', function(e) {
    const lat = e.latlng.lat;
    const lon = e.latlng.lng;

    // 5. Actualizar inputs ocultos
    inputLatitud.value = lat;
    inputLongitud.value = lon;

    // 6. Poner/Mover el marcador
    if (marker) {
        marker.setLatLng(e.latlng);
    } else {
        marker = L.marker(e.latlng).addTo(map);
    }
    marker.bindPopup("Ubicación seleccionada").openPopup();
});