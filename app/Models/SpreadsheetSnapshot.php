<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SpreadsheetSnapshot extends Model
{
    use HasFactory;

    protected $table = 'spreadsheet_snapshots';

    protected $fillable = [
        'dataset_link_id',
        'divisi_id',
        'snapshot_date',
        'data_json',
        'total_rows',
        'total_ams',
        'total_customers',
        'total_products',
        'fetched_at',
        'fetch_status',
        'fetch_error',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'fetched_at' => 'datetime',
        'total_rows' => 'integer',
        'total_ams' => 'integer',
        'total_customers' => 'integer',
        'total_products' => 'integer',
    ];

    /**
     * Relationship: DatasetLink
     */
    public function datasetLink()
    {
        return $this->belongsTo(DatasetLink::class, 'dataset_link_id');
    }

    /**
     * Relationship: Divisi
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    /**
     * Get parsed JSON data as array
     */
    public function getParsedDataAttribute()
    {
        return json_decode($this->data_json, true);
    }

    /**
     * Get formatted snapshot date for display
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->snapshot_date)->locale('id')->isoFormat('D MMM YYYY');
    }

    /**
     * Get divisi name
     */
    public function getDivisiNameAttribute()
    {
        return $this->divisi->kode ?? 'Unknown';
    }

    /**
     * Get display name for dropdown
     */
    public function getDisplayNameAttribute()
    {
        return $this->divisi_name . ' ' . $this->formatted_date;
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        return $this->fetch_status === 'success' ? 'green' : 'red';
    }

    /**
     * Get status icon
     */
    public function getStatusIconAttribute()
    {
        return $this->fetch_status === 'success' ? 'fa-check-circle' : 'fa-times-circle';
    }

    /**
     * Check if snapshot is recent (within 7 days)
     */
    public function isRecent()
    {
        return $this->snapshot_date->diffInDays(now()) <= 7;
    }

    /**
     * Scope: Success only
     */
    public function scopeSuccess($query)
    {
        return $query->where('fetch_status', 'success');
    }

    /**
     * Scope: Failed only
     */
    public function scopeFailed($query)
    {
        return $query->where('fetch_status', 'failed');
    }

    /**
     * Scope: By divisi
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    /**
     * Scope: Latest first
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('snapshot_date', 'desc');
    }

    /**
     * Scope: With relationships
     */
    public function scopeWithRelations($query)
    {
        return $query->with(['divisi', 'datasetLink']);
    }

    /**
     * Get statistics summary
     */
    public function getStatsSummary()
    {
        return [
            'total_rows' => $this->total_rows,
            'total_ams' => $this->total_ams,
            'total_customers' => $this->total_customers,
            'total_products' => $this->total_products,
            'snapshot_date' => $this->formatted_date,
            'divisi' => $this->divisi_name,
            'status' => $this->fetch_status,
        ];
    }

    /**
     * Store parsed data from Google Sheets
     *
     * @param array $parsedData - Data from GoogleSheetService
     * @return void
     */
    public function storeSpreadsheetData(array $parsedData)
    {
        // Calculate statistics
        $totalRows = count($parsedData);
        $uniqueAMs = collect($parsedData)->pluck('am')->unique()->count();
        $uniqueCustomers = collect($parsedData)->pluck('customer_name')->unique()->count();
        $uniqueProducts = collect($parsedData)->pluck('product')->unique()->count();

        // Update snapshot
        $this->update([
            'data_json' => json_encode($parsedData),
            'total_rows' => $totalRows,
            'total_ams' => $uniqueAMs,
            'total_customers' => $uniqueCustomers,
            'total_products' => $uniqueProducts,
            'fetched_at' => now(),
            'fetch_status' => 'success',
            'fetch_error' => null,
        ]);
    }

    /**
     * Mark snapshot as failed
     *
     * @param string $error
     * @return void
     */
    public function markAsFailed($error)
    {
        $this->update([
            'fetch_status' => 'failed',
            'fetch_error' => $error,
            'fetched_at' => now(),
        ]);
    }
}