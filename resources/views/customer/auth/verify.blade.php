<x-customer.layout title="Verify OTP">
    <section class="panel auth-panel">
        <h1>Verify OTP</h1>
        <p class="muted">Demo OTP for {{ $email }}:</p>
        <div class="otp">{{ $otp }}</div>

        @if ($errors->has('otp'))
            <div class="errors">
                {{ $errors->first('otp') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.otp.verify') }}">
            @csrf
            <div class="field">
                <label for="otp">OTP</label>
                <input id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" required autofocus>
            </div>

            <button type="submit">Log in</button>
        </form>
    </section>
</x-customer.layout>
