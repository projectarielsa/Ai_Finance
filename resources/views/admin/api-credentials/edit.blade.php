@extends('layouts.admin')
@section('title', 'Edit API Credential')
@section('page-title', 'Edit API Credential')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        <form method="POST" action="{{ route('admin.api-credentials.update',$credential) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="form-group">
                <label class="input-label">Nama *</label>
                <input type="text" name="name" value="{{ old('name',$credential->name) }}" class="input-field" required>
            </div>

            <div class="glass-card p-4 bg-dark-700/30">
                <p class="text-dark-400 text-xs mb-0.5">Provider</p>
                <p class="text-white font-medium">{{ $credential->provider }}</p>
                <p class="text-dark-400 text-xs mt-2 mb-0.5">API Key Saat Ini (masked)</p>
                <code class="text-sm font-mono text-dark-300">{{ $credential->masked_key }}</code>
            </div>

            <div class="form-group">
                <label class="input-label">API Key Baru <span class="text-dark-500">(kosongkan jika tidak ingin mengubah)</span></label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show?'text':'password'" name="key_value" class="input-field pr-11" placeholder="Isi untuk mengganti API key">
                    <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-400 hover:text-white">👁</button>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label">Endpoint URL</label>
                <input type="url" name="endpoint_url" value="{{ old('endpoint_url',$credential->endpoint_url) }}" class="input-field">
            </div>

            <div class="form-group">
                <label class="input-label">Model</label>
                <input type="text" name="model" value="{{ old('model',$credential->model) }}" class="input-field">
            </div>

            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_active" value="1" {{ $credential->is_active?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Aktif
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_default" value="1" {{ $credential->is_default?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Default
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
                <a href="{{ route('admin.api-credentials.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
