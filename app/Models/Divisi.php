<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Divisi extends Model
{
    use HasFactory;

    protected $table = 'divisi';

    protected $fillable = [
        'nama',
        'kode',
    ];

    // Relationships
    public function segments()
    {
        return $this->hasMany(Segment::class);
    }

    public function teldas()
    {
        return $this->hasMany(Telda::class);
    }

    // Many-to-many relationship with AccountManager through pivot table
    public function accountManagers()
    {
        return $this->belongsToMany(AccountManager::class, 'account_manager_divisi')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    // Revenue relationships (as snapshot FK)
    public function ccRevenues()
    {
        return $this->hasMany(CcRevenue::class);
    }

    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    // Scopes
    public function scopeByKode($query, $kode)
    {
        return $query->where('kode', $kode);
    }

    public function scopeDgs($query)
    {
        return $query->where('kode', 'DGS');
    }

    public function scopeDss($query)
    {
        return $query->where('kode', 'DSS');
    }

    public function scopeDps($query)
    {
        return $query->where('kode', 'DPS');
    }

    // Helper methods
    public function isDgs(): bool
    {
        return $this->kode === 'DGS';
    }

    public function isDss(): bool
    {
        return $this->kode === 'DSS';
    }

    public function isDps(): bool
    {
        return $this->kode === 'DPS';
    }

    // Get revenue source based on divisi type
    public function getRevenueSource(): string
    {
        return ($this->isDgs() || $this->isDss()) ? 'HO' : 'BILL';
    }
}