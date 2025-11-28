<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migration: Create Stored Procedure and Trigger for Auto-Update AM Revenues
 *
 * Purpose: When cc_revenues updated, automatically recalculate related am_revenues
 *
 * Command to create this file:
 * php artisan make:migration create_sp_and_trigger_for_am_revenue_auto_update
 *
 * Command to run:
 * php artisan migrate
 *
 * Command to rollback:
 * php artisan migrate:rollback
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================================
        // STEP 1: Create Stored Procedure
        // ============================================================
        DB::unprepared("
            DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues;
        ");

        DB::unprepared("
            CREATE PROCEDURE sp_recalculate_am_revenues(
                IN p_cc_id BIGINT UNSIGNED,
                IN p_divisi_id BIGINT UNSIGNED,
                IN p_bulan TINYINT UNSIGNED,
                IN p_tahun SMALLINT UNSIGNED,
                IN p_new_target_revenue DECIMAL(25,2),
                IN p_new_real_revenue DECIMAL(25,2),
                OUT p_updated_count INT
            )
            BEGIN
                -- Deklarasi variabel
                DECLARE v_am_id BIGINT UNSIGNED;
                DECLARE v_am_revenue_id BIGINT UNSIGNED;
                DECLARE v_proporsi DECIMAL(5,2);
                DECLARE v_new_target_am DECIMAL(25,2);
                DECLARE v_new_real_am DECIMAL(25,2);
                DECLARE v_achievement_rate DECIMAL(8,2);
                DECLARE v_done INT DEFAULT 0;

                -- Cursor untuk loop semua AM yang terkait
                DECLARE cur_am_revenues CURSOR FOR
                    SELECT
                        id,
                        account_manager_id,
                        proporsi
                    FROM am_revenues
                    WHERE corporate_customer_id = p_cc_id
                      AND divisi_id = p_divisi_id
                      AND bulan = p_bulan
                      AND tahun = p_tahun;

                -- Handler untuk end of cursor
                DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

                -- Initialize counter
                SET p_updated_count = 0;

                -- Open cursor
                OPEN cur_am_revenues;

                -- Loop through all AM revenues
                read_loop: LOOP
                    -- Fetch next row
                    FETCH cur_am_revenues INTO v_am_revenue_id, v_am_id, v_proporsi;

                    -- Exit if no more rows
                    IF v_done THEN
                        LEAVE read_loop;
                    END IF;

                    -- Normalize proporsi (jika > 1, anggap dalam persen, convert ke decimal)
                    IF v_proporsi > 1 THEN
                        SET v_proporsi = v_proporsi / 100;
                    END IF;

                    -- Calculate proportional target revenue
                    SET v_new_target_am = p_new_target_revenue * v_proporsi;

                    -- Calculate proportional real revenue
                    SET v_new_real_am = p_new_real_revenue * v_proporsi;

                    -- Calculate achievement rate (avoid division by zero)
                    IF v_new_target_am > 0 THEN
                        SET v_achievement_rate = (v_new_real_am / v_new_target_am) * 100;
                    ELSE
                        SET v_achievement_rate = 0;
                    END IF;

                    -- Round achievement rate to 2 decimal places
                    SET v_achievement_rate = ROUND(v_achievement_rate, 2);

                    -- Update AM revenue
                    UPDATE am_revenues
                    SET
                        target_revenue = v_new_target_am,
                        real_revenue = v_new_real_am,
                        achievement_rate = v_achievement_rate,
                        updated_at = NOW()
                    WHERE id = v_am_revenue_id;

                    -- Increment counter if update successful
                    IF ROW_COUNT() > 0 THEN
                        SET p_updated_count = p_updated_count + 1;
                    END IF;

                END LOOP read_loop;

                -- Close cursor
                CLOSE cur_am_revenues;

            END
        ");

        // ============================================================
        // STEP 2: Create Trigger
        // ============================================================
        DB::unprepared("
            DROP TRIGGER IF EXISTS after_cc_revenues_update;
        ");

        DB::unprepared("
            CREATE TRIGGER after_cc_revenues_update
            AFTER UPDATE ON cc_revenues
            FOR EACH ROW
            BEGIN
                -- Deklarasi variabel
                DECLARE v_updated_count INT DEFAULT 0;
                DECLARE v_target_changed BOOLEAN DEFAULT FALSE;
                DECLARE v_real_changed BOOLEAN DEFAULT FALSE;

                -- Check if target_revenue changed
                IF OLD.target_revenue != NEW.target_revenue THEN
                    SET v_target_changed = TRUE;
                END IF;

                -- Check if real_revenue changed
                IF OLD.real_revenue != NEW.real_revenue THEN
                    SET v_real_changed = TRUE;
                END IF;

                -- Proceed only if at least one revenue field changed
                IF v_target_changed OR v_real_changed THEN

                    -- Call stored procedure to recalculate AM revenues
                    CALL sp_recalculate_am_revenues(
                        NEW.corporate_customer_id,
                        NEW.divisi_id,
                        NEW.bulan,
                        NEW.tahun,
                        NEW.target_revenue,
                        NEW.real_revenue,
                        v_updated_count
                    );

                END IF;

            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop trigger first (depends on stored procedure)
        DB::unprepared("DROP TRIGGER IF EXISTS after_cc_revenues_update");

        // Drop stored procedure
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_recalculate_am_revenues");
    }
};