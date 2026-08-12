<x-customer.layout title="Login Pelanggan">
    <section class="panel auth-panel">
        <h1>Login Pelanggan</h1>
        <p class="muted">Masukkan email pelanggan yang terdaftar.</p>

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

            <button type="submit">Lanjutkan</button>
        </form>
    </section>
</x-customer.layout>
