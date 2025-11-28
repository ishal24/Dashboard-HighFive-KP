<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Witel extends Model
{
    use HasFactory;

    protected $table = 'witel';

    protected $fillable = [
        'nama',
    ];

    // Relationships
    public function accountManagers()
    {
        return $this->hasMany(AccountManager::class);
    }

    public function teldas()
    {
        return $this->hasMany(Telda::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Revenue relationships (as snapshot FK)
    public function ccRevenuesHo()
    {
        return $this->hasMany(CcRevenue::class, 'witel_ho_id');
    }

    public function ccRevenuesBill()
    {
        return $this->hasMany(CcRevenue::class, 'witel_bill_id');
    }

    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    // Scopes
    public function scopeWithAccountManagersCount($query)
    {
        return $query->withCount('accountManagers');
    }

    public function scopeWithTeldasCount($query)
    {
        return $query->withCount('teldas');
    }
}