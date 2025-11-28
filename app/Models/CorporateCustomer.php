<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorporateCustomer extends Model
{
    use HasFactory;

    protected $table = 'corporate_customers';

    protected $fillable = [
        'nama',
        'nipnas',
    ];

    // Append custom attributes
    protected $appends = ['primary_account_manager'];

    // ===== RELATIONSHIPS =====

    public function ccRevenues()
    {
        return $this->hasMany(CcRevenue::class);
    }

    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class);
    }

    /**
     * Get latest AM revenue record (for eager loading)
     */
    public function latestAmRevenue()
    {
        return $this->hasOne(AmRevenue::class)
                    ->with('accountManager')
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->orderBy('proporsi', 'desc')
                    ->latestOfMany(['tahun', 'bulan', 'proporsi']);
    }

    /**
     * Get all Account Managers handling this Corporate Customer
     * Many-to-Many relationship through am_revenues pivot table
     */
    public function accountManagers()
    {
        return $this->hasManyThrough(
            AccountManager::class,
            AmRevenue::class,
            'corporate_customer_id', // Foreign key on am_revenues table
            'id',                     // Foreign key on account_managers table
            'id',                     // Local key on corporate_customers table
            'account_manager_id'      // Local key on am_revenues table
        )->distinct();
    }

    // ===== ACCESSORS =====

    /**
     * Get PRIMARY Account Manager (highest proporsi) for latest period
     * This can be accessed as: $cc->primary_account_manager
     */
    public function getPrimaryAccountManagerAttribute()
    {
        // Use cached relationship if already loaded
        if ($this->relationLoaded('latestAmRevenue')) {
            return $this->latestAmRevenue?->accountManager;
        }

        // Otherwise query it
        $latestRevenue = $this->amRevenues()
                              ->with('accountManager')
                              ->orderBy('tahun', 'desc')
                              ->orderBy('bulan', 'desc')
                              ->orderBy('proporsi', 'desc')
                              ->first();

        return $latestRevenue?->accountManager;
    }

    // ===== SCOPES =====

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'like', '%' . $search . '%')
              ->orWhere('nipnas', 'like', '%' . $search . '%');
        });
    }

    public function scopeByNipnas($query, $nipnas)
    {
        return $query->where('nipnas', $nipnas);
    }

    /**
     * Filter by Account Manager (through am_revenues)
     */
    public function scopeByAccountManager($query, $accountManagerId)
    {
        return $query->whereHas('amRevenues', function ($q) use ($accountManagerId) {
            $q->where('account_manager_id', $accountManagerId);
        });
    }

    /**
     * Filter by Divisi (through cc_revenues)
     */
    public function scopeByDivisi($query, $divisiId)
    {
        return $query->whereHas('ccRevenues', function ($q) use ($divisiId) {
            $q->where('divisi_id', $divisiId);
        });
    }

    // ===== HELPER METHODS =====

    public function getDisplayNameAttribute(): string
    {
        return $this->nama . ' (' . $this->nipnas . ')';
    }

    /**
     * Get total revenue for specific period
     */
    public function getTotalRevenue($year, $month = null)
    {
        $query = $this->ccRevenues()->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->sum('real_revenue');
    }

    /**
     * Get revenue by divisi for specific period
     */
    public function getRevenueByDivisi($year, $month = null)
    {
        $query = $this->ccRevenues()
                      ->with('divisi')
                      ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->get()->groupBy('divisi.kode')->map(function ($revenues) {
            return [
                'divisi_nama' => $revenues->first()->divisi->nama,
                'divisi_kode' => $revenues->first()->divisi->kode,
                'total_revenue' => $revenues->sum('real_revenue'),
                'total_target' => $revenues->sum('target_revenue'),
                'achievement_rate' => $revenues->sum('target_revenue') > 0 ?
                    ($revenues->sum('real_revenue') / $revenues->sum('target_revenue')) * 100 : 0
            ];
        });
    }

    /**
     * Get account managers handling this CC in specific period
     * Returns collection of AccountManager models with pivot data (proporsi, revenue, etc)
     */
    public function getAccountManagers($year, $month)
    {
        return $this->amRevenues()
                    ->with('accountManager')
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->get()
                    ->map(function ($amRevenue) {
                        $am = $amRevenue->accountManager;
                        if ($am) {
                            // Add pivot data to AM object
                            $am->pivot_proporsi = $amRevenue->proporsi;
                            $am->pivot_real_revenue = $amRevenue->real_revenue;
                            $am->pivot_target_revenue = $amRevenue->target_revenue;
                            $am->pivot_achievement_rate = $amRevenue->achievement_rate;
                        }
                        return $am;
                    })
                    ->filter() // Remove nulls
                    ->unique('id'); // Remove duplicates
    }

    /**
     * Get latest revenue record
     */
    public function getLatestRevenue()
    {
        return $this->ccRevenues()
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->first();
    }

    /**
     * Get current divisi (from latest revenue)
     */
    public function getCurrentDivisi()
    {
        $latestRevenue = $this->getLatestRevenue();
        return $latestRevenue?->divisi;
    }

    /**
     * Get current segment (from latest revenue)
     */
    public function getCurrentSegment()
    {
        $latestRevenue = $this->getLatestRevenue();
        return $latestRevenue?->segment;
    }

    /**
     * Get current witel (from latest revenue)
     */
    public function getCurrentWitel()
    {
        $latestRevenue = $this->getLatestRevenue();
        return $latestRevenue?->witelHo ?? $latestRevenue?->witelBill;
    }

    /**
     * Get historical revenue trend
     */
    public function getRevenueTrend($months = 12)
    {
        return $this->ccRevenues()
                    ->selectRaw('tahun, bulan, SUM(real_revenue) as total_revenue')
                    ->groupBy('tahun', 'bulan')
                    ->orderBy('tahun', 'desc')
                    ->orderBy('bulan', 'desc')
                    ->limit($months)
                    ->get()
                    ->reverse()
                    ->values();
    }

    /**
     * Calculate average monthly revenue
     */
    public function getAverageMonthlyRevenue($year = null)
    {
        $query = $this->ccRevenues();

        if ($year) {
            $query->where('tahun', $year);
        }

        $revenues = $query->selectRaw('AVG(real_revenue) as avg_revenue')->first();
        return $revenues->avg_revenue ?? 0;
    }

    /**
     * Check if CC has Account Manager for specific period
     */
    public function hasAccountManager($year = null, $month = null)
    {
        $query = $this->amRevenues();

        if ($year) {
            $query->where('tahun', $year);
            if ($month) {
                $query->where('bulan', $month);
            }
        }

        return $query->exists();
    }

    /**
     * Get all Account Managers who have EVER handled this CC
     */
    public function getAllAccountManagers()
    {
        return AccountManager::whereHas('amRevenues', function ($query) {
            $query->where('corporate_customer_id', $this->id);
        })->get();
    }

    /**
     * Get Account Manager split (proporsi) for specific period
     */
    public function getAccountManagerSplit($year, $month)
    {
        return $this->amRevenues()
                    ->with('accountManager')
                    ->where('tahun', $year)
                    ->where('bulan', $month)
                    ->get()
                    ->map(function ($amRevenue) {
                        return [
                            'account_manager_id' => $amRevenue->account_manager_id,
                            'account_manager_nama' => $amRevenue->accountManager?->nama,
                            'account_manager_nik' => $amRevenue->accountManager?->nik,
                            'proporsi' => $amRevenue->proporsi,
                            'real_revenue' => $amRevenue->real_revenue,
                            'target_revenue' => $amRevenue->target_revenue,
                            'achievement_rate' => $amRevenue->achievement_rate,
                        ];
                    });
    }

    /**
     * Get Account Managers for specific period
     */
    public function accountManagersForPeriod($year, $month = null)
    {
        $query = $this->amRevenues()
                      ->with('accountManager')
                      ->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->get()
                     ->pluck('accountManager')
                     ->filter()
                     ->unique('id')
                     ->values();
    }
}