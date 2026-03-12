<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // General
            ['group' => 'general', 'key' => 'site_name',    'value' => null],
            ['group' => 'general', 'key' => 'site_logo',    'value' => null],
            ['group' => 'general', 'key' => 'currency',     'value' => null],
            ['group' => 'general', 'key' => 'timezone',     'value' => null],
            ['group' => 'general', 'key' => 'date_format',  'value' => null],

            // Shipping
            ['group' => 'shipping', 'key' => 'default_shipping_cost',     'value' => null],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold',   'value' => null],
            ['group' => 'shipping', 'key' => 'max_cod_amount',            'value' => null],

            // Notifications
            ['group' => 'notifications', 'key' => 'notify_on_new_order',      'value' => null],
            ['group' => 'notifications', 'key' => 'notify_on_status_change',  'value' => null],
            ['group' => 'notifications', 'key' => 'notify_on_new_shipper',    'value' => null],
        ];

        foreach ($defaults as $setting) {
            Setting::query()->firstOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                ]
            );
        }
    }
}
