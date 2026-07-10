<x-customer.layout title="Verify OTP">
    <section class="panel auth-panel">
        <h1>Verify OTP</h1>
        <p class="muted">Enter the six-digit code for {{ $email }}.</p>
        @if ($otp)
            <div class="otp" aria-label="Demo verification code">{{ $otp }}</div>
        @endif

        @if ($errors->has('otp'))
            <div class="errors">
                {{ $errors->first('otp') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.otp.verify') }}">
            @csrf
            <div class="field">
                <label for="otp">Verification code</label>
                <input id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" required autofocus>
            </div>

            <button type="submit">Log in</button>
        </form>
    </section>
</x-customer.layout>
