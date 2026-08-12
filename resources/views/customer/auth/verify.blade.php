<x-customer.layout title="Verifikasi OTP">
    <section class="panel auth-panel">
        <h1>Verifikasi OTP</h1>
        @if ($emailSent && ! $otp)
            <p class="muted">Kami telah mengirim kode 6 digit ke {{ $email }}. Masukkan kode tersebut di bawah ini untuk melanjutkan.</p>
        @elseif ($otp)
            <p class="muted">Masukkan kode 6 digit untuk {{ $email }}.</p>
            <div class="otp" aria-label="Kode verifikasi demo">{{ $otp }}</div>
        @else
            <p class="muted">Masukkan kode 6 digit untuk {{ $email }}.</p>
        @endif

        @if ($errors->has('otp'))
            <div class="errors">
                {{ $errors->first('otp') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.otp.verify') }}">
            @csrf
            <div class="field">
                <label for="otp">Kode verifikasi</label>
                <input id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
            </div>

            <button type="submit">Masuk</button>
        </form>
    </section>
</x-customer.layout>
