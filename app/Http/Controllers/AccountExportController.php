<?php

namespace App\Http\Controllers;

use App\Features\M0Foundations;
use App\Jobs\ExportAccountData;
use App\Services\AccountExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportController extends Controller
{
    /**
     * Small exports download straight away; large ones are queued and emailed.
     */
    public function store(Request $request): BinaryFileResponse|RedirectResponse
    {
        abort_unless(Feature::active(M0Foundations::class), 404);

        $user = $request->user();

        if (AccountExporter::shouldQueue($user)) {
            ExportAccountData::dispatch($user);

            return back()->with('status', 'account-export-queued');
        }

        $path = tempnam(sys_get_temp_dir(), 'skylight-export-');
        AccountExporter::writeArchive($user, $path);

        return response()
            ->download($path, 'skylight-export-'.now()->format('Y-m-d').'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    /**
     * Signed, expiring link from the "export ready" email. The file must also
     * belong to the signed-in user, so a forwarded link is useless to others.
     */
    public function download(Request $request, string $file): StreamedResponse
    {
        abort_unless(preg_match('/^[0-9a-f-]{36}\.zip$/', $file) === 1, 404);

        $path = "exports/{$request->user()->id}/{$file}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, 'skylight-export.zip');
    }
}
