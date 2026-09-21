<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION forbid_table_mutation()
            RETURNS TRIGGER AS $$
            BEGIN
                RAISE EXCEPTION \'Table % is append-only. UPDATE and DELETE operations are forbidden.\', TG_TABLE_NAME;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS audit_logs_immutable_trigger ON audit_logs;
            CREATE TRIGGER audit_logs_immutable_trigger
            BEFORE UPDATE OR DELETE ON audit_logs
            FOR EACH ROW EXECUTE FUNCTION forbid_table_mutation();

            DROP TRIGGER IF EXISTS proposal_status_histories_immutable_trigger ON proposal_status_histories;
            CREATE TRIGGER proposal_status_histories_immutable_trigger
            BEFORE UPDATE OR DELETE ON proposal_status_histories
            FOR EACH ROW EXECUTE FUNCTION forbid_table_mutation();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS audit_logs_immutable_trigger ON audit_logs;
            DROP TRIGGER IF EXISTS proposal_status_histories_immutable_trigger ON proposal_status_histories;
            DROP FUNCTION IF EXISTS forbid_table_mutation();
        ');
    }
};

