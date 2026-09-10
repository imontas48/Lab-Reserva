<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInReservationRequest;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Requests\RejectReservationRequest;
use App\Http\Requests\StoreLabReservationRequest;
use App\Http\Requests\StoreRecurringLabReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Services\AttendanceService;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService,
        private readonly AttendanceService $attendance
    ) {}

    /**
     * Display a listing of all reservations (admin only).
     * GET /api/v1/reservations
     */
    public function index(IndexReservationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reservation::class);

        $reservations = $this->reservationService->getAllReservations($request->filters(), $request->perPage());

        return ReservationResource::collection($reservations);
    }

    /**
     * Solicitudes pendientes de aprobación.
     * GET /api/v1/reservations/pending
     */
    public function pending(IndexReservationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewPending', Reservation::class);

        $reservations = $this->reservationService->getPendingReservations(
            $request->filters(),
            $request->perPage() ?? 15
        );

        return ReservationResource::collection($reservations);
    }

    /**
     * Display reservations for the authenticated user.
     * GET /api/v1/my-reservations
     */
    public function indexForUser(IndexReservationRequest $request): AnonymousResourceCollection
    {
        $reservations = $this->reservationService->getReservationsForUser(
            $request->user(),
            $request->filters(),
            $request->perPage() ?? 15
        );

        return ReservationResource::collection($reservations);
    }

    /**
     * Ocupación de un equipo (sus reservas y las clases de su laboratorio).
     * GET /api/v1/equipment/{equipment}/reservations
     */
    public function indexForEquipment(Equipment $equipment, IndexReservationRequest $request): AnonymousResourceCollection
    {
        // Cualquier usuario autenticado puede consultar la ocupación de un
        // equipo: es lo que necesita para elegir una franja libre. Lo que no
        // puede es ver de quién es cada reserva, y de eso se ocupa
        // ReservationResource.
        $this->authorize('view', $equipment);

        $reservations = $this->reservationService->getReservationsForEquipment(
            $equipment,
            $request->filters()
        );

        return ReservationResource::collection($reservations);
    }

    /**
     * Ocupación de un laboratorio (sus clases y las reservas de sus equipos).
     * GET /api/v1/labs/{lab}/reservations
     */
    public function indexForLab(Lab $lab, IndexReservationRequest $request): AnonymousResourceCollection
    {
        $this->authorize('view', $lab);

        $reservations = $this->reservationService->getReservationsForLab(
            $lab,
            $request->filters()
        );

        return ReservationResource::collection($reservations);
    }

    /**
     * Store a newly created reservation in storage.
     * POST /api/v1/reservations
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createReservation(
            $request->validated(),
            $request->user()
        );

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Solicita un laboratorio completo para una clase.
     * POST /api/v1/lab-reservations
     */
    public function storeLab(StoreLabReservationRequest $request): JsonResponse
    {
        $reservation = $this->reservationService->createLabReservation(
            $request->validated(),
            $request->user()
        );

        return (new ReservationResource($reservation))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Serie semanal de clases.
     * POST /api/v1/lab-reservations/recurring
     */
    public function storeRecurringLab(StoreRecurringLabReservationRequest $request): JsonResponse
    {
        $result = $this->reservationService->createRecurringLabReservations(
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'data' => [
                'recurrence_group' => $result['recurrence_group'],
                'created' => ReservationResource::collection($result['created'])->resolve(),
                'skipped' => $result['skipped'],
            ],
        ], 201);
    }

    /**
     * Registra la llegada del usuario.
     * POST /api/v1/reservations/{reservation}/check-in
     */
    public function checkIn(CheckInReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $checkedIn = $this->attendance->checkIn($reservation, $request->user(), $request->validated('code'));

        return new ReservationResource($checkedIn);
    }

    /**
     * Marca una inasistencia (administrador).
     * POST /api/v1/reservations/{reservation}/no-show
     */
    public function markNoShow(Reservation $reservation): ReservationResource
    {
        $this->authorize('markNoShow', $reservation);

        return new ReservationResource($this->attendance->markNoShow($reservation));
    }

    /**
     * Cancela las ocurrencias futuras de una serie.
     * PATCH /api/v1/reservations/{reservation}/cancel-series
     */
    public function cancelSeries(Request $request, Reservation $reservation): JsonResponse
    {
        $this->authorize('cancel', $reservation);

        $count = $this->reservationService->cancelSeries($reservation, $request->user());

        return response()->json([
            'message' => "Se cancelaron {$count} clases de la serie.",
            'data' => ['cancelled' => $count],
        ]);
    }

    /**
     * Display the specified reservation.
     * GET /api/v1/reservations/{reservation}
     */
    public function show(Reservation $reservation): ReservationResource
    {
        $this->authorize('view', $reservation);

        $reservation->load(ReservationService::DETAIL_RELATIONS);

        return new ReservationResource($reservation);
    }

    /**
     * Update the specified reservation in storage.
     * PUT/PATCH /api/v1/reservations/{reservation}
     * (Principalmente usado para cambios administrativos)
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $this->authorize('update', $reservation);

        $updatedReservation = $this->reservationService->updateReservation(
            $reservation,
            $request->validated()
        );

        return new ReservationResource($updatedReservation);
    }

    /**
     * Cancel a reservation.
     * PATCH /api/v1/reservations/{reservation}/cancel
     */
    public function cancel(Request $request, Reservation $reservation): ReservationResource
    {
        $this->authorize('cancel', $reservation);

        $cancelledReservation = $this->reservationService->cancelReservation($reservation, $request->user());

        return new ReservationResource($cancelledReservation);
    }

    /**
     * Aprueba una solicitud pendiente.
     * PATCH /api/v1/reservations/{reservation}/approve
     */
    public function approve(Request $request, Reservation $reservation): ReservationResource
    {
        $this->authorize('approve', $reservation);

        $approved = $this->reservationService->approveReservation($reservation, $request->user());

        return new ReservationResource($approved);
    }

    /**
     * Rechaza una solicitud pendiente.
     * PATCH /api/v1/reservations/{reservation}/reject
     */
    public function reject(RejectReservationRequest $request, Reservation $reservation): ReservationResource
    {
        $this->authorize('reject', $reservation);

        $rejected = $this->reservationService->rejectReservation(
            $reservation,
            $request->user(),
            $request->validated('reason')
        );

        return new ReservationResource($rejected);
    }

    /**
     * Display reservations filtered by user role (admin only).
     * GET /api/v1/reservations/by-role/{role}
     *
     * @param  string  $role  - 'student' | 'teacher'
     */
    public function indexByRole(IndexReservationRequest $request, string $role): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reservation::class);

        $reservations = $this->reservationService->getReservationsByUserRole($role, $request->filters(), $request->perPage() ?? 15);

        return ReservationResource::collection($reservations);
    }

    /**
     * Remove the specified reservation from storage (admin only).
     * DELETE /api/v1/reservations/{reservation}
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        $this->reservationService->deleteReservation($reservation);

        return response()->json([
            'message' => 'Reserva eliminada exitosamente',
        ]);
    }
}
