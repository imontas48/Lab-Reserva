<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Regla de negocio violada por la peticion.
 *
 * Los servicios lanzaban \Exception generica para casos como "el equipo ya no
 * esta disponible" o "solo se pueden cancelar reservas confirmadas". Sin un
 * renderer registrado, el handler las convertia en HTTP 500: el cliente no
 * podia distinguir una regla de negocio de una caida del servidor, el mensaje
 * en espanol se perdia con APP_DEBUG=false y se disparaban alertas falsas.
 *
 * Se traduce a 422, el mismo codigo que ya usa la validacion, para que el
 * frontend tenga un unico camino de tratamiento de errores de entrada.
 */
class BusinessRuleException extends RuntimeException {}
