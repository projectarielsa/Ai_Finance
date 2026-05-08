<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappGateway;
use App\Services\AppSettingService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WhatsappGatewayController extends Controller
{
    public function index()
    {
        $gateways = WhatsappGateway::withTrashed()->latest()->get();
        return view('admin.whatsapp-gateways.index', compact('gateways'));
    }

    public function create()
    {
        return view('admin.whatsapp-gateways.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'provider'       => 'required|string|max:50',
            'base_url'       => 'required|url',
            'api_key'        => 'required|string',
            'device_id'      => 'nullable|string|max:100',
            'sender_number'  => 'nullable|string|max:20',
            'webhook_secret' => 'nullable|string|max:200',
            'is_active'      => 'nullable|boolean',
            'is_default'     => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            WhatsappGateway::query()->update(['is_default' => false]);
        }

        $gateway = WhatsappGateway::create([...$data,
            'is_active'      => $request->boolean('is_active', true),
            'is_default'     => $request->boolean('is_default', false),
            'webhook_url'    => route('webhook.whatsapp'),
            'created_by'     => Auth::id(),
        ]);

        app(AppSettingService::class)->clearCache();

        return redirect()->route('admin.whatsapp-gateways.index')->with('success', 'WhatsApp Gateway berhasil ditambahkan!');
    }

    public function edit(WhatsappGateway $whatsappGateway)
    {
        return view('admin.whatsapp-gateways.edit', ['gateway' => $whatsappGateway]);
    }

    public function update(Request $request, WhatsappGateway $whatsappGateway)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'base_url'       => 'required|url',
            'device_id'      => 'nullable|string|max:100',
            'sender_number'  => 'nullable|string|max:20',
            'webhook_secret' => 'nullable|string|max:200',
            'is_active'      => 'nullable|boolean',
            'is_default'     => 'nullable|boolean',
        ]);

        if ($request->filled('api_key')) {
            $data['api_key'] = $request->api_key;
        }

        if ($request->boolean('is_default')) {
            WhatsappGateway::where('id', '!=', $whatsappGateway->id)->update(['is_default' => false]);
        }

        $whatsappGateway->update([...$data,
            'is_active'  => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
        ]);

        app(AppSettingService::class)->clearCache();

        return redirect()->route('admin.whatsapp-gateways.index')->with('success', 'Gateway diperbarui!');
    }

    public function destroy(WhatsappGateway $whatsappGateway)
    {
        $whatsappGateway->delete();
        app(AppSettingService::class)->clearCache();
        return redirect()->route('admin.whatsapp-gateways.index')->with('success', 'Gateway dihapus!');
    }

    public function testConnection(WhatsappGateway $whatsappGateway)
    {
        $service = app(WhatsAppService::class);
        $result  = $service->testConnection($whatsappGateway);
        return response()->json($result);
    }

    public function sendTestMessage(Request $request, WhatsappGateway $whatsappGateway)
    {
        $request->validate(['phone' => 'required|string', 'message' => 'required|string']);
        $service = app(WhatsAppService::class);
        $success = $service->sendTestMessage($request->phone, $request->message, $whatsappGateway);
        return response()->json(['success' => $success, 'message' => $success ? 'Pesan terkirim!' : 'Gagal mengirim pesan.']);
    }
}
