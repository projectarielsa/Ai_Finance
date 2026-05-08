@extends('layouts.admin')
@section('title', 'Tambah WhatsApp Gateway')
@section('page-title', 'Tambah WhatsApp Gateway')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <form method="POST" action="{{ route('admin.whatsapp-gateways.store') }}" class="space-y-4">
            @csrf

            <div class="form-group">
                <label class="input-label">Nama Gateway *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="input-field" placeholder="Contoh: Fonnte Production" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Provider *</label>
                    <select name="provider" class="input-field" required>
                        <option value="fonnte">Fonnte</option>
                        <option value="wablas">Wablas</option>
                        <option value="whacenter">Whacenter</option>
                        <option value="custom">Custom API</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Nomor Pengirim</label>
                    <input type="text" name="sender_number" value="{{ old('sender_number') }}" class="input-field" placeholder="628xxx">
                </div>
            </div>

            <div class="form-group">
                <label class="input-label">Base URL *</label>
                <input type="url" name="base_url" value="{{ old('base_url') }}" class="input-field" placeholder="https://api.fonnte.com" required>
            </div>

            <div class="form-group">
                <label class="input-label">API Key *</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show?'text':'password'" name="api_key" class="input-field pr-11" required>
                    <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-400">👁</button>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label">Device ID / Session ID</label>
                <input type="text" name="device_id" value="{{ old('device_id') }}" class="input-field" placeholder="ID device WhatsApp">
            </div>

            <div class="form-group">
                <label class="input-label">Webhook Secret</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="webhook_secret" value="{{ old('webhook_secret', \Illuminate\Support\Str::random(32)) }}" class="input-field font-mono text-sm">
                </div>
                <p class="text-xs text-dark-500 mt-1">Gunakan string acak ini sebagai header X-Webhook-Secret di gateway Anda</p>
            </div>

            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Aktif
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Set sebagai default
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Gateway</button>
                <a href="{{ route('admin.whatsapp-gateways.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
