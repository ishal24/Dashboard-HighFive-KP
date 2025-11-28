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
        // Clean up invalid data first
        $this->cleanInvalidData();

        // Add foreign key constraints to users table
        Schema::table('users', function (Blueprint $table) {
            // Add witel_id foreign key
            if (Schema::hasTable('witel') && !$this->foreignKeyExists('users', 'witel_id')) {
                $table->foreign('witel_id')
                    ->references('id')
                    ->on('witel')
                    ->onDelete('set null');
            }

            // Add account_manager_id foreign key
            if (Schema::hasTable('account_managers') && !$this->foreignKeyExists('users', 'account_manager_id')) {
                $table->foreign('account_manager_id')
                    ->references('id')
                    ->on('account_managers')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop foreign keys safely
            try {
                if ($this->foreignKeyExists('users', 'witel_id')) {
                    $table->dropForeign(['witel_id']);
                }
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            try {
                if ($this->foreignKeyExists('users', 'account_manager_id')) {
                    $table->dropForeign(['account_manager_id']);
                }
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }
        });
    }

    /**
     * Clean invalid data before adding foreign keys
     */
    private function cleanInvalidData(): void
    {
        // Clean invalid witel_id references
        if (Schema::hasTable('users') && Schema::hasTable('witel')) {
            DB::statement("
                UPDATE users
                SET witel_id = NULL
                WHERE witel_id IS NOT NULL
                AND witel_id NOT IN (SELECT id FROM witel)
            ");
        }

        // Clean invalid account_manager_id references
        if (Schema::hasTable('users') && Schema::hasTable('account_managers')) {
            DB::statement("
                UPDATE users
                SET account_manager_id = NULL
                WHERE account_manager_id IS NOT NULL
                AND account_manager_id NOT IN (SELECT id FROM account_managers)
            ");
        }
    }

    /**
     * Check if foreign key exists
     */
    private function foreignKeyExists($table, $column): bool
    {
        try {
            $foreignKeys = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableForeignKeys($table);

            foreach ($foreignKeys as $foreignKey) {
                if (in_array($column, $foreignKey->getLocalColumns())) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            // Fallback to raw query if Doctrine fails
            try {
                $constraintExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
                    WHERE tc.table_name = ? AND kcu.column_name = ? AND tc.constraint_type = 'FOREIGN KEY'",
                    [$table, $column]
                );

                return $constraintExists[0]->count > 0;
            } catch (\Exception $e2) {
                return false;
            }
        }
    }
};