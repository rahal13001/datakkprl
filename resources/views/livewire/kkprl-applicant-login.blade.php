<main class="mx-auto min-h-screen w-full max-w-xl bg-white px-4 py-8 text-slate-900 sm:px-6">
    <h1 class="text-2xl font-bold">{{ $registering ? 'Daftar akun pemohon' : 'Masuk akun pemohon' }}</h1>
    <p class="mt-3 text-sm leading-6 text-slate-600">Akun baru dapat membuka dokumen miliknya. Builder tersedia setelah approval petugas.</p>
    <form wire:submit="submit" class="mt-6 space-y-5">
        <div>
            <label for="applicant-email" class="block text-sm font-medium">Email</label>
            <input id="applicant-email" type="email" wire:model="email" autocomplete="username" required maxlength="255" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2">
            @error('email') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="applicant-password" class="block text-sm font-medium">Password</label>
            <input id="applicant-password" type="password" wire:model="password" autocomplete="{{ $registering ? 'new-password' : 'current-password' }}" required maxlength="255" @if ($registering) minlength="12" @endif class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2">
            @if ($registering) <p class="mt-1 text-xs text-slate-600">Minimal 12 karakter.</p> @endif
            @error('password') <p role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        @if ($registering)
            <div>
                <label for="applicant-password-confirmation" class="block text-sm font-medium">Konfirmasi password</label>
                <input id="applicant-password-confirmation" type="password" wire:model="password_confirmation" autocomplete="new-password" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2">
            </div>
        @endif
        <button type="submit" wire:loading.attr="disabled" class="w-full rounded-md bg-amber-700 px-4 py-3 font-semibold text-white disabled:opacity-60">{{ $registering ? 'Daftar' : 'Masuk' }}</button>
    </form>
    <p class="mt-5 text-sm"><a href="{{ route($registering ? 'kkprl.applicant.login' : 'kkprl.applicant.register') }}" class="font-semibold text-amber-800 underline">{{ $registering ? 'Sudah punya akun? Masuk' : 'Belum punya akun? Daftar' }}</a></p>
    @unless ($registering)
        <p class="mt-5 text-sm text-slate-600">Lupa password? Hubungi petugas layanan untuk reset password.</p>
    @endunless
</main>
