<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportRangeRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports
    ) {}

    /**
     * GET /api/v1/reports/summary
     */
    public function summary(ReportRangeRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->reports->summary($request->from(), $request->to())]);
    }

    /**
     * GET /api/v1/reports/occupancy
     */
    public function occupancy(ReportRangeRequest $request): JsonResponse
    {
        return response()->json([
            'data' => $this->reports->occupancy($request->from(), $request->to(), $request->labId()),
        ]);
    }

    /**
     * GET /api/v1/reports/export (CSV)
     */
    public function export(ReportRangeRequest $request): StreamedResponse
    {
        $from = $request->from();
        $to = $request->to();
        $filename = sprintf('reservas_%s_%s.csv', $from->toDateString(), $to->toDateString());

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            // BOM para que Excel abra el UTF-8 con acentos correctamente.
            fwrite($handle, "\xEF\xBB\xBF");

            foreach ($this->reports->exportRows($from, $to) as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
