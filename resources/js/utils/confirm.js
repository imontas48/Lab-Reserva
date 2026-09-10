import Swal from 'sweetalert2';

/**
 * Diálogo de confirmación destructiva, único para toda la aplicación.
 *
 * Convivían tres mecanismos distintos para la misma pregunta: SweetAlert2 en
 * laboratorios y equipos, `confirm()` nativo en reservas y software, y un modal
 * escrito a mano en roles. Además de la incoherencia visual, `confirm()` bloquea
 * el hilo y no se puede estilar ni traducir.
 *
 * @param {{ title?: string, html: string, confirmText?: string }} options
 * @returns {Promise<boolean>} true si el usuario confirma
 */
export async function confirmDestructive({
    title = '¿Estás seguro?',
    html,
    confirmText = 'Sí, eliminar',
}) {
    const { isConfirmed } = await Swal.fire({
        title,
        html,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        // El foco arranca en Cancelar: en un diálogo destructivo, un Enter
        // distraído no debe borrar nada.
        focusCancel: true,
    });

    return isConfirmed;
}

/**
 * Confirmación de una acción NO destructiva pero con consecuencias (aprobar
 * una solicitud, por ejemplo). Misma apariencia que la destructiva, con el
 * botón principal en el color de acción y el foco en confirmar.
 *
 * @param {{ title?: string, html: string, confirmText?: string }} options
 * @returns {Promise<boolean>} true si el usuario confirma
 */
export async function confirmAction({
    title = '¿Confirmar?',
    html,
    confirmText = 'Sí, continuar',
}) {
    const { isConfirmed } = await Swal.fire({
        title,
        html,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    return isConfirmed;
}
