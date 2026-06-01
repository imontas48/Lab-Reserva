<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\equipment;
use App\Models\reservations;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', reservations::class);

        $filters = [
            'search' => $request->input('search'),
            'user_id' => $request->input('user_id'),
            'equipment_id' => $request->input('equipment_id'),
            'lab_id' => $request->input('lab_id'),
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'sort_by' => $request->input('sort_by', 'start_time'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page');

        $reservations = $this->reservationService->getAllReservations($filters, $perPage);

        return ReservationResource::collection($reservations);
    }

    /**
     * Display reservations for the authenticated user.
     * GET /api/v1/my-reservations
     */
    public function indexForUser(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'sort_by' => $request->input('sort_by', 'start_time'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $reservations = $this->reservationService->getReservationsForUser(
            $request->user(),
            $filters,
            $perPage
        );

        return ReservationResource::collection($reservations);
    }

    /**
     * Display reservations for a specific equipment.
     * GET /api/v1/equipment/{equipment}/reservations
     */
    public function indexForEquipment(equipment $equipment, Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status', 'confirmed'), // Por defecto solo confirmadas
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

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
    public function show(reservations $reservation): ReservationResource
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
    public function update(UpdateReservationRequest $request, reservations $reservation): ReservationResource
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
    public function cancel(reservations $reservation): ReservationResource
    {
        $this->authorize('cancel', $reservation);

        $cancelledReservation = $this->reservationService->cancelReservation($reservation);

        return new ReservationResource($cancelledReservation);
    }

    /**
     * Display reservations filtered by user role (admin only).
     * GET /api/v1/reservations/by-role/{role}
     *
     * @param Request $request
     * @param string $role - 'student' | 'teacher'
     */
    public function indexByRole(Request $request, string $role): AnonymousResourceCollection
    {
        $this->authorize('viewAny', reservations::class);

        // Validar que el rol solicitado sea válido
        if (!in_array($role, ['student', 'teacher'])) {
            abort(422, 'Rol no válido. Debe ser "student" o "teacher".');
        }

        $filters = [
            'search'     => $request->input('search'),
            'status'     => $request->input('status'),
            'start_date' => $request->input('start_date'),
            'end_date'   => $request->input('end_date'),
            'sort_by'    => $request->input('sort_by', 'start_time'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->integer('per_page', 15);

        $reservations = $this->reservationService->getReservationsByUserRole($role, $filters, $perPage);

        return ReservationResource::collection($reservations);
    }

    /**
     * Remove the specified reservation from storage (admin only).
     * DELETE /api/v1/reservations/{reservation}
     */
    public function destroy(reservations $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        $this->reservationService->deleteReservation($reservation);

        return response()->json([
            'message' => 'Reserva eliminada exitosamente'
        ]);
    }
}
