<?php

namespace Tests\Feature\Notifications;

use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\LabReservationRequested;
use App\Notifications\ReservationApproved;
use App\Notifications\ReservationCancelledByAdmin;
use App\Notifications\ReservationRejected;
use App\Notifications\ReservationReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReservationNotificationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function labPayload(Lab $lab): array
    {
        return [
            'lab_id' => $lab->id,
            'purpose' => 'Clase de prueba',
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('10:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('12:00:00')->toIso8601String(),
        ];
    }

    public function test_una_solicitud_avisa_a_los_administradores(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $otroAdmin = User::factory()->admin()->create();
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', $this->labPayload($lab))->assertCreated();

        Notification::assertSentTo([$admin, $otroAdmin], LabReservationRequested::class);
    }

    public function test_la_reserva_directa_del_administrador_no_genera_solicitud(): void
    {
        Notification::fake();
        $this->actingAsAdmin();

        $this->postJson('/api/v1/lab-reservations', $this->labPayload(Lab::factory()->create()))->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_aprobar_y_rechazar_avisan_al_solicitante(): void
    {
        Notification::fake();
        $teacher = User::factory()->teacher()->create();
        $aprobada = Reservation::factory()->forLab(Lab::factory()->create())->forUser($teacher)->future()->create();
        $rechazada = Reservation::factory()->forLab(Lab::factory()->create())->forUser($teacher)->future()->create();
        $this->actingAsAdmin();

        $this->patchJson("/api/v1/reservations/{$aprobada->id}/approve")->assertOk();
        $this->patchJson("/api/v1/reservations/{$rechazada->id}/reject", ['reason' => 'Sin cupo'])->assertOk();

        Notification::assertSentTo($teacher, ReservationApproved::class);
        Notification::assertSentTo($teacher, ReservationRejected::class, function (ReservationRejected $n) {
            return str_contains($n->body(), 'Sin cupo');
        });
    }

    public function test_cancelar_una_reserva_ajena_avisa_al_dueño_pero_cancelar_la_propia_no(): void
    {
        Notification::fake();
        $student = User::factory()->student()->create();
        $ajena = Reservation::factory()->forUser($student)->future()->create();
        $this->actingAsAdmin();
        $this->patchJson("/api/v1/reservations/{$ajena->id}/cancel")->assertOk();

        Notification::assertSentTo($student, ReservationCancelledByAdmin::class);

        $propia = Reservation::factory()->forUser($student)->future()->create();
        $this->actingAs($student, 'sanctum');
        $this->patchJson("/api/v1/reservations/{$propia->id}/cancel")->assertOk();

        Notification::assertCount(1);
    }

    public function test_el_recordatorio_se_envia_una_sola_vez_y_solo_a_las_proximas(): void
    {
        Notification::fake();
        $student = User::factory()->student()->create();
        $proxima = Reservation::factory()->forUser($student)
            ->between(now()->addHours(3), now()->addHours(4))->confirmed()->create();
        Reservation::factory()->forUser($student)
            ->between(now()->addDays(5), now()->addDays(5)->addHour())->confirmed()->create();

        $this->artisan('reservations:send-reminders')->assertSuccessful();
        $this->artisan('reservations:send-reminders')->assertSuccessful();

        Notification::assertSentToTimes($student, ReservationReminder::class, 1);
        $this->assertNotNull($proxima->fresh()->reminder_sent_at);
    }

    public function test_el_centro_de_notificaciones_es_personal(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otro = User::factory()->teacher()->create();
        $reserva = Reservation::factory()->forLab(Lab::factory()->create())->forUser($teacher)->future()->create();

        // Envio real (sin fake) por el canal database.
        $teacher->notify((new ReservationApproved($reserva->load('lab'))));
        $otro->notify((new ReservationApproved($reserva)));

        $this->actingAs($teacher, 'sanctum');

        $this->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread', 1);

        $list = $this->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(1, 'data');
        $id = $list->json('data.0.id');
        $this->assertSame('reservation_approved', $list->json('data.0.key'));

        $this->patchJson("/api/v1/notifications/{$id}/read")->assertOk()->assertJsonPath('data.is_read', true);
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('data.unread', 0);

        // La del otro usuario no es alcanzable.
        $ajena = $otro->notifications()->first();
        $this->patchJson("/api/v1/notifications/{$ajena->id}/read")->assertNotFound();
    }
}
