<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ensure consumer, company, freelancer roles use guard_name = 'api'
     * so API users get correct role in login and role middleware works.
     * Also reassign model_has_roles so users linked to same-named web roles
     * are linked to the api guard role instead.
     */
    public function up(): void
    {
        $rolesTable = config('permission.table_names.roles', 'roles');
        $pivotTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $modelKey = config('permission.column_names.model_morph_key', 'model_id');
        $rolePivotKey = config('permission.column_names.role_pivot_key', 'role_id');

        $apiRoleNames = ['consumer', 'company', 'freelancer'];

        foreach ($apiRoleNames as $roleName) {
            $apiRole = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', 'api')
                ->first();

            $nonApiRoles = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', '!=', 'api')
                ->get();

            if ($apiRole && $nonApiRoles->isNotEmpty()) {
                foreach ($nonApiRoles as $oldRole) {
                    DB::table($pivotTable)
                        ->where($rolePivotKey, $oldRole->id)
                        ->update([$rolePivotKey => $apiRole->id]);
                }
            }

            // Only update guard to api when no api role exists (avoid duplicate name+guard_name)
            if (!$apiRole) {
                DB::table($rolesTable)
                    ->where('name', $roleName)
                    ->where('guard_name', '!=', 'api')
                    ->update(['guard_name' => 'api']);
            }
        }

        $store = config('permission.cache.store');
        if ($store && $store !== 'default') {
            app('cache')->store($store)->forget(config('permission.cache.key'));
        } else {
            app('cache')->forget(config('permission.cache.key'));
        }
    }

    public function down(): void
    {
        // No safe way to revert without knowing previous guard_name
    }
};
