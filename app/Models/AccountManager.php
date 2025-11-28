<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountManager extends Model
{
    use HasFactory;

    protected $table = 'account_managers';

    public const ROLE_AM    = 'AM';
    public const ROLE_HOTDA = 'HOTDA';

    protected $fillable = [
        'nama',
        'nik',
        'role',       // AM | HOTDA
        'divisi_id',  // home division (nullable)
        'witel_id',   // nullable
        'telda_id',   // nullable, wajib jika HOTDA
    ];

    protected $casts = [
        'role' => 'string',
    ];

    // --- Relationships ---
    public function divisi()     { return $this->belongsTo(Divisi::class); }
    public function witel()      { return $this->belongsTo(Witel::class); }
    public function telda()      { return $this->belongsTo(Telda::class); }
    public function user()       { return $this->hasOne(User::class); }
    public function amRevenues() { return $this->hasMany(AmRevenue::class); }

    // Opsional: multi-divisi via pivot
    public function divisis()
    {
        return $this->belongsToMany(Divisi::class, 'account_manager_divisi')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    // --- Scopes ---
    public function scopeAm($q)        { return $q->where('role', self::ROLE_AM); }
    public function scopeHotda($q)     { return $q->where('role', self::ROLE_HOTDA); }
    public function scopeByWitel($q,$id){ return $q->where('witel_id',$id); }
    public function scopeByTelda($q,$id){ return $q->where('telda_id',$id); }
    public function scopeByDivisi($q,$id){ return $q->where('divisi_id',$id); }
    public function scopeSearch($q,$t)
    {
        return $q->where(fn($w)=>$w->where('nama','like',"%{$t}%")
                                   ->orWhere('nik','like',"%{$t}%"));
    }

    // --- Role checks ---
    public function isAm(): bool   { return $this->role === self::ROLE_AM; }
    public function isHotda(): bool{ return $this->role === self::ROLE_HOTDA; }

    // --- Helpers ---
    public function getPrimaryDivisi()
    {
        // Utamakan kolom langsung (home division)
        if ($this->divisi) return $this->divisi;

        // Fallback: pivot primary (kalau kamu pakai pivot)
        return $this->divisis()->wherePivot('is_primary', 1)->first();
    }

    public function getPrimaryDivisiNama(): ?string
    {
        $d = $this->divisi ?? $this->divisis()->wherePivot('is_primary',1)->first();
        return $d?->nama;
    }

    public function getDivisiKodes(): array
    {
        $codes = $this->divisi ? [$this->divisi->kode] : [];
        return array_values(array_unique(array_merge(
            $codes,
            $this->divisis()->pluck('kode')->toArray()
        )));
    }

    public function getDisplayNameAttribute(): string
    {
        $role = $this->isHotda() ? self::ROLE_HOTDA : self::ROLE_AM;
        $loc  = $this->isHotda() ? $this->telda?->nama : $this->witel?->nama;
        return "{$this->nama} ({$role}" . ($loc ? " - {$loc}" : '') . ')';
    }

    // --- Aggregates ---
    public function getMonthlyRevenue(int $year, int $month)
    {
        return $this->amRevenues()->where('tahun',$year)->where('bulan',$month)->sum('real_revenue');
    }

    public function getYearlyRevenue(int $year)
    {
        return $this->amRevenues()->where('tahun',$year)->sum('real_revenue');
    }

    // --- Business rules ---
    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $am) {
            if (!in_array($am->role, [self::ROLE_AM, self::ROLE_HOTDA], true)) {
                throw new \InvalidArgumentException('Role tidak valid: gunakan AM atau HOTDA.');
            }
            if ($am->isHotda() && is_null($am->telda_id)) {
                throw new \InvalidArgumentException('HOTDA wajib memiliki telda_id.');
            }
            if ($am->isAm() && !is_null($am->telda_id)) {
                throw new \InvalidArgumentException('AM tidak boleh memiliki telda_id.');
            }
        });
    }
}
