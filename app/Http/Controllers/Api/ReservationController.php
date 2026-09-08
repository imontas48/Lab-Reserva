<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexReservationRequest;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    public function __construct(
        private readonly ReservationService $reservationService
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
     * Display reservations for a specific equipment.
     * GET /api/v1/equipment/{equipment}/reservations
     */
    public function indexForEquipment(Equipment $equipment, IndexReservationRequest $request): AnonymousResourceCollection
    {
        // Cualquier usuario autenticado puede consultar la ocupación de un
        // equipo: es lo que necesita para elegir una franja libre. Lo que no
        // puede es ver de quién es cada reserva, y de eso se ocupa
        // ReservationResource.
        $this->authorize('view', $equipment);

        // Por defecto solo las confirmadas: es lo que ocupa el equipo.
        $filters = $request->filters() + ['status' => 'confirmed'];

        $reservations = $this->reservationService->getReservationsForEquipment(
            $equipment,
            $filters
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
     * Display the specified reservation.
     * GET /api/v1/reservations/{reservation}
     */
    public function show(Reservation $reservation): ReservationResource
    {
        $this->authorize('view', $reservation);

        $reservation->load(['user', 'equipment.lab']);

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
    public function cancel(Reservation $reservation): ReservationResource
    {
        $this->authorize('cancel', $reservation);

        $cancelledReservation = $this->reservationService->cancelReservation($reservation);

        return new ReservationResource($cancelledReservation);
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
