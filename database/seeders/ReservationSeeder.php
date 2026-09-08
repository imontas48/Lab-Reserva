<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea reservas de prueba para demostrar el sistema de estados dinámicos:
     * - Reserva ACTIVA (en uso ahora)
     * - Reserva FUTURA (programada para después)
     * - Equipo DISPONIBLE (sin reservas)
     * - Equipo FUERA DE SERVICIO (is_operational = false)
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->error(' No hay usuarios en la base de datos. Ejecuta primero: php artisan db:seed --class=AdminUserSeeder');

            return;
        }

        $lab = Lab::where('is_active', true)->first();

        if (! $lab) {
            $this->command->error(' No hay laboratorios activos en la base de datos.');

            return;
        }

        // Obtener equipos existentes
        $equipments = Equipment::where('is_operational', true)->get();

        // Si no hay suficientes equipos, crear algunos de prueba
        if ($equipments->count() < 4) {
            $this->command->info('️  Creando equipos de prueba...');

            $equipmentsNeeded = 4 - $equipments->count();

            for ($i = 1; $i <= $equipmentsNeeded; $i++) {
                $newEquipment = Equipment::create([
                    'lab_id' => $lab->id,
                    'identifier' => 'PC-DEMO-'.str_pad($i, 3, '0', STR_PAD_LEFT),
                    'type' => 'Computadora',
                    'specifications' => 'CPU: Intel Core i5, RAM: 8GB, Almacenamiento: 256GB SSD',
                    'is_operational' => true,
                ]);

                $equipments->push($newEquipment);
                $this->command->line("   ✓ Equipo creado: {$newEquipment->identifier}");
            }
        }

        // Ahora tenemos al menos 4 equipos
        $equipments = Equipment::where('is_operational', true)->take(4)->get();

        // ========================================================================
        // 1. RESERVA ACTIVA - Estado: "IN_USE"
        // ========================================================================
        $equipment1 = $equipments[0];

        Reservation::create([
            'user_id' => $user->id,
            'equipment_id' => $equipment1->id,
            'start_time' => Carbon::now()->subHour(),        // Empezó hace 1 hora
            'end_time' => Carbon::now()->addHours(2),        // Termina en 2 horas
            'status' => 'confirmed',
        ]);

        $this->command->info(" Reserva ACTIVA creada para equipo: {$equipment1->identifier}");
        $this->command->info('   Estado esperado: IN_USE (En uso hasta '.Carbon::now()->addHours(2)->format('H:i').')');

        // ========================================================================
        // 2. RESERVA FUTURA - Estado: "RESERVED"
        // ========================================================================
        $equipment2 = $equipments[1];

        Reservation::create([
            'user_id' => $user->id,
            'equipment_id' => $equipment2->id,
            'start_time' => Carbon::now()->addHours(3),      // Empieza en 3 horas
            'end_time' => Carbon::now()->addHours(5),        // Termina en 5 horas
            'status' => 'confirmed',
        ]);

        $this->command->info(" Reserva FUTURA creada para equipo: {$equipment2->identifier}");
        $this->command->info('   Estado esperado: RESERVED (Reservado para '.Carbon::now()->addHours(3)->format('d/m H:i').')');

        // ========================================================================
        // 3. EQUIPO DISPONIBLE - Estado: "AVAILABLE"
        // ========================================================================
        if (isset($equipments[2])) {
            $equipment3 = $equipments[2];
            $this->command->info(" Equipo SIN reservas: {$equipment3->identifier}");
            $this->command->info('   Estado esperado: AVAILABLE (Disponible)');
        }

        // ========================================================================
        // 4. EQUIPO FUERA DE SERVICIO - Estado: "OUT_OF_SERVICE"
        // ========================================================================
        if (isset($equipments[3])) {
            $equipment4 = $equipments[3];
            $equipment4->update(['is_operational' => false]);

            $this->command->info(" Equipo marcado como NO operacional: {$equipment4->identifier}");
            $this->command->info('   Estado esperado: OUT_OF_SERVICE (Equipo en mantenimiento)');
        }

        $this->command->info("\n Seeding completado! Verifica los estados en:");
        $this->command->info('   Frontend: http://localhost:8000/reservations/create');
        $this->command->info('   API: GET /api/v1/labs/{lab_id}/equipment');

        // Mostrar estados actuales
        $this->command->info("\n Estados calculados:");
        Equipment::with('reservations')->get()->each(function ($eq) {
            $status = $eq->getCurrentStatus();
            $icon = [
                'available' => '',
                'in_use' => '',
                'reserved' => '',
                'out_of_service' => '',
            ][$status['status']] ?? '';

            $this->command->line("   {$icon} {$eq->identifier}: {$status['details']}");
        });
    }
}
