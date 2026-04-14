<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $duplicates = DB::table('conversation_users')
            ->select('conversation_id', 'user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('conversation_id', 'user_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isNotEmpty()) {
            $duplicatedPairs = $duplicates->map(function ($dup) {
                return "({$dup->conversation_id}, {$dup->user_id})";
            })->implode(', ');

            throw new \RuntimeException(
                "Cannot add unique constraint. Duplicate (conversation_id, user_id) pairs exist: {$duplicatedPairs}. Please remove duplicates before running this migration."
            );
        }

        Schema::table('conversation_users', function (Blueprint $table) {
            $table->unique(['conversation_id', 'user_id'], 'conversation_users_unique');
        });
    }

    public function down()
    {
        Schema::table('conversation_users', function (Blueprint $table) {
            $table->dropUnique('conversation_users_unique');
        });
    }
};