<?php
/**
 * Archivo: includes/footer.php
 * Descripción: Pie de página semántico HTML5 y scripts de validación Vanilla JS.
 * Sistema: Clínica Salud Total C.A.
 */

declare(strict_types=1);
?>
    </main>

    <footer class="site-footer no-print">
        <div class="container footer-wrapper">
            <p>
                &copy; <?= date('Y') ?> <strong>Clínica Salud Total C.A.</strong> &mdash; Sistema de Gestión de Pacientes.
            </p>
            <p class="footer-meta">
                Todos los derechos reservados.
            </p>
        </div>
    </footer>

    <!-- Scripts de Validación e Interacción en JavaScript Vanilla -->
    <script>
        /**
         * Solicita confirmación al Administrador antes de eliminar un paciente.
         *
         * @param {string} nombrePaciente Nombre completo del paciente.
         * @param {string} cedula         Cédula de identidad del paciente.
         * @returns {boolean} True si el usuario confirma la operación.
         */
        function confirmarEliminacion(nombrePaciente, cedula) {
            return window.confirm(
                '¿Está seguro de que desea eliminar definitivamente al paciente ' +
                nombrePaciente + ' (C.I.: ' + cedula + ')?\n\nEsta acción no se puede deshacer.'
            );
        }

        /**
         * Muestra u oculta un mensaje de error debajo de un campo del formulario.
         *
         * @param {HTMLElement} inputElement Elemento input a marcar.
         * @param {string}      mensaje      Texto del error (vacío para limpiar).
         */
        function marcarErrorCampo(inputElement, mensaje) {
            if (!inputElement) return;
            var contenedor = inputElement.closest('.form-group');
            if (!contenedor) return;

            var spanError = contenedor.querySelector('.field-error-js');
            if (!spanError) {
                spanError = document.createElement('small');
                spanError.className = 'field-error field-error-js';
                contenedor.appendChild(spanError);
            }

            if (mensaje) {
                inputElement.classList.add('input-invalid');
                spanError.textContent = mensaje;
                spanError.style.display = 'block';
            } else {
                inputElement.classList.remove('input-invalid');
                spanError.textContent = '';
                spanError.style.display = 'none';
            }
        }

        /**
         * Valida el formulario de creación/edición de pacientes antes de enviarlo al servidor.
         *
         * @param {HTMLFormElement} formulario Formulario HTML a validar.
         * @returns {boolean} True si todos los campos cumplen las reglas.
         */
        function validarFormularioPaciente(formulario) {
            var esValido = true;

            var inputNombres   = formulario.querySelector('#nombres');
            var inputApellidos = formulario.querySelector('#apellidos');
            var inputCedula    = formulario.querySelector('#cedula_identidad');
            var inputContacto  = formulario.querySelector('#datos_contacto');

            var regexLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'.-]{2,100}$/;
            var regexCedula = /^[VEJPGvejpg]?[-]?[0-9]{5,12}$/;

            // 1. Validar Nombres
            var nombres = inputNombres ? inputNombres.value.trim() : '';
            if (nombres.length < 2) {
                marcarErrorCampo(inputNombres, 'Ingrese los nombres del paciente (mínimo 2 caracteres).');
                esValido = false;
            } else if (!regexLetras.test(nombres)) {
                marcarErrorCampo(inputNombres, 'Los nombres solo deben contener letras y espacios.');
                esValido = false;
            } else {
                marcarErrorCampo(inputNombres, '');
            }

            // 2. Validar Apellidos
            var apellidos = inputApellidos ? inputApellidos.value.trim() : '';
            if (apellidos.length < 2) {
                marcarErrorCampo(inputApellidos, 'Ingrese los apellidos del paciente (mínimo 2 caracteres).');
                esValido = false;
            } else if (!regexLetras.test(apellidos)) {
                marcarErrorCampo(inputApellidos, 'Los apellidos solo deben contener letras y espacios.');
                esValido = false;
            } else {
                marcarErrorCampo(inputApellidos, '');
            }

            // 3. Validar Cédula de Identidad
            var cedula = inputCedula ? inputCedula.value.trim().toUpperCase() : '';
            if (cedula.length === 0) {
                marcarErrorCampo(inputCedula, 'La cédula de identidad es obligatoria.');
                esValido = false;
            } else if (!regexCedula.test(cedula)) {
                marcarErrorCampo(inputCedula, 'Formato inválido. Ejemplo válido: V-12345678 o E-87654321.');
                esValido = false;
            } else {
                // Normalizar prefijo V- si el usuario solo escribió números
                if (/^[0-9]{5,12}$/.test(cedula)) {
                    inputCedula.value = 'V-' + cedula;
                } else if (/^[VEJPG][0-9]{5,12}$/.test(cedula)) {
                    inputCedula.value = cedula.charAt(0) + '-' + cedula.slice(1);
                } else {
                    inputCedula.value = cedula;
                }
                marcarErrorCampo(inputCedula, '');
            }

            // 4. Validar Datos de Contacto (Teléfono y Seguro Médico)
            var contacto = inputContacto ? inputContacto.value.trim() : '';
            if (contacto.length < 5) {
                marcarErrorCampo(inputContacto, 'Indique el teléfono de contacto y el seguro médico (mínimo 5 caracteres).');
                esValido = false;
            } else if (contacto.length > 255) {
                marcarErrorCampo(inputContacto, 'Los datos de contacto no pueden exceder los 255 caracteres.');
                esValido = false;
            } else {
                marcarErrorCampo(inputContacto, '');
            }

            return esValido;
        }
    </script>
</body>
</html>

