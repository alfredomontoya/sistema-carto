<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // communications table
        Schema::table('communications', function (Blueprint $table) {
            $table->index('recipient_user_id', 'comms_recipient_user_idx');
            $table->index(['status', 'area_id', 'created_at'], 'comms_status_area_created_idx');
            $table->index(['status', 'user_id', 'created_at'], 'comms_status_user_created_idx');
            $table->index(['area_id', 'year', 'type', 'sequence'], 'comms_area_year_type_seq_idx');
        });

        // areas table
        Schema::table('areas', function (Blueprint $table) {
            $table->index('parent_id', 'areas_parent_id_idx');
            $table->index('numbering_area_id', 'areas_numbering_area_id_idx');
            $table->index('is_active', 'areas_is_active_idx');
            $table->index('name', 'areas_name_idx');
        });

        // users table
        Schema::table('users', function (Blueprint $table) {
            $table->index('is_active', 'users_is_active_idx');
        });

        // positions table
        Schema::table('positions', function (Blueprint $table) {
            $table->index('code', 'positions_code_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * On MySQL an index backing a foreign key cannot be dropped directly
     * (error 1553), so the FK is dropped first and re-created afterwards to
     * restore the pre-migration state. Other drivers drop the index plainly.
     */
    public function down(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        Schema::table('communications', function (Blueprint $table) use ($isMysql) {
            if ($isMysql) {
                $table->dropForeign('communications_recipient_user_id_foreign');
            }
            $table->dropIndex('comms_recipient_user_idx');
            $table->dropIndex('comms_status_area_created_idx');
            $table->dropIndex('comms_status_user_created_idx');
            $table->dropIndex('comms_area_year_type_seq_idx');
            if ($isMysql) {
                $table->foreign('recipient_user_id', 'communications_recipient_user_id_foreign')
                    ->references('id')->on('users')->nullOnDelete();
            }
        });

        Schema::table('areas', function (Blueprint $table) use ($isMysql) {
            if ($isMysql) {
                $table->dropForeign('areas_parent_id_foreign');
                $table->dropForeign('areas_numbering_area_id_foreign');
            }
            $table->dropIndex('areas_parent_id_idx');
            $table->dropIndex('areas_numbering_area_id_idx');
            $table->dropIndex('areas_is_active_idx');
            $table->dropIndex('areas_name_idx');
            if ($isMysql) {
                $table->foreign('parent_id', 'areas_parent_id_foreign')
                    ->references('id')->on('areas')->nullOnDelete();
                $table->foreign('numbering_area_id', 'areas_numbering_area_id_foreign')
                    ->references('id')->on('areas')->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_active_idx');
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex('positions_code_idx');
        });
    }
};
