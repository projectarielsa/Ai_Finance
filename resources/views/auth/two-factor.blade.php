@extends('layouts.auth')
@section('title', 'Verifikasi 2FA')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 rounded-2xl bg-primary-500/15 flex items-center justify-center text-3xl mx-auto mb-4">🔐</div>
    <h2 class="text-xl font-bold text-white mb-1">Verifikasi Dua Langkah</h2>
    <p class="text-dark-400 text-sm">Kode OTP 6 digit telah dikirim ke Telegram Anda</p>
</div>

@if($errors->any())
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
    {{ $errors->first() }}
</div>
@endif

@if(session('status'))
<div class="mb-4 p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
    {{ session('status') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
    {{ session('error') }}
</div>
@endif

<form method="POST" action="{{ route('two-factor.verify.submit') }}" class="space-y-5" x-data>
    @csrf

    {{-- OTP Input: 6 kotak terpisah --}}
    <div>
        <label class="input-label text-center block mb-3">Masukkan kode OTP</label>
        <div class="flex gap-2 justify-center" x-data="otpInput()">
            @for($i = 0; $i < 6; $i++)
            <input
                type="text"
                inputmode="numeric"
                maxlength="1"
                x-ref="input{{ $i }}"
                @input="handleInput($event, {{ $i }})"
                @keydown="handleKeydown($event, {{ $i }})"
                @paste.prevent="handlePaste($event)"
                class="w-11 h-14 text-center text-xl font-bold rounded-xl bg-dark-800 border border-dark-600/50 text-white focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none transition-colors"
            >
            @endfor
            {{-- Hidden input untuk submit --}}
            <input type="hidden" name="code" x-ref="codeInput">
        </div>
    </div>

    <button type="submit" class="btn-primary w-full justify-center py-3">
        Verifikasi
    </button>
</form>

<div class="mt-5 text-center space-y-3">
    <form method="POST" action="{{ route('two-factor.resend') }}">
        @csrf
        <button type="submit" class="text-sm text-primary-400 hover:text-primary-300">
            Tidak menerima kode? Kirim ulang
        </button>
    </form>

    <a href="{{ route('login') }}" class="block text-sm text-dark-400 hover:text-white">
        ← Kembali ke halaman login
    </a>
</div>

<div class="mt-5 p-3 rounded-xl bg-dark-700/30 border border-dark-600/30">
    <p class="text-xs text-dark-400 text-center">
        💡 Cek pesan dari bot <strong class="text-dark-300">@{{ config('services.telegram.bot_username', 'KeuanganArielsa_bot') }}</strong> di Telegram
    </p>
</div>

@push('scripts')
<script>
function otpInput() {
    return {
        handleInput(e, idx) {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val ? val[0] : '';
            this.updateHidden();
            if (val && idx < 5) {
                this.$refs['input' + (idx + 1)].focus();
            }
            // Auto-submit saat isi terakhir
            if (idx === 5 && val) {
                this.$nextTick(() => {
                    const code = this.getCode();
                    if (code.length === 6) {
                        e.target.closest('form').submit();
                    }
                });
            }
        },
        handleKeydown(e, idx) {
            if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                this.$refs['input' + (idx - 1)].focus();
            }
            if (e.key === 'ArrowLeft' && idx > 0) this.$refs['input' + (idx - 1)].focus();
            if (e.key === 'ArrowRight' && idx < 5) this.$refs['input' + (idx + 1)].focus();
        },
        handlePaste(e) {
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            if (!text) return;
            text.split('').forEach((ch, i) => {
                if (this.$refs['input' + i]) this.$refs['input' + i].value = ch;
            });
            const lastIdx = Math.min(text.length - 1, 5);
            this.$refs['input' + lastIdx]?.focus();
            this.updateHidden();
            if (text.length === 6) {
                this.$nextTick(() => document.querySelector('form').submit());
            }
        },
        getCode() {
            return [0,1,2,3,4,5].map(i => this.$refs['input' + i]?.value || '').join('');
        },
        updateHidden() {
            this.$refs.codeInput.value = this.getCode();
        },
    };
}
</script>
@endpush
@endsection
