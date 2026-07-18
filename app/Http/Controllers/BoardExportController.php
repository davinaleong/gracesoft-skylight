<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Services\BoardExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardExportController extends Controller
{
    public function __invoke(Request $request, Board $board): Response|StreamedResponse
    {
        abort_unless($board->workspace->hasMember($request->user()), 403);

        $filename = str($board->name)->slug()->value() ?: 'board';

        if ($request->query('format') === 'csv') {
            return $this->csv($board, $filename);
        }

        return response(BoardExporter::toJson($board), 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$filename}.json\"",
        ]);
    }

    private function csv(Board $board, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($board) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, BoardExporter::CSV_HEADERS);

            foreach (BoardExporter::toCsvRows($board) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv']);
    }
}
