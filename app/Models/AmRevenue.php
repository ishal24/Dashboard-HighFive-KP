<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AmRevenue extends Model
{
    use HasFactory;

    protected $table = 'am_revenues';

    protected $fillable = [
        'account_manager_id',
        'corporate_customer_id',
        'divisi_id',
        'witel_id',
        'telda_id',
        'proporsi',
        'target_revenue',
        'real_revenue',
        'achievement_rate',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'proporsi' => 'decimal:2',
        'target_revenue' => 'decimal:2',
        'real_revenue' => 'decimal:2',
        'achievement_rate' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    // Relationships
    public function accountManager()
    {
        return $this->belongsTo(AccountManager::class);
    }

    public function corporateCustomer()
    {
        return $this->belongsTo(CorporateCustomer::class);
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function witel()
    {
        return $this->belongsTo(Witel::class);
    }

    public function telda()
    {
        return $this->belongsTo(Telda::class);
    }

    // Scopes
    public function scopeByPeriod($query, $year, $month = null)
    {
        $query = $query->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query;
    }

    public function scopeByAccountManager($query, $accountManagerId)
    {
        return $query->where('account_manager_id', $accountManagerId);
    }

    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function scopeByWitel($query, $witelId)
    {
        return $query->where('witel_id', $witelId);
    }

    public function scopeByTelda($query, $teldaId)
    {
        return $query->whereNotNull('telda_id')->where('telda_id', $teldaId);
    }

    public function scopeHotdaOnly($query)
    {
        return $query->whereNotNull('telda_id');
    }

    public function scopeAmOnly($query)
    {
        return $query->whereNull('telda_id');
    }

    public function scopeWithRelations($query)
    {
        return $query->with([
            'accountManager:id,nama,nik,role',
            'corporateCustomer:id,nama,nipnas',
            'divisi:id,nama,kode',
            'witel:id,nama',
            'telda:id,nama'
        ]);
    }

    // Accessors & Mutators
    public function getCalculatedAchievementRateAttribute(): float
    {
        if ($this->target_revenue <= 0) {
            return 0;
        }

        return round(($this->real_revenue / $this->target_revenue) * 100, 2);
    }

    public function getPeriodAttribute(): string
    {
        return sprintf('%04d-%02d', $this->tahun, $this->bulan);
    }

    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        return $months[$this->bulan] . ' ' . $this->tahun;
    }

    public function getProporsiPercentAttribute(): string
    {
        return number_format((float)$this->proporsi, 2) . '%';
    }

    // Helper methods
    public function isHotda(): bool
    {
        return !is_null($this->telda_id);
    }

    public function isAm(): bool
    {
        return is_null($this->telda_id);
    }

    public function getAccountManagerNama(): ?string
    {
        return $this->accountManager?->nama;
    }

    public function getAccountManagerRole(): ?string
    {
        return $this->accountManager?->role;
    }

    public function getCorporateCustomerNama(): ?string
    {
        return $this->corporateCustomer?->nama;
    }

    public function getDivisiKode(): ?string
    {
        // If divisi_id is null, fallback to account manager's primary divisi
        if ($this->divisi) {
            return $this->divisi->kode;
        }

        if ($this->accountManager) {
            return $this->accountManager->getPrimaryDivisi()?->kode;
        }

        return null;
    }

    public function getWitelNama(): ?string
    {
        // If witel_id is null, fallback to account manager's witel
        if ($this->witel) {
            return $this->witel->nama;
        }

        if ($this->accountManager) {
            return $this->accountManager->getWitelNama();
        }

        return null;
    }

    public function getTeldaNama(): ?string
    {
        // Only for HOTDA
        if ($this->telda) {
            return $this->telda->nama;
        }

        if ($this->isHotda() && $this->accountManager) {
            return $this->accountManager->getTeldaNama();
        }

        return null;
    }

    // Validation methods
    public function validateProporsi(): bool
    {
        return $this->proporsi >= 0 && $this->proporsi <= 100;
    }

    public function validateTeldaConsistency(): bool
    {
        $amRole = $this->accountManager?->role;

        if ($amRole === 'HOTDA') {
            return !is_null($this->telda_id);
        } elseif ($amRole === 'AM') {
            return is_null($this->telda_id);
        }

        return true; // Allow if AM role is not set yet
    }

    // Static methods for aggregation
    public static function getTotalRevenueByAM($year, $month = null)
    {
        $query = static::with('accountManager')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('account_manager_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(*) as cc_count')
            ->groupBy('account_manager_id')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    public static function getTotalRevenueByDivisi($year, $month = null)
    {
        $query = static::with('divisi')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('divisi_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(DISTINCT account_manager_id) as am_count')
            ->whereNotNull('divisi_id')
            ->groupBy('divisi_id')
            ->get();
    }

    public static function getTotalRevenueByWitel($year, $month = null)
    {
        $query = static::with('witel')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('witel_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target, COUNT(DISTINCT account_manager_id) as am_count')
            ->whereNotNull('witel_id')
            ->groupBy('witel_id')
            ->get();
    }

    public static function getTop10AM($year, $month = null)
    {
        return static::getTotalRevenueByAM($year, $month)->take(10);
    }

    public static function validateProporsiTotal($corporateCustomerId, $year, $month)
    {
        $totalProporsi = static::where('corporate_customer_id', $corporateCustomerId)
            ->where('tahun', $year)
            ->where('bulan', $month)
            ->sum('proporsi');

        return abs($totalProporsi - 100) < 0.01; // Allow small floating point differences
    }

    // Boot method for model events
    protected static function boot()
    {
        parent::boot();

        // Auto-calculate achievement rate on saving
        static::saving(function ($amRevenue) {
            // Validate proporsi
            if (!$amRevenue->validateProporsi()) {
                throw new \InvalidArgumentException('Proporsi must be between 0 and 100');
            }

            // Validate telda consistency
            if (!$amRevenue->validateTeldaConsistency()) {
                throw new \InvalidArgumentException('Telda assignment inconsistent with Account Manager role');
            }

            // Validate period
            if ($amRevenue->bulan < 1 || $amRevenue->bulan > 12) {
                throw new \InvalidArgumentException('Bulan must be between 1 and 12');
            }

            // Auto-calculate achievement rate if not set
            if (is_null($amRevenue->achievement_rate)) {
                $amRevenue->achievement_rate = $amRevenue->calculated_achievement_rate;
            }
        });

        // Validate proporsi total after saving
        static::saved(function ($amRevenue) {
            if (!static::validateProporsiTotal($amRevenue->corporate_customer_id, $amRevenue->tahun, $amRevenue->bulan)) {
                Log::warning("Proporsi total for CC {$amRevenue->corporate_customer_id} in {$amRevenue->tahun}-{$amRevenue->bulan} is not 100%");
            }
        });
    }
}

