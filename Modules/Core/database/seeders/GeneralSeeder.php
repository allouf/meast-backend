<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\General;

class GeneralSeeder extends Seeder
{
    public function run(): void
    {
        // إعدادات صفحات ثابتة
        $staticKeys = [
            'policy_en', 'policy_ar',
            'terms_en', 'terms_ar',
            'about_en', 'about_ar',
        ];

        foreach ($staticKeys as $key) {
            General::updateOrCreate(
                ['key' => $key],
                ['value' => null, 'group' => 'static']
            );
        }

        // إعدادات التواصل
        $contactKeys = [
            'phone', 'email',
            'facebook', 'whatsapp', 'instagram', 'telegram',
            'linkedin', 'twitter', 'youtube', 'tiktok', 'snapchat',
        ];

        foreach ($contactKeys as $key) {
            General::updateOrCreate(
                ['key' => $key],
                ['value' => null, 'group' => 'contact']
            );
        }

        // إعدادات نظام الإحالة (أو إعدادات تشغيلية أخرى)
        $systemKeys = [
            'referral_enabled' => '1',
            'redeem_enabled' => '1',
            'referral_daily_limit' => null,
            'referral_monthly_limit' => null,
        ];

        foreach ($systemKeys as $key => $value) {
            General::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'system']
            );
        }
    }
}
