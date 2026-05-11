<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name',                    'value' => 'Finance AI Assistant',                   'type' => 'string',  'group' => 'general', 'label' => 'App Name',                           'is_public' => true],
            ['key' => 'app_description',             'value' => 'Smart personal finance powered by AI',   'type' => 'string',  'group' => 'general', 'label' => 'App Description',                    'is_public' => true],
            ['key' => 'minimum_balance_default',     'value' => '100000',                                  'type' => 'integer', 'group' => 'finance', 'label' => 'Default Minimum Balance Warning',     'is_public' => false],
            ['key' => 'ai_confidence_threshold',     'value' => '70',                                      'type' => 'integer', 'group' => 'ai',      'label' => 'AI Confidence Threshold (%)',         'is_public' => false],
            ['key' => 'ai_pending_if_low_confidence','value' => '1',                                       'type' => 'boolean', 'group' => 'ai',      'label' => 'Set Pending if AI Confidence Low',   'is_public' => false],
            ['key' => 'receipt_scan_enabled',        'value' => '1',                                       'type' => 'boolean', 'group' => 'ai',      'label' => 'Enable Receipt Scanner',             'is_public' => false],
            ['key' => 'voice_note_enabled',          'value' => '1',                                       'type' => 'boolean', 'group' => 'ai',      'label' => 'Enable Voice Note Transcription',    'is_public' => false],
        ];

        foreach ($settings as $s) {
            AppSetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
