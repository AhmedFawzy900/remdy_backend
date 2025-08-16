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
        // Backfill remedy_remedy_type from remedies.remedy_type_id
        DB::table('remedies')
            ->whereNotNull('remedy_type_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                $inserts = [];
                foreach ($rows as $row) {
                    $inserts[] = [
                        'remedy_id' => $row->id,
                        'remedy_type_id' => $row->remedy_type_id,
                    ];
                }
                if (!empty($inserts)) {
                    DB::table('remedy_remedy_type')->upsert($inserts, ['remedy_id', 'remedy_type_id']);
                }
            });

        // Backfill body_system_remedy from remedies.body_system_id
        DB::table('remedies')
            ->whereNotNull('body_system_id')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                $inserts = [];
                foreach ($rows as $row) {
                    $inserts[] = [
                        'body_system_id' => $row->body_system_id,
                        'remedy_id' => $row->id,
                    ];
                }
                if (!empty($inserts)) {
                    DB::table('body_system_remedy')->upsert($inserts, ['body_system_id', 'remedy_id']);
                }
            });

        // Backfill disease_remedy from remedies.disease_id (if column exists)
        if (Schema::hasColumn('remedies', 'disease_id')) {
            DB::table('remedies')
                ->whereNotNull('disease_id')
                ->orderBy('id')
                ->chunkById(1000, function ($rows) {
                    $inserts = [];
                    foreach ($rows as $row) {
                        $inserts[] = [
                            'disease_id' => $row->disease_id,
                            'remedy_id' => $row->id,
                        ];
                    }
                    if (!empty($inserts)) {
                        DB::table('disease_remedy')->upsert($inserts, ['disease_id', 'remedy_id']);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op (backfill only)
    }
}; 