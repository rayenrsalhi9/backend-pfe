<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Change the column type to string (using raw SQL for compatibility)
        DB::statement('ALTER TABLE reminders MODIFY frequency VARCHAR(255) NULL');

        // 2. Migrate existing numeric data to string values
        $mapping = [
            '0' => 'daily',
            '1' => 'weekly',
            '2' => 'monthly',
            '3' => 'quarterly',
            '4' => 'half_yearly',
            '5' => 'yearly',
            '6' => 'once',
        ];

        foreach ($mapping as $int => $string) {
            DB::table('reminders')
                ->where('frequency', $int)
                ->update(['frequency' => $string]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $mapping = [
            'daily' => '0',
            'weekly' => '1',
            'monthly' => '2',
            'quarterly' => '3',
            'half_yearly' => '4',
            'yearly' => '5',
            'once' => '6',
        ];

        foreach ($mapping as $string => $int) {
            DB::table('reminders')
                ->where('frequency', $string)
                ->update(['frequency' => $int]);
        }

        DB::statement('ALTER TABLE reminders MODIFY frequency INT NULL');
    }
};
