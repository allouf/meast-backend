<?php

namespace Modules\Job\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Job\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * name: JSON with 'en' and 'ar' keys for English and Arabic translations.
     */
    public function run(): void
    {
        $languages = [
            ['name' => ['en' => 'English', 'ar' => 'الإنجليزية'], 'code' => 'en', 'sort_order' => 1],
            ['name' => ['en' => 'Arabic', 'ar' => 'العربية'], 'code' => 'ar', 'sort_order' => 2],
            ['name' => ['en' => 'French', 'ar' => 'الفرنسية'], 'code' => 'fr', 'sort_order' => 3],
            ['name' => ['en' => 'Spanish', 'ar' => 'الإسبانية'], 'code' => 'es', 'sort_order' => 4],
            ['name' => ['en' => 'German', 'ar' => 'الألمانية'], 'code' => 'de', 'sort_order' => 5],
            ['name' => ['en' => 'Italian', 'ar' => 'الإيطالية'], 'code' => 'it', 'sort_order' => 6],
            ['name' => ['en' => 'Portuguese', 'ar' => 'البرتغالية'], 'code' => 'pt', 'sort_order' => 7],
            ['name' => ['en' => 'Chinese', 'ar' => 'الصينية'], 'code' => 'zh', 'sort_order' => 8],
            ['name' => ['en' => 'Japanese', 'ar' => 'اليابانية'], 'code' => 'ja', 'sort_order' => 9],
            ['name' => ['en' => 'Korean', 'ar' => 'الكورية'], 'code' => 'ko', 'sort_order' => 10],
            ['name' => ['en' => 'Turkish', 'ar' => 'التركية'], 'code' => 'tr', 'sort_order' => 11],
            ['name' => ['en' => 'Russian', 'ar' => 'الروسية'], 'code' => 'ru', 'sort_order' => 12],
            ['name' => ['en' => 'Hindi', 'ar' => 'الهندية'], 'code' => 'hi', 'sort_order' => 13],
            ['name' => ['en' => 'Dutch', 'ar' => 'الهولندية'], 'code' => 'nl', 'sort_order' => 14],
            ['name' => ['en' => 'Polish', 'ar' => 'البولندية'], 'code' => 'pl', 'sort_order' => 15],
            ['name' => ['en' => 'Persian', 'ar' => 'الفارسية'], 'code' => 'fa', 'sort_order' => 16],
            ['name' => ['en' => 'Urdu', 'ar' => 'الأردية'], 'code' => 'ur', 'sort_order' => 17],
            ['name' => ['en' => 'Indonesian', 'ar' => 'الإندونيسية'], 'code' => 'id', 'sort_order' => 18],
            ['name' => ['en' => 'Malay', 'ar' => 'الملايوية'], 'code' => 'ms', 'sort_order' => 19],
            ['name' => ['en' => 'Thai', 'ar' => 'التايلاندية'], 'code' => 'th', 'sort_order' => 20],
            ['name' => ['en' => 'Vietnamese', 'ar' => 'الفيتنامية'], 'code' => 'vi', 'sort_order' => 21],
            ['name' => ['en' => 'Greek', 'ar' => 'اليونانية'], 'code' => 'el', 'sort_order' => 22],
            ['name' => ['en' => 'Hebrew', 'ar' => 'العبرية'], 'code' => 'he', 'sort_order' => 23],
            ['name' => ['en' => 'Swedish', 'ar' => 'السويدية'], 'code' => 'sv', 'sort_order' => 24],
            ['name' => ['en' => 'Norwegian', 'ar' => 'النرويجية'], 'code' => 'no', 'sort_order' => 25],
            ['name' => ['en' => 'Danish', 'ar' => 'الدنماركية'], 'code' => 'da', 'sort_order' => 26],
            ['name' => ['en' => 'Finnish', 'ar' => 'الفنلندية'], 'code' => 'fi', 'sort_order' => 27],
            ['name' => ['en' => 'Czech', 'ar' => 'التشيكية'], 'code' => 'cs', 'sort_order' => 28],
            ['name' => ['en' => 'Romanian', 'ar' => 'الرومانية'], 'code' => 'ro', 'sort_order' => 29],
            ['name' => ['en' => 'Hungarian', 'ar' => 'المجرية'], 'code' => 'hu', 'sort_order' => 30],
        ];

        foreach ($languages as $item) {
            Language::firstOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['is_active' => true])
            );
        }
    }
}
