<?php

namespace App\Services;

use App\Models\FileRevision;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class LibreOfficePreviewService
{
    private ?string $resolvedBinary = null;

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
    }

    public function ensurePdfPreview(FileRevision $revision): array
    {
        $extension = strtolower($revision->extension ?: pathinfo($revision->original_name, PATHINFO_EXTENSION));

        if (!$this->supports($extension)) {
            return ['available' => false, 'message' => 'Este archivo no requiere conversión con LibreOffice.'];
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($revision->file_path)) {
            return ['available' => false, 'message' => 'No se encontró el archivo original para generar la vista previa.'];
        }

        $previewPath = $this->previewPath($revision);

        if ($disk->exists($previewPath) && !$this->previewIsStale($disk->path($revision->file_path), $disk->path($previewPath))) {
            return [
                'available' => true,
                'path' => $previewPath,
                'url' => '/storage/' . ltrim(str_replace('\\', '/', $previewPath), '/'),
                'fresh' => false,
            ];
        }

        $sourcePath = $disk->path($revision->file_path);
        $previewAbsolutePath = $disk->path($previewPath);
        File::ensureDirectoryExists(dirname($previewAbsolutePath));

        $tempDir = storage_path('app/tmp/libreoffice/' . $revision->id . '_' . uniqid());
        $profileDir = $tempDir . DIRECTORY_SEPARATOR . 'profile';
        File::ensureDirectoryExists($tempDir);
        File::ensureDirectoryExists($profileDir);

        try {
            $binary = $this->resolveBinary();

            if (!$binary) {
                return ['available' => false, 'message' => 'LibreOffice no está instalado o el binario `soffice` no está disponible para PHP.'];
            }

            $process = new Process([
                $binary,
                '--headless',
                '--nologo',
                '--nolockcheck',
                '--nodefault',
                '--norestore',
                '-env:UserInstallation=' . $this->fileUri($profileDir),
                '--convert-to',
                'pdf',
                '--outdir',
                $tempDir,
                $sourcePath,
            ]);
            $process->setTimeout((int) config('services.libreoffice.timeout', 180));
            $process->mustRun();

            $generatedPath = $tempDir . DIRECTORY_SEPARATOR . pathinfo($sourcePath, PATHINFO_FILENAME) . '.pdf';
            if (!is_file($generatedPath)) {
                return ['available' => false, 'message' => 'LibreOffice no devolvió un PDF utilizable para este archivo.'];
            }

            File::copy($generatedPath, $previewAbsolutePath);

            return [
                'available' => true,
                'path' => $previewPath,
                'url' => '/storage/' . ltrim(str_replace('\\', '/', $previewPath), '/'),
                'fresh' => true,
            ];
        } catch (ProcessFailedException $exception) {
            return [
                'available' => false,
                'message' => $this->normalizeErrorMessage($exception->getProcess()->getErrorOutput() ?: $exception->getMessage()),
            ];
        } catch (\Throwable $exception) {
            return [
                'available' => false,
                'message' => $this->normalizeErrorMessage($exception->getMessage()),
            ];
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    private function resolveBinary(): ?string
    {
        if ($this->resolvedBinary !== null) {
            return $this->resolvedBinary;
        }

        $configured = trim((string) config('services.libreoffice.binary', ''));
        $candidates = array_filter([
            $configured,
            'soffice',
            'soffice.com',
            'C:\\Program Files\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
        ]);

        foreach ($candidates as $candidate) {
            if ($this->binaryExists($candidate)) {
                return $this->resolvedBinary = $candidate;
            }
        }

        return $this->resolvedBinary = null;
    }

    private function binaryExists(string $candidate): bool
    {
        if ($candidate === '') {
            return false;
        }

        if (str_contains($candidate, '\\') || str_contains($candidate, '/')) {
            return is_file($candidate);
        }

        try {
            $probe = new Process([$candidate, '--version']);
            $probe->setTimeout(10);
            $probe->run();

            return $probe->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function previewPath(FileRevision $revision): string
    {
        return 'previews/revisions/' . $revision->id . '/preview.pdf';
    }

    private function previewIsStale(string $sourcePath, string $previewPath): bool
    {
        return !is_file($previewPath) || filemtime($sourcePath) > filemtime($previewPath);
    }

    private function normalizeErrorMessage(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message));

        if ($message === '') {
            return 'No fue posible generar la vista previa con LibreOffice.';
        }

        $lower = strtolower($message);

        if (
            str_contains($lower, 'not recognized') ||
            str_contains($lower, 'not found') ||
            str_contains($lower, 'no se reconoce')
        ) {
            return 'LibreOffice no está instalado o el binario `soffice` no está disponible para PHP.';
        }

        return $message;
    }

    private function fileUri(string $path): string
    {
        $normalized = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        return 'file:///' . ltrim($normalized, '/');
    }
}
