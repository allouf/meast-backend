<?php

namespace Modules\Store\Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class IssueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('issue_types')->insert([
            [
                'name' => 'Payment Issue',
                'description' => 'Problems related to payment, billing, or refunds.',
                'priority' => 'high',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Order Delay',
                'description' => 'Customer reports that the order is delayed or late.',
                'priority' => 'medium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Technical Issue',
                'description' => 'Login issues, application bugs, and other technical problems.',
                'priority' => 'high',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'General Inquiry',
                'description' => 'General questions or inquiries from customers.',
                'priority' => 'low',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }
}
