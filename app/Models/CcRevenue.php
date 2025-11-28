<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcRevenue extends Model
{
    use HasFactory;

    protected $table = 'cc_revenues';

    protected $fillable = [
        'corporate_customer_id',
        'divisi_id',
        'segment_id',
        'witel_ho_id',
        'witel_bill_id',
        'nama_cc',
        'nipnas',
        'target_revenue',
        'real_revenue',
        'revenue_source',
        'tipe_revenue',
        'bulan',
        'tahun',
    ];

    protected $casts = [
        'target_revenue' => 'decimal:2',
        'real_revenue' => 'decimal:2',
        'bulan' => 'integer',
        'tahun' => 'integer',
    ];

    // Relationships
    public function corporateCustomer()
    {
        return $this->belongsTo(CorporateCustomer::class);
    }

    public function divisi()
    {
        return $this->belongsTo(Divisi::class);
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function witelHo()
    {
        return $this->belongsTo(Witel::class, 'witel_ho_id');
    }

    public function witelBill()
    {
        return $this->belongsTo(Witel::class, 'witel_bill_id');
    }

    // Derived AM revenues
    public function amRevenues()
    {
        return $this->hasMany(AmRevenue::class, 'corporate_customer_id', 'corporate_customer_id')
                    ->where('tahun', $this->tahun)
                    ->where('bulan', $this->bulan);
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

    public function scopeByDivisi($query, $divisiId)
    {
        return $query->where('divisi_id', $divisiId);
    }

    public function scopeBySegment($query, $segmentId)
    {
        return $query->where('segment_id', $segmentId);
    }

    public function scopeByWitelHo($query, $witelId)
    {
        return $query->where('witel_ho_id', $witelId);
    }

    public function scopeByWitelBill($query, $witelId)
    {
        return $query->where('witel_bill_id', $witelId);
    }

    public function scopeByRevenueSource($query, $source)
    {
        return $query->where('revenue_source', $source);
    }

    public function scopeByTipeRevenue($query, $tipe)
    {
        return $query->where('tipe_revenue', $tipe);
    }

    public function scopeReguler($query)
    {
        return $query->where('tipe_revenue', 'REGULER');
    }

    public function scopeNgtma($query)
    {
        return $query->where('tipe_revenue', 'NGTMA');
    }

    public function scopeHo($query)
    {
        return $query->where('revenue_source', 'HO');
    }

    public function scopeBill($query)
    {
        return $query->where('revenue_source', 'BILL');
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama_cc', 'like', '%' . $search . '%')
              ->orWhere('nipnas', 'like', '%' . $search . '%');
        });
    }

    // Accessors & Mutators
    public function getAchievementRateAttribute(): float
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
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $months[$this->bulan] . ' ' . $this->tahun;
    }

    // Helper methods
    public function isHo(): bool
    {
        return $this->revenue_source === 'HO';
    }

    public function isBill(): bool
    {
        return $this->revenue_source === 'BILL';
    }

    public function isReguler(): bool
    {
        return $this->tipe_revenue === 'REGULER';
    }

    public function isNgtma(): bool
    {
        return $this->tipe_revenue === 'NGTMA';
    }

    public function getDivisiKode(): ?string
    {
        return $this->divisi?->kode;
    }

    public function getSegmentKode(): ?string
    {
        return $this->segment?->ssegment_ho;
    }

    public function getWitelNama(): ?string
    {
        if ($this->isHo() && $this->witelHo) {
            return $this->witelHo->nama;
        } elseif ($this->isBill() && $this->witelBill) {
            return $this->witelBill->nama;
        }

        return null;
    }

    // Static methods for aggregation
    public static function getTotalRevenueByDivisi($year, $month = null)
    {
        $query = static::with('divisi')->where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        return $query->selectRaw('divisi_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target')
                    ->groupBy('divisi_id')
                    ->get();
    }

    public static function getTotalRevenueByWitel($year, $month = null, $source = null)
    {
        $query = static::where('tahun', $year);

        if ($month) {
            $query->where('bulan', $month);
        }

        if ($source === 'HO') {
            $query->whereNotNull('witel_ho_id')
                  ->with('witelHo')
                  ->selectRaw('witel_ho_id as witel_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target')
                  ->groupBy('witel_ho_id');
        } elseif ($source === 'BILL') {
            $query->whereNotNull('witel_bill_id')
                  ->with('witelBill')
                  ->selectRaw('witel_bill_id as witel_id, SUM(real_revenue) as total_revenue, SUM(target_revenue) as total_target')
                  ->groupBy('witel_bill_id');
        } else {
            // Combined query for both HO and BILL
            return [
                'ho' => static::getTotalRevenueByWitel($year, $month, 'HO'),
                'bill' => static::getTotalRevenueByWitel($year, $month, 'BILL')
            ];
        }

        return $query->get();
    }

    // Validation before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($ccRevenue) {
            // Validate revenue source consistency
            if ($ccRevenue->revenue_source === 'HO' && is_null($ccRevenue->witel_ho_id)) {
                throw new \InvalidArgumentException('HO revenue source requires witel_ho_id');
            }

            if ($ccRevenue->revenue_source === 'BILL' && is_null($ccRevenue->witel_bill_id)) {
                throw new \InvalidArgumentException('BILL revenue source requires witel_bill_id');
            }

            // Validate period
            if ($ccRevenue->bulan < 1 || $ccRevenue->bulan > 12) {
                throw new \InvalidArgumentException('Bulan must be between 1 and 12');
            }

            if ($ccRevenue->tahun < 2020 || $ccRevenue->tahun > 2030) {
                throw new \InvalidArgumentException('Tahun must be between 2020 and 2030');
            }
        });
    }
}