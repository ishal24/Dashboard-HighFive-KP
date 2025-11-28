<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segment extends Model
{
    use HasFactory;

    protected $table = 'segments';

    protected $fillable = [
        'lsegment_ho',
        'ssegment_ho',
        'divisi_id',
    ];

    // Relationships
    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function corporateCustomers()
    {
        return $this->hasMany(CorporateCustomer::class);
    }

    // Revenue relationships (as snapshot FK)
    public function ccRevenues()
    {
        return $this->hasMany(CcRevenue::class);
    }

    // Scopes
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function scopeByKode($query, $kode)
    {
        return $query->where('ssegment_ho', $kode);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('lsegment_ho', 'like', '%' . $search . '%')
              ->orWhere('ssegment_ho', 'like', '%' . $search . '%');
        });
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->lsegment_ho . ' (' . $this->ssegment_ho . ')';
    }

    // Helper methods
    public function getDivisiKode(): ?string
    {
        return $this->divisi?->kode;
    }

    public function getDivisiNama(): ?string
    {
        return $this->divisi?->nama;
    }
}