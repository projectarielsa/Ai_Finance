@extends('layouts.admin')
@section('title', 'App Settings')
@section('page-title', 'App Settings')

@section('content')
<div class="space-y-6 animate-fade-in">
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach($settings as $group => $groupSettings)
        <div class="glass-card p-6 mb-4">
            <h3 class="text-white font-semibold capitalize mb-4 flex items-center gap-2">
                @php $icons=['general'=>'⚙️','finance'=>'💰','ai'=>'🤖','whatsapp'=>'📱']; @endphp
                {{ $icons[$group] ?? '📌' }} {{ ucfirst($group) }}
            </h3>
            <div class="space-y-4">
                @foreach($groupSettings as $setting)
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <label for="s_{{ $setting->key }}" class="text-sm font-medium text-dark-200">{{ $setting->label ?? $setting->key }}</label>
                        @if($setting->description)<p class="text-xs text-dark-500 mt-0.5">{{ $setting->description }}</p>@endif
                    </div>
                    <div class="w-64">
                        @if($setting->type === 'boolean')
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="hidden" name="settings[{{ $setting->key }}]" value="0">
                            <input type="checkbox" name="settings[{{ $setting->key }}]" id="s_{{ $setting->key }}" value="1" {{ $setting->value?'checked':'' }} class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-primary-500">
                            <span class="text-sm text-dark-300">{{ $setting->value ? 'Aktif' : 'Nonaktif' }}</span>
                        </label>
                        @else
                        <input type="{{ in_array($setting->type,['integer','float'])?'number':'text' }}"
                               name="settings[{{ $setting->key }}]"
                               id="s_{{ $setting->key }}"
                               value="{{ $setting->value }}"
                               class="input-field text-sm py-2">
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

        @if($settings->isEmpty())
        <div class="glass-card p-10 text-center"><p class="text-dark-400">Tidak ada settings. Jalankan seeder terlebih dahulu.</p></div>
        @else
        <button type="submit" class="btn-primary">Simpan Semua Pengaturan</button>
        @endif
    </form>
</div>
@endsection
