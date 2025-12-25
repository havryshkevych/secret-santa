<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('budget')->nullable()->after('description');
            $table->enum('result_format', ['private', 'group'])->default('private')->after('budget');
            $table->boolean('registration_open')->default(true)->after('result_format');
            $table->string('group_chat_id')->nullable()->after('organizer_chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['budget', 'result_format', 'registration_open', 'group_chat_id']);
        });
    }
};
