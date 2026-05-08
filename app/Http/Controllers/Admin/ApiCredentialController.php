<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Services\AppSettingService;
use App\Services\GrokAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiCredentialController extends Controller
{
    public function index()
    {
        $credentials = ApiCredential::withTrashed()->latest()->get();
        return view('admin.api-credentials.index', compact('credentials'));
    }

    public function create()
    {
        return view('admin.api-credentials.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'provider'     => 'required|string|max:50',
            'key_name'     => 'required|string|max:100',
            'key_value'    => 'required|string',
            'endpoint_url' => 'nullable|url',
            'model'        => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
            'is_default'   => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            ApiCredential::where('provider', $data['provider'])->update(['is_default' => false]);
        }

        ApiCredential::create([...$data,
            'is_active'  => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
            'updated_by' => Auth::id(),
        ]);

        // Clear settings cache
        app(AppSettingService::class)->clearCache();

        return redirect()->route('admin.api-credentials.index')->with('success', 'API Credential berhasil ditambahkan!');
    }

    public function edit(ApiCredential $apiCredential)
    {
        return view('admin.api-credentials.edit', ['credential' => $apiCredential]);
    }

    public function update(Request $request, ApiCredential $apiCredential)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'endpoint_url' => 'nullable|url',
            'model'        => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
            'is_default'   => 'nullable|boolean',
        ]);

        // Only update key_value if provided
        if ($request->filled('key_value')) {
            $request->validate(['key_value' => 'string|min:8']);
            $data['key_value'] = $request->key_value;
        }

        if ($request->boolean('is_default')) {
            ApiCredential::where('provider', $apiCredential->provider)->where('id', '!=', $apiCredential->id)->update(['is_default' => false]);
        }

        $apiCredential->update([...$data,
            'is_active'  => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
            'updated_by' => Auth::id(),
        ]);

        app(AppSettingService::class)->clearCache();

        return redirect()->route('admin.api-credentials.index')->with('success', 'API Credential diperbarui!');
    }

    public function destroy(ApiCredential $apiCredential)
    {
        $apiCredential->delete();
        app(AppSettingService::class)->clearCache();
        return redirect()->route('admin.api-credentials.index')->with('success', 'API Credential dihapus!');
    }

    public function testConnection(ApiCredential $apiCredential)
    {
        if ($apiCredential->provider === 'grok') {
            $service = app(GrokAIService::class);
            $result  = $service->testConnection();
        } else {
            $result = ['success' => false, 'message' => 'Test not implemented for this provider'];
        }

        $apiCredential->update([
            'last_tested_at'      => now(),
            'last_test_success'   => $result['success'],
            'last_test_message'   => $result['message'],
        ]);

        return response()->json($result);
    }
}
