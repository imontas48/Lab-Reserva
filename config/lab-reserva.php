<?php

/*
|--------------------------------------------------------------------------
| Reglas de negocio de Lab-Reserva
|--------------------------------------------------------------------------
|
| Cuotas y ventanas de reserva por rol base (users.role). Un valor null
| significa "sin limite". Se leen desde el entorno para que cada institucion
| ajuste su politica sin tocar codigo; los valores por defecto reproducen la
| politica recomendada: el estudiante reserva poco y a corto plazo, el
| profesor planifica el semestre.
|
*/

return [
    'reservations' => [
        'min_minutes' => 30,

        // Horas antes del inicio a las que se envia el recordatorio.
        'reminder_hours_before' => env('LAB_RESERVA_REMINDER_HOURS_BEFORE', 24),

        // Semanas maximas de una serie recurrente cuando no hay periodo
        // academico vigente que la acote.
        'recurrence_max_weeks' => env('LAB_RESERVA_RECURRENCE_MAX_WEEKS', 16),

        'roles' => [
            'student' => [
                'max_active' => env('LAB_RESERVA_STUDENT_MAX_ACTIVE', 3),
                'max_advance_days' => env('LAB_RESERVA_STUDENT_MAX_ADVANCE_DAYS', 14),
                'max_hours' => env('LAB_RESERVA_STUDENT_MAX_HOURS', 4),
            ],
            'teacher' => [
                'max_active' => env('LAB_RESERVA_TEACHER_MAX_ACTIVE', 10),
                'max_advance_days' => env('LAB_RESERVA_TEACHER_MAX_ADVANCE_DAYS', 60),
                'max_hours' => env('LAB_RESERVA_TEACHER_MAX_HOURS', 8),
            ],
            'admin' => [
                'max_active' => env('LAB_RESERVA_ADMIN_MAX_ACTIVE'),
                'max_advance_days' => env('LAB_RESERVA_ADMIN_MAX_ADVANCE_DAYS'),
                'max_hours' => env('LAB_RESERVA_ADMIN_MAX_HOURS', 8),
            ],
        ],
    ],

    /*
    | Check-in y politica de inasistencias (inspirado en LibCal): el usuario
    | confirma su llegada con el codigo de la reserva; si no lo hace dentro
    | del periodo de gracia, la reserva pasa a no_show y libera la franja.
    | Tras max_strikes inasistencias en window_days se bloquean las reservas
    | durante block_days.
    */
    'check_in' => [
        'opens_minutes_before' => env('LAB_RESERVA_CHECKIN_OPENS_BEFORE', 10),
        'grace_minutes' => env('LAB_RESERVA_CHECKIN_GRACE', 15),
    ],

    'no_show' => [
        'max_strikes' => env('LAB_RESERVA_NO_SHOW_MAX_STRIKES', 3),
        'window_days' => env('LAB_RESERVA_NO_SHOW_WINDOW_DAYS', 30),
        'block_days' => env('LAB_RESERVA_NO_SHOW_BLOCK_DAYS', 7),
    ],
];
