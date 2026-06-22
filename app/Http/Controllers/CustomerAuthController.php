<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('customer.auth.login');
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'Email not found. Use the email address registered by your admin.'])
                ->onlyInput('email');
        }

        if (! $user->hasRole(User::ROLE_CUSTOMER)) {
            return back()
                ->withErrors(['email' => 'This email is not registered as a customer account.'])
                ->onlyInput('email');
        }

        $otp = (string) random_int(100000, 999999);

        $request->session()->put('customer_otp', [
            'email' => $user->email,
            'code' => $otp,
            'expires_at' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->route('customer.otp.show');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('customer_otp')) {
            return redirect()->route('customer.login');
        }

        return view('customer.auth.verify', [
            'otp' => $request->session()->get('customer_otp.code'),
            'email' => $request->session()->get('customer_otp.email'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $otp = $request->session()->get('customer_otp');

        if (! $otp || now()->timestamp > $otp['expires_at']) {
            $request->session()->forget('customer_otp');

            return redirect()
                ->route('customer.login')
                ->withErrors(['email' => 'The OTP has expired. Request a new code.']);
        }

        if (! hash_equals($otp['code'], $validated['otp'])) {
            return back()->withErrors(['otp' => 'The OTP is wrong. Please check the code and try again.']);
        }

        $user = User::query()
            ->where('email', $otp['email'])
            ->first();

        if (! $user?->hasRole(User::ROLE_CUSTOMER)) {
            $request->session()->forget('customer_otp');

            return redirect()
                ->route('customer.login')
                ->withErrors(['email' => 'Enter a registered customer email address.']);
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        $request->session()->forget('customer_otp');

        return redirect()->route('customer.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('customer.login');
    }
}
