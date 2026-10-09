<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MigrateDataController extends Controller
{
    public function migrateCountries()
    {
        $oldCountries = DB::connection('mysql_old')->table('countries')->get();
        foreach ($oldCountries as $old) {
            $nameJson = json_encode([
                'en' => $old->en_name,
                'ar' => $old->ar_name,
            ], JSON_UNESCAPED_UNICODE);

            $isActive = $old->state === 'active' ? 1 : 0;

            $newData = [
                'id' => $old->id,
                'name' => $nameJson,
                'is_active' => $isActive,
                'flag' => $old->flag,
                'created_at' => $old->created_at,
                'updated_at' => $old->updated_at,
            ];

            DB::connection('mysql')->table('countries')->insert($newData);
        }

        return "Data migration between databases completed.";
    }
    public function migrateRegions()
    {
        DB::connection('mysql_old')->table('regions')->orderBy('id')->chunk(100, function ($oldRegions) {
            foreach ($oldRegions as $old) {
                $nameJson = json_encode([
                    'en' => $old->en_name,
                    'ar' => $old->ar_name,
                ], JSON_UNESCAPED_UNICODE);

                $newData = [
                    'id' => $old->id,
                    'country_id' => $old->country_id,
                    'name' => $nameJson,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ];

                DB::connection('mysql')->table('regions')->insert($newData);
            }
        });

        return "Regions migration completed in chunks.";
    }
    public function migrateCategories()
    {
        DB::connection('mysql_old')->table('categories')->orderBy('id')->chunk(100, function ($oldCategories) {
            foreach ($oldCategories as $old) {
                $nameJson = json_encode([
                    'en' => $old->en_name,
                    'ar' => $old->ar_name,
                ], JSON_UNESCAPED_UNICODE);

                $isActive = $old->state === 'active' ? 1 : 0;

                $newData = [
                    'id' => $old->id,
                    'name' => $nameJson,
                    'icon' => $old->icon,
                    'is_active' => $isActive,
                    'sort_order' => $old->sort_order,
                    '_lft' => $old->_lft,
                    '_rgt' => $old->_rgt,
                    'parent_id' => $old->parent_id,
                    'created_at' => $old->created_at,
                    'updated_at' => $old->updated_at,
                ];

                DB::connection('mysql')->table('categories')->insert($newData);
            }
        });

        return "Categories migration completed successfully.";
    }

    public function fixCountryFlags()
    {
        $countries = DB::table('countries')->get();

        foreach ($countries as $country) {
            $flag = $country->flag;

            if (!str_starts_with($flag, 'storage/')) {
                $newFlag = 'storage/' . ltrim($flag, '/');

                DB::table('countries')
                    ->where('id', $country->id)
                    ->update(['flag' => $newFlag]);
            }
        }

        return "Updated flag paths for countries.";
    }

    public function fixCategoryIcons()
    {
        $categories = DB::table('categories')
            ->orderBy('id')
            ->get();

        foreach ($categories as $category) {
            $icon = $category->icon;

            if ($icon !== null && !str_starts_with($icon, 'storage/')) {
                $newIcon = 'storage/' . ltrim($icon, '/');

                DB::table('categories')
                    ->where('id', $category->id)
                    ->update(['icon' => $newIcon]);
            }
        }

        return "تم تعديل المسارات لرموز الفئات (باستثناء null).";
    }
}
