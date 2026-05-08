@extends('layouts.admin')
@section('title', 'Edit Gateway')
@section('page-title', 'Edit WhatsApp Gateway')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <form method="POST" action="{{ route('admin.whatsapp-gateways.update',$gateway) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="form-group">
                <label class="input-label">Nama Gateway *</label>
                <input type="text" name="name" value="{{ old('name',$gateway->name) }}" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">Base URL *</label>
                <input type="url" name="base_url" value="{{ old('base_url',$gateway->base_url) }}" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="input-label">API Key Baru <span class="text-dark-500">(kosongkan jika tidak berubah)</span></label>
                <input type="password" name="api_key" class="input-field" placeholder="••••••••">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Nomor Pengirim</label>
                    <input type="text" name="sender_number" value="{{ old('sender_number',$gateway->sender_number) }}" class="input-field">
                </div>
                <div class="form-group">
                    <label class="input-label">Device ID</label>
                    <input type="text" name="device_id" value="{{ old('device_id',$gateway->device_id) }}" class="input-field">
                </div>
            </div>
            <div class="form-group">
                <label class="input-label">Webhook Secret</label>
                <input type="text" name="webhook_secret" value="{{ old('webhook_secret',$gateway->webhook_secret) }}" class="input-field font-mono text-sm">
            </div>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_active" value="1" {{ $gateway->is_active?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Aktif
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_default" value="1" {{ $gateway->is_default?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Default
                </label>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan</button>
                <a href="{{ route('admin.whatsapp-gateways.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
