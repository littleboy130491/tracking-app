<x-customer.layout title="Customer Login">
    <section class="panel auth-panel">
        <h1>Customer Login</h1>
        <p class="muted">Enter your registered email to receive a demo OTP.</p>

        @if ($errors->has('email'))
            <div class="errors">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('customer.otp.request') }}">
            @csrf
            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>

            <button type="submit">Request OTP</button>
        </form>
    </section>
</x-customer.layout>
