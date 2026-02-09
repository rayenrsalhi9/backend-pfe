<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('response_audit_trails', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('forumId')->nullable(false);
            $table->uuid('responseId')->nullable(false);
            $table->string('responseType')->nullable(false); // 'comment' or 'reaction'
            $table->string('operationName'); // 'Created', 'Updated', 'Deleted'
            $table->longText('responseContent')->nullable();
            $table->longText('previousContent')->nullable();
            $table->string('ipAddress', 45)->nullable();
            $table->text('userAgent')->nullable();
            $table->uuid('createdBy')->nullable(false);
            $table->uuid('modifiedBy')->nullable();
            $table->boolean('isDeleted')->default(false);
            $table->dateTime('createdDate')->useCurrent();
            $table->dateTime('modifiedDate')->useCurrent();

            
            // Foreign key constraints
            $table->foreign('forumId')->references('id')->on('forums')->onDelete('cascade');
            $table->foreign('createdBy')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['forumId', 'createdDate']);
            $table->index(['responseId', 'responseType']);
            $table->index(['createdBy', 'createdDate']);
            $table->index(['operationName', 'createdDate']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('response_audit_trails');
    }
};
