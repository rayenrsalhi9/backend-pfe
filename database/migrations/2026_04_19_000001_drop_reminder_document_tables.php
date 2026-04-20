<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE reminders DROP FOREIGN KEY reminders_documentid_foreign');
        DB::statement('ALTER TABLE reminderSchedulers DROP FOREIGN KEY reminderschedulers_documentid_foreign');
        DB::statement('ALTER TABLE reminders DROP COLUMN documentId');
        DB::statement('ALTER TABLE reminderSchedulers DROP COLUMN documentId');
    }

    public function down()
    {
        DB::statement('ALTER TABLE reminders ADD COLUMN documentId VARCHAR(36) NULL');
        DB::statement('ALTER TABLE reminderSchedulers ADD COLUMN documentId VARCHAR(36) NULL');
        DB::statement('ALTER TABLE reminders ADD CONSTRAINT reminders_documentid_foreign FOREIGN KEY (documentId) REFERENCES documents(id)');
        DB::statement('ALTER TABLE reminderSchedulers ADD CONSTRAINT reminderschedulers_documentid_foreign FOREIGN KEY (documentId) REFERENCES documents(id)');
    }
};