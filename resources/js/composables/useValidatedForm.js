import { useForm } from 'vee-validate';
import * as yup from 'yup';

/**
 * Formulario validado en cliente que además absorbe los errores del servidor.
 *
 * Los ocho formularios de la aplicación usaban `ref({})` y delegaban TODA la
 * validación en el 422 del backend: un viaje de ida y vuelta por cada errata, y
 * ningún aviso mientras se escribe. El registro ni siquiera comprobaba que las
 * dos contraseñas coincidieran.
 *
 * La validación de cliente es ADITIVA: el servidor sigue siendo la autoridad, y
 * sus errores se vuelcan sobre los mismos campos con `setErrors`, de modo que la
 * vista solo tiene un sitio del que leer errores.
 *
 * @param {import('yup').ObjectSchema} schema
 * @param {Record<string, unknown>} initialValues
 */
export function useValidatedForm(schema, initialValues = {}) {
    const form = useForm({
        validationSchema: schema,
        initialValues,
    });

    /**
     * Vuelca los errores de validación del backend sobre los campos.
     *
     * Laravel devuelve `{ errors: { campo: ['mensaje', ...] } }`; VeeValidate
     * espera un mensaje por campo.
     */
    function applyServerErrors(error) {
        const serverErrors = error?.response?.data?.errors;

        if (!serverErrors) {
            return false;
        }

        const mapped = {};
        for (const [field, messages] of Object.entries(serverErrors)) {
            mapped[field] = Array.isArray(messages) ? messages[0] : messages;
        }

        form.setErrors(mapped);

        return true;
    }

    return { ...form, applyServerErrors };
}

/**
 * Reglas reutilizables, para que los mensajes no se reescriban en cada vista.
 */
export const rules = {
    email: () => yup
        .string()
        .required('El correo electrónico es obligatorio.')
        .email('Introduce un correo electrónico válido.'),

    password: () => yup
        .string()
        .required('La contraseña es obligatoria.')
        .min(8, 'La contraseña debe tener al menos 8 caracteres.'),

    passwordConfirmation: (field = 'password') => yup
        .string()
        .required('Confirma la contraseña.')
        .oneOf([yup.ref(field)], 'Las contraseñas no coinciden.'),

    requiredText: (label, max = 255) => yup
        .string()
        .required(`${label} es obligatorio.`)
        .max(max, `${label} no puede superar los ${max} caracteres.`),

    positiveInteger: (label) => yup
        .number()
        .typeError(`${label} debe ser un número.`)
        .required(`${label} es obligatorio.`)
        .integer(`${label} debe ser un número entero.`)
        .min(1, `${label} debe ser mayor que cero.`),
};
