<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestoreBackupRequest;
use App\Http\Requests\Admin\UpdateBackupSettingsRequest;
use App\Http\Requests\Admin\UploadBackupRequest;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backups,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Backups/Index', [
            'backups' => $this->backups->list(),
            'destination' => $this->backups->destinationInfo(),
            'drives' => $this->backups->availableDrives(),
            'max' => $this->backups->max(),
        ]);
    }

    public function store(): RedirectResponse
    {
        try {
            $backup = $this->backups->create();
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'No se pudo generar el respaldo: '.$e->getMessage());
        }

        $message = "Respaldo {$backup['name']} generado ({$backup['size_human']}).";

        if ($backup['pruned'] > 0) {
            $message .= " Se eliminó {$backup['pruned']} respaldo antiguo por límite de retención.";
        }

        return redirect()->route('admin.backups.index')->with('success', $message);
    }

    public function restore(RestoreBackupRequest $request): RedirectResponse
    {
        try {
            $this->backups->restore($request->string('file')->toString());
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'No se pudo restaurar el respaldo: '.$e->getMessage());
        }

        return redirect()->route('admin.backups.index')
            ->with('success', 'Respaldo restaurado correctamente. Vuelve a iniciar sesión si es necesario.');
    }

    public function uploadRestore(UploadBackupRequest $request): RedirectResponse
    {
        try {
            /** @var \Illuminate\Http\UploadedFile $uploaded */
            $uploaded = $request->file('backup_file');
            $this->backups->restoreUploadedFile($uploaded);
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'No se pudo restaurar el archivo: '.$e->getMessage());
        }

        return redirect()->route('admin.backups.index')
            ->with('success', 'Archivo restaurado correctamente. Vuelve a iniciar sesión si es necesario.');
    }

    public function update(UpdateBackupSettingsRequest $request): RedirectResponse
    {
        try {
            $this->backups->setDrive($request->input('drive') ?: null);
            $this->backups->setMax((int) $request->input('max'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'No se pudo guardar la configuración: '.$e->getMessage());
        }

        return redirect()->route('admin.backups.index')
            ->with('success', 'Configuración de respaldos actualizada.');
    }

    public function download(string $file): BinaryFileResponse|RedirectResponse
    {
        try {
            $path = $this->backups->downloadPath($file);
        } catch (\Throwable) {
            return redirect()->route('admin.backups.index')
                ->with('error', 'El respaldo no existe.');
        }

        return response()->download($path);
    }
}
