<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stitching_sizes')) {
            return;
        }

        if (Schema::hasColumn('stitching_sizes', 'sections')) {
            return;
        }

        Schema::table('stitching_sizes', function (Blueprint $table) {
            $table->json('sections')->nullable()->after('standard_size');
        });

        $rows = DB::table('stitching_sizes')->get();
        foreach ($rows as $row) {
            $sections = [];
            if ($row->kameez_measurements) {
                $sections[] = [
                    'name' => 'Kameez',
                    'measurements' => json_decode($row->kameez_measurements, true) ?? [],
                ];
            }
            if ($row->shalwar_measurements) {
                $sections[] = [
                    'name' => 'Shalwar',
                    'measurements' => json_decode($row->shalwar_measurements, true) ?? [],
                ];
            }
            DB::table('stitching_sizes')->where('id', $row->id)->update([
                'sections' => json_encode($sections ?: [['name' => 'Measurements', 'measurements' => []]]),
            ]);
        }

        Schema::table('stitching_sizes', function (Blueprint $table) {
            $table->json('sections')->nullable(false)->change();
            $table->dropColumn(['kameez_measurements', 'shalwar_measurements']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stitching_sizes') || ! Schema::hasColumn('stitching_sizes', 'sections')) {
            return;
        }

        Schema::table('stitching_sizes', function (Blueprint $table) {
            $table->json('kameez_measurements')->nullable();
            $table->json('shalwar_measurements')->nullable();
        });

        Schema::table('stitching_sizes', function (Blueprint $table) {
            $table->dropColumn('sections');
        });
    }
};
