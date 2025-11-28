<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatasetLink extends Model
{
    use HasFactory;

    protected $table = 'dataset_links';

    protected $fillable = [
        'divisi_id',
        'link_spreadsheet',
        'is_active',
        'last_fetched_at',
        'total_snapshots',
        'last_fetch_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_fetched_at' => 'datetime',
        'total_snapshots' => 'integer',
    ];

    /**
     * Relationship: Divisi
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    /**
     * Relationship: Snapshots
     */
    public function snapshots()
    {
        return $this->hasMany(SpreadsheetSnapshot::class, 'dataset_link_id');
    }

    /**
     * Get latest snapshot
     */
    public function latestSnapshot()
    {
        return $this->hasOne(SpreadsheetSnapshot::class, 'dataset_link_id')
                    ->where('fetch_status', 'success')
                    ->latest('snapshot_date');
    }

    /**
     * Get formatted name for display
     */
    public function getFormattedNameAttribute()
    {
        return $this->divisi->kode ?? 'Unknown';
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute()
    {
        if (!$this->is_active) {
            return 'gray';
        }

        if ($this->last_fetch_status === 'success') {
            return 'green';
        }

        return 'red';
    }

    /**
     * Get status text
     */
    public function getStatusTextAttribute()
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->last_fetch_status === 'success') {
            return 'Active';
        }

        return 'Failed';
    }

    /**
     * Check if link needs refresh (last fetch > 7 days ago)
     */
    public function needsRefresh()
    {
        if (!$this->last_fetched_at) {
            return true;
        }

        return $this->last_fetched_at->diffInDays(now()) >= 7;
    }

    /**
     * Scope: Active links only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: With divisi
     */
    public function scopeWithDivisi($query)
    {
        return $query->with('divisi');
    }

    /**
     * Update fetch statistics
     */
    public function updateFetchStats($status, $error = null)
    {
        $this->update([
            'last_fetched_at' => now(),
            'last_fetch_status' => $status === 'success' ? 'success' : $error,
            'total_snapshots' => $this->snapshots()->where('fetch_status', 'success')->count(),
        ]);
    }
}