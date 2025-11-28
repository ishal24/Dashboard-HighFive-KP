<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telda extends Model
{
    use HasFactory;

    protected $table = 'teldas';

    protected $fillable = [
        'nama',
        'witel_id',
        'divisi_id',
    ];

    // Relationships
    public function witel()
    {
        return $this->belongsTo(Witel::class);
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    // HOTDA (Account Manager Daerah) relationships
    public function accountManagers()
    {
        return $this->hasMany(AccountManager::class, 'telda_id')
                    ->where('role', 'HOTDA');
    }

    // Revenue relationships (as snapshot FK)
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    // Scopes
    public function scopeByWitel($query, $witelId)
    {
        return $query->where('witel_id', $witelId);
    }

    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function scopeWithHotda($query)
    {
        return $query->with(['accountManagers' => function ($q) {
            $q->where('role', 'HOTDA');
        }]);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('nama', 'like', '%' . $search . '%');
    }

    // Helper methods
    public function getWitelNama(): ?string
    {
        return $this->witel?->nama;
    }

    public function getDivisiNama(): ?string
    {
        return $this->divisi?->nama;
    }

    public function getDivisiKode(): ?string
    {
        return $this->divisi?->kode;
    }

    public function getFullNameAttribute(): string
    {
        return $this->nama . ' - ' . $this->getWitelNama() . ' (' . $this->getDivisiKode() . ')';
    }

    // Get active HOTDA count
    public function getActiveHotdaCount(): int
    {
        return $this->accountManagers()->count();
    }

    // Check if TELDA has HOTDA assigned
    public function hasHotda(): bool
    {
        return $this->accountManagers()->exists();
    }
}