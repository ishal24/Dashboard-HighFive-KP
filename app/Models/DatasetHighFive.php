<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DatasetHighFive extends Model
{
    use HasFactory;

    protected $table = 'dataset_high_five';

    protected $fillable = [
        'link_spreadsheet',
        'tanggal',
        'divisi_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relationship dengan tabel divisi
     */
    public function divisi()
    {
        return $this->belongsTo(Divisi::class, 'divisi_id');
    }

    /**
     * Scope untuk filter berdasarkan divisi
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    /**
     * Scope untuk filter berdasarkan tanggal/bulan
     */
    public function scopeByMonth($query, $month, $year)
    {
        return $query->whereMonth('tanggal', $month)
                     ->whereYear('tanggal', $year);
    }

    /**
     * Accessor untuk format nama dataset
     * Format: "DSS 1 Nov 2024"
     */
    public function getFormattedNameAttribute()
    {
        $divisiName = $this->divisi->kode ?? 'Unknown'; // Fix: kode bukan kode_divisi
        $tanggal = Carbon::parse($this->tanggal)->locale('id');

        return $divisiName . ' ' . $tanggal->day . ' ' . $tanggal->shortMonthName . ' ' . $tanggal->year;
    }

    /**
     * Get all datasets untuk dropdown, grouped by divisi
     */
    public static function getDatasetOptions()
    {
        return self::with('divisi')
                   ->orderBy('divisi_id')
                   ->orderBy('tanggal', 'desc')
                   ->get()
                   ->groupBy('divisi_id');
    }
}