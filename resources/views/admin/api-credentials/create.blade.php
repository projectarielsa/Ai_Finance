@extends('layouts.admin')
@section('title', 'Tambah API Credential')
@section('page-title', 'Tambah API Credential')

@section('content')
<div class="max-w-xl mx-auto animate-fade-in">
    <div class="glass-card p-6">
        @if($errors->any())
        <div class="mb-5 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.api-credentials.store') }}" class="space-y-4">
            @csrf

            <div class="form-group">
                <label class="input-label">Nama *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="input-field" placeholder="Contoh: Grok AI Production" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label class="input-label">Provider *</label>
                    <select name="provider" class="input-field" required>
                        <option value="grok">Grok AI (xAI)</option>
                        <option value="openai">OpenAI (Whisper)</option>
                        <option value="google">Google Vision</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="input-label">Key Name *</label>
                    <input type="text" name="key_name" value="{{ old('key_name','api_key') }}" class="input-field" required>
                </div>
            </div>

            <div class="form-group">
                <label class="input-label">API Key *</label>
                <div class="relative" x-data="{ show: false }">
                    <input :type="show?'text':'password'" name="key_value" class="input-field pr-11" placeholder="xai-xxxxxxxxxxxxxxxxxxxx" required>
                    <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-dark-400 hover:text-white">
                        <svg x-show="!show" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/></svg>
                        <svg x-show="show" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                    </button>
                </div>
                <p class="text-xs text-dark-500 mt-1">Key akan dienkripsi sebelum disimpan ke database</p>
            </div>

            <div class="form-group">
                <label class="input-label">Endpoint URL</label>
                <input type="url" name="endpoint_url" value="{{ old('endpoint_url','https://api.x.ai/v1') }}" class="input-field" placeholder="https://api.x.ai/v1">
            </div>

            <div class="form-group">
                <label class="input-label">Model</label>
                <input type="text" name="model" value="{{ old('model','grok-2-vision-1212') }}" class="input-field" placeholder="grok-2-vision-1212">
            </div>

            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Aktif
                </label>
                <label class="flex items-center gap-2 cursor-pointer text-sm text-dark-200">
                    <input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                    Default untuk provider ini
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary">Simpan Credential</button>
                <a href="{{ route('admin.api-credentials.index') }}" class="btn-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
