<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\AppSettingService;
use Illuminate\Http\Request;

class AppSettingController extends Controller
{
    public function __construct(protected AppSettingService $settingService) {}

    public function index()
    {
        $settings = AppSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settingsData = $request->input('settings', []);

        foreach ($settingsData as $key => $value) {
            $setting = AppSetting::where('key', $key)->first();
            if ($setting) {
                $setting->update(['value' => $value]);
            }
        }

        $this->settingService->clearCache();

        return back()->with('success', 'Pengaturan berhasil disimpan!');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'key'         => 'required|string|unique:app_settings,key',
            'value'       => 'nullable|string',
            'type'        => 'required|in:string,boolean,integer,float,json',
            'group'       => 'required|string',
            'label'       => 'nullable|string',
            'description' => 'nullable|string',
            'is_public'   => 'nullable|boolean',
        ]);

        AppSetting::create([...$data, 'is_public' => $request->boolean('is_public', false)]);
        $this->settingService->clearCache();

        return back()->with('success', 'Setting berhasil ditambahkan!');
    }

    public function destroy(AppSetting $appSetting)
    {
        $appSetting->delete();
        $this->settingService->clearCache();
        return back()->with('success', 'Setting dihapus!');
    }
}
