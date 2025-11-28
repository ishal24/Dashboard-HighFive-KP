<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RevenueImport;
use App\Models\User;

class ImportRevenueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    protected $originalFilename;

    /**
     * Jumlah percobaan maksimum untuk job ini
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Batas waktu dalam detik sebelum job dianggap timeout
     *
     * @var int
     */
    public $timeout = 3600; // 1 jam

    /**
     * Create a new job instance.
     *
     * @param string $filePath Path file di storage
     * @param int $userId ID user yang melakukan import
     * @param string $originalFilename Nama file asli yang diupload
     * @return void
     */
    public function __construct($filePath, $userId, $originalFilename = null)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->originalFilename = $originalFilename;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Set batas waktu dan memori yang lebih tinggi
        ini_set('max_execution_time', 0); // Tidak ada batas waktu
        ini_set('memory_limit', '1G');    // Tingkatkan batas memori

        Log::info('Memulai job import revenue', [
            'file_path' => $this->filePath,
            'user_id' => $this->userId,
            'original_filename' => $this->originalFilename
        ]);

        try {
            // Pastikan file ada
            if (!file_exists(public_path($this->filePath))) {
                Log::error('File import tidak ditemukan', [
                    'file_path' => $this->filePath,
                    'public_path' => public_path($this->filePath)
                ]);
                throw new \Exception('File import tidak ditemukan: ' . $this->filePath);
            }

            Log::info('File ditemukan, memulai proses import', [
                'file_path' => $this->filePath,
                'file_size' => filesize(public_path($this->filePath))
            ]);

            // Import Excel
            $import = new RevenueImport();
            Excel::import($import, public_path($this->filePath));

            // Dapatkan hasil import
            $results = $import->getImportResults();
            $importedCount = $results['imported'];
            $duplicateCount = $results['duplicates'];
            $errorCount = $results['errors'];
            $errorDetails = $results['error_details'] ?? [];

            // Buat pesan hasil
            $message = "$importedCount data Revenue berhasil diimpor.";
            if ($duplicateCount > 0) {
                $message .= " $duplicateCount data duplikat diperbarui.";
            }
            if ($errorCount > 0) {
                $message .= " $errorCount data gagal diimpor.";
            }

            // Simpan log hasil
            Log::info('Import revenue selesai', [
                'message' => $message,
                'imported' => $importedCount,
                'duplicates' => $duplicateCount,
                'errors' => $errorCount,
                'user_id' => $this->userId
            ]);

            // Simpan ke cache untuk ditampilkan saat user reload halaman
            \Illuminate\Support\Facades\Cache::put('import_result_' . $this->userId, [
                'message' => $message,
                'error_details' => $errorDetails,
                'timestamp' => now()->toDateTimeString()
            ], 3600); // simpan selama 1 jam

            // Hapus file setelah selesai
            if (file_exists(public_path($this->filePath))) {
                unlink(public_path($this->filePath));
                Log::info('File temp berhasil dihapus', ['file_path' => $this->filePath]);
            }

        } catch (\Exception $e) {
            Log::error('Error saat import revenue: ' . $e->getMessage(), [
                'exception' => $e,
                'file_path' => $this->filePath,
                'user_id' => $this->userId,
                'trace' => $e->getTraceAsString()
            ]);

            // Simpan error ke cache untuk ditampilkan ke user
            \Illuminate\Support\Facades\Cache::put('import_error_' . $this->userId, [
                'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 3600);

            // Propagate exception untuk dihandle oleh Laravel queue
            $this->fail($e);
        }
    }

    /**
     * Handle kegagalan job.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Import revenue job gagal: ' . $exception->getMessage(), [
            'exception' => $exception,
            'file_path' => $this->filePath,
            'user_id' => $this->userId
        ]);

        // Simpan info kegagalan untuk ditampilkan ke user
        \Illuminate\Support\Facades\Cache::put('import_failed_' . $this->userId, [
            'message' => 'Import gagal: ' . $exception->getMessage(),
            'timestamp' => now()->toDateTimeString()
        ], 3600);

        // Hapus file temp jika masih ada
        if (file_exists(public_path($this->filePath))) {
            try {
                unlink(public_path($this->filePath));
                Log::info('File temp berhasil dihapus setelah error', ['file_path' => $this->filePath]);
            } catch (\Exception $e) {
                Log::error('Gagal menghapus file temp setelah error: ' . $e->getMessage());
            }
        }
    }
}