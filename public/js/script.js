(function () {
    'use strict';

    var CLAVE = 'sgg-tema';
    var raiz = document.documentElement;

    function leer() {
        try {
            return localStorage.getItem(CLAVE);
        } catch (e) {
            return null; // almacenamiento bloqueado: se usa el tema por defecto
        }
    }

    function guardar(tema) {
        try {
            localStorage.setItem(CLAVE, tema);
        } catch (e) {
            /* sin persistencia, pero el cambio sigue funcionando en esta página */
        }
    }

    function aplicar(tema) {
        if (tema === 'claro') {
            raiz.setAttribute('data-tema', 'claro');
        } else {
            raiz.removeAttribute('data-tema');
        }
    }

    function temaActual() {
        return raiz.getAttribute('data-tema') === 'claro' ? 'claro' : 'oscuro';
    }

    // 1) Aplicar de inmediato la preferencia guardada.
    aplicar(leer() === 'claro' ? 'claro' : 'oscuro');

    // 2) Cuando el DOM esté listo, agregar el botón a la barra de navegación.
    document.addEventListener('DOMContentLoaded', function () {
        var contenedor = document.querySelector('nav > div');
        if (!contenedor) {
            return;
        }

        var boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'btn-tema';

        function pintar() {
            var esClaro = temaActual() === 'claro';
            boton.textContent = esClaro ? '🌙 Modo noche' : '☀️ Modo día';
            boton.setAttribute('aria-label', esClaro ? 'Cambiar a modo noche' : 'Cambiar a modo día');
        }

        boton.addEventListener('click', function () {
            var nuevo = temaActual() === 'claro' ? 'oscuro' : 'claro';
            aplicar(nuevo);
            guardar(nuevo);
            pintar();
        });

        // Si el tema cambia en otra pestaña, esta se sincroniza.
        window.addEventListener('storage', function (ev) {
            if (ev.key === CLAVE) {
                aplicar(ev.newValue === 'claro' ? 'claro' : 'oscuro');
                pintar();
            }
        });

        pintar();
        contenedor.insertBefore(boton, contenedor.firstChild);
    });
})();
