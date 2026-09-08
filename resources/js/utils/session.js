/**
 * Almacenamiento del token de sesión y punto único de cierre de sesión.
 *
 * Existe para romper el ciclo de importación entre el cliente HTTP y el store
 * de autenticación: el interceptor de 401 necesita limpiar la sesión, y el
 * store necesita el cliente HTTP para hacer login. Ambos dependen de este
 * módulo, que no depende de ninguno.
 *
 * Sobre el mecanismo elegido: la aplicación usa **token Bearer**, no la
 * autenticación por cookie de Sanctum. Antes convivían los dos —el cliente
 * enviaba `withCredentials` y negociaba el token CSRF, y además mandaba el
 * Bearer— sin que nada decidiera cuál mandaba. Todas las rutas de la API están
 * tras `auth:sanctum`, que acepta el Bearer, así que se conserva ese y se
 * elimina la maquinaria de cookies.
 *
 * El token vive en localStorage, lo que lo expone a XSS. Es una concesión
 * consciente frente a una cookie HttpOnly, que exigiría volver al modo con
 * estado. Se compensa con caducidad en el servidor (config/sanctum.php) y con
 * el cierre de sesión que revoca todos los tokens del usuario.
 */

const TOKEN_KEY = 'auth_token';

let onUnauthorized = null;

/**
 * Registra qué hacer cuando el servidor rechaza la sesión.
 * Lo llama el store de autenticación al inicializarse.
 *
 * @param {() => void} handler
 */
export function setUnauthorizedHandler(handler) {
    onUnauthorized = handler;
}

export function getToken() {
    try {
        return localStorage.getItem(TOKEN_KEY);
    } catch {
        // Modo privado o almacenamiento bloqueado: se opera sin sesión
        // persistida en vez de reventar el arranque de la aplicación.
        return null;
    }
}

export function setToken(token) {
    try {
        localStorage.setItem(TOKEN_KEY, token);
    } catch {
        /* sin persistencia; la sesión dura lo que la pestaña */
    }
}

export function clearToken() {
    try {
        localStorage.removeItem(TOKEN_KEY);
    } catch {
        /* nada que limpiar */
    }
}

/**
 * Cierra la sesión en el cliente.
 *
 * El interceptor de 401 llamaba a `router.push('/login')` sin limpiar nada, de
 * modo que el guard del router seguía viendo `isAuthenticated === true` y
 * rebotaba a `/dashboard`, que volvía a pedir datos, que volvían a dar 401:
 * un bucle de navegación con una ráfaga de peticiones detrás.
 */
export function terminateSession() {
    clearToken();

    if (onUnauthorized) {
        onUnauthorized();
    }
}
