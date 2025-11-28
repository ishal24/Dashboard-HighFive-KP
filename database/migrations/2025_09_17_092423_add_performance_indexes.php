<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Additional performance indexes for better query performance

        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'users_role_index')) {
                    $table->index(['role'], 'users_role_index');
                }
                if (!$this->indexExists('users', 'users_role_witel_index')) {
                    $table->index(['role', 'witel_id'], 'users_role_witel_index');
                }
            });
        }

        // Account managers table indexes
        if (Schema::hasTable('account_managers')) {
            Schema::table('account_managers', function (Blueprint $table) {
                if (!$this->indexExists('account_managers', 'am_role_witel_index')) {
                    $table->index(['role', 'witel_id'], 'am_role_witel_index');
                }
                if (!$this->indexExists('account_managers', 'am_nik_nama_index')) {
                    $table->index(['nik', 'nama'], 'am_nik_nama_index');
                }
            });
        }

        // CC revenues performance indexes
        if (Schema::hasTable('cc_revenues')) {
            Schema::table('cc_revenues', function (Blueprint $table) {
                // Period-based queries
                if (!$this->indexExists('cc_revenues', 'cc_rev_period_divisi_index')) {
                    $table->index(['tahun', 'bulan', 'divisi_id'], 'cc_rev_period_divisi_index');
                }
                if (!$this->indexExists('cc_revenues', 'cc_rev_period_segment_index')) {
                    $table->index(['tahun', 'bulan', 'segment_id'], 'cc_rev_period_segment_index');
                }
                if (!$this->indexExists('cc_revenues', 'cc_rev_period_tipe_index')) {
                    $table->index(['tahun', 'bulan', 'tipe_revenue'], 'cc_rev_period_tipe_index');
                }

                // Search and filter indexes
                if (!$this->indexExists('cc_revenues', 'cc_rev_nama_nipnas_index')) {
                    $table->index(['nama_cc', 'nipnas'], 'cc_rev_nama_nipnas_index');
                }
                if (!$this->indexExists('cc_revenues', 'cc_rev_revenue_source_index')) {
                    $table->index(['revenue_source'], 'cc_rev_revenue_source_index');
                }
            });
        }

        // AM revenues performance indexes
        if (Schema::hasTable('am_revenues')) {
            Schema::table('am_revenues', function (Blueprint $table) {
                // Period-based queries
                if (!$this->indexExists('am_revenues', 'am_rev_period_am_index')) {
                    $table->index(['tahun', 'bulan', 'account_manager_id'], 'am_rev_period_am_index');
                }
                if (!$this->indexExists('am_revenues', 'am_rev_period_divisi_index')) {
                    $table->index(['tahun', 'bulan', 'divisi_id'], 'am_rev_period_divisi_index');
                }
                if (!$this->indexExists('am_revenues', 'am_rev_period_witel_index')) {
                    $table->index(['tahun', 'bulan', 'witel_id'], 'am_rev_period_witel_index');
                }

                // HOTDA specific queries
                if (!$this->indexExists('am_revenues', 'am_rev_telda_period_index')) {
                    $table->index(['telda_id', 'tahun', 'bulan'], 'am_rev_telda_period_index');
                }

                // Revenue analysis
                if (!$this->indexExists('am_revenues', 'am_rev_real_revenue_index')) {
                    $table->index(['real_revenue'], 'am_rev_real_revenue_index');
                }
            });
        }

        // Segments table indexes
        if (Schema::hasTable('segments')) {
            Schema::table('segments', function (Blueprint $table) {
                if (!$this->indexExists('segments', 'segments_divisi_kode_index')) {
                    $table->index(['divisi_id', 'ssegment_ho'], 'segments_divisi_kode_index');
                }
            });
        }

        // Teldas table indexes
        if (Schema::hasTable('teldas')) {
            Schema::table('teldas', function (Blueprint $table) {
                if (!$this->indexExists('teldas', 'teldas_witel_divisi_index')) {
                    $table->index(['witel_id', 'divisi_id'], 'teldas_witel_divisi_index');
                }
            });
        }

        // Account manager divisi pivot table indexes
        if (Schema::hasTable('account_manager_divisi')) {
            Schema::table('account_manager_divisi', function (Blueprint $table) {
                if (!$this->indexExists('account_manager_divisi', 'am_divisi_primary_index')) {
                    $table->index(['is_primary', 'divisi_id'], 'am_divisi_primary_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop all the custom indexes
        $indexesToDrop = [
            'users' => ['users_role_index', 'users_role_witel_index'],
            'account_managers' => ['am_role_witel_index', 'am_nik_nama_index'],
            'cc_revenues' => [
                'cc_rev_period_divisi_index',
                'cc_rev_period_segment_index',
                'cc_rev_period_tipe_index',
                'cc_rev_nama_nipnas_index',
                'cc_rev_revenue_source_index'
            ],
            'am_revenues' => [
                'am_rev_period_am_index',
                'am_rev_period_divisi_index',
                'am_rev_period_witel_index',
                'am_rev_telda_period_index',
                'am_rev_real_revenue_index'
            ],
            'segments' => ['segments_divisi_kode_index'],
            'teldas' => ['teldas_witel_divisi_index'],
            'account_manager_divisi' => ['am_divisi_primary_index']
        ];

        foreach ($indexesToDrop as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $indexName) {
                        try {
                            $table->dropIndex($indexName);
                        } catch (\Exception $e) {
                            // Index might not exist, continue
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($tableName, $indexName): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};