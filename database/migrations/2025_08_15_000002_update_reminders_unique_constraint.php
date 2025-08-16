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
        // First, migrate existing data from 'day' to 'days' column only if 'day' column exists
        if (Schema::hasColumn('reminders', 'day')) {
            $reminders = DB::table('reminders')->whereNotNull('day')->get();
            
            foreach ($reminders as $reminder) {
                if ($reminder->day) {
                    // Convert single day to array format
                    DB::table('reminders')
                        ->where('id', $reminder->id)
                        ->update([
                            'days' => json_encode([$reminder->day])
                        ]);
                }
            }

            // // Finally, drop the old 'day' column
            // Schema::table('reminders', function (Blueprint $table) {
            //     $table->dropColumn('day');
            // });
        }
    }

    /**
     * Reverse the migrations.
     */
        public function down(): void
    {
        // Add back the old 'day' column only if it doesn't exist
        if (!Schema::hasColumn('reminders', 'day')) {
            Schema::table('reminders', function (Blueprint $table) {
                $table->string('day')->nullable()->after('element_id');
            });

            // Migrate data back from 'days' to 'day' column
            $reminders = DB::table('reminders')->whereNotNull('days')->get();
            
            foreach ($reminders as $reminder) {
                if ($reminder->days) {
                    $daysArray = json_decode($reminder->days, true);
                    if (is_array($daysArray) && !empty($daysArray)) {
                        // Take the first day from the array
                        DB::table('reminders')
                            ->where('id', $reminder->id)
                            ->update([
                                'day' => $daysArray[0]
                            ]);
                    }
                }
            }
        }
    }
}; 