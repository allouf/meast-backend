<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'App\Models\Post' => 'Modules\Community\Models\Post',
            'App\Models\Reel' => 'Modules\Community\Models\Reel',
            'App\Models\Story' => 'Modules\Community\Models\Story',
        ];

        foreach ($map as $old => $new) {
            DB::table('comments')->where('commentable_type', $old)->update(['commentable_type' => $new]);
            DB::table('reactions')->where('reactionable_type', $old)->update(['reactionable_type' => $new]);
        }
    }

    public function down(): void
    {
        $map = [
            'App\Models\Post' => 'Modules\Community\Models\Post',
            'App\Models\Reel' => 'Modules\Community\Models\Reel',
            'App\Models\Story' => 'Modules\Community\Models\Story',
        ];

        foreach ($map as $old => $new) {
            DB::table('comments')->where('commentable_type', $new)->update(['commentable_type' => $old]);
            DB::table('reactions')->where('reactionable_type', $new)->update(['reactionable_type' => $old]);
        }
    }
};
