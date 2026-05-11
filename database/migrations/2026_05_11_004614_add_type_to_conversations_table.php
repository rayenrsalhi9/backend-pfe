<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->string('type', 10)->default('private')->after('title');
        });

        DB::table('conversations')
            ->whereIn('id', function ($query) {
                $query->select('conversation_id')
                    ->from('conversation_users')
                    ->groupBy('conversation_id')
                    ->havingRaw('COUNT(*) > 2');
            })
            ->orWhereNotNull('title')
            ->update(['type' => 'group']);
    }

    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
