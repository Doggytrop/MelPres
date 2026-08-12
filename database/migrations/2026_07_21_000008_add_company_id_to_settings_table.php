<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        if (DB::table('settings')->whereNull('company_id')->exists()) {
            $companyId = DB::table('companies')
                ->where('slug', 'melpres')
                ->value('id');

            $companyId ??= DB::table('companies')
                ->orderBy('id')
                ->value('id');

            if ($companyId === null) {
                $timestamp = now();
                $companyId = DB::table('companies')->insertGetId([
                    'name' => 'MelPres',
                    'slug' => 'melpres',
                    'status' => 'active',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            DB::table('settings')
                ->whereNull('company_id')
                ->update(['company_id' => $companyId]);
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_key_unique');
            $table->unique(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign('settings_company_id_foreign');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique('settings_company_id_key_unique');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('company_id');
            $table->unique('key');
        });
    }
};
