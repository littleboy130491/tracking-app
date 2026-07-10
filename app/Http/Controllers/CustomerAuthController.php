<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

        if (! $user || ! $user->is_active || ! $user->hasRole(User::ROLE_CUSTOMER)) {
            return back()
                ->withErrors(['email' => 'We could not send a code to this address. Contact your administrator.'])
                ->onlyInput('email');
        }

        $otp = (string) random_int(100000, 999999);

        $request->session()->put('customer_otp', [
            'email' => $user->email,
            'hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('customer_auth.otp_expires_minutes', 10))->timestamp,
        ]);

        if (config('customer_auth.display_otp')) {
            $request->session()->put('customer_otp_demo_code', $otp);
        }

        if (config('customer_auth.send_email')) {
            Mail::raw(
                "Your BL Tracking verification code is {$otp}. It expires in ".config('customer_auth.otp_expires_minutes', 10).' minutes.',
                fn ($message) => $message->to($user->email)->subject('Your BL Tracking verification code'),
            );
        }

        return redirect()->route('customer.otp.show');
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('customer_otp')) {
            return redirect()->route('customer.login');
        }

        return view('customer.auth.verify', [
            'otp' => config('customer_auth.display_otp')
                ? $request->session()->get('customer_otp_demo_code')
                : null,
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
            $request->session()->forget(['customer_otp', 'customer_otp_demo_code']);

            return redirect()
                ->route('customer.login')
                ->withErrors(['email' => 'The OTP has expired. Request a new code.']);
        }

        if (! Hash::check($validated['otp'], $otp['hash'])) {
            $otp['attempts'] = ((int) ($otp['attempts'] ?? 0)) + 1;
            $request->session()->put('customer_otp', $otp);

            if ($otp['attempts'] >= (int) config('customer_auth.otp_max_attempts', 5)) {
                $request->session()->forget(['customer_otp', 'customer_otp_demo_code']);

                return redirect()
                    ->route('customer.login')
                    ->withErrors(['email' => 'Too many incorrect attempts. Request a new code.']);
            }

            return back()->withErrors(['otp' => 'The OTP is wrong. Please check the code and try again.']);
        }

        $user = User::query()
            ->where('email', $otp['email'])
            ->first();

        if (! $user?->is_active || ! $user->hasRole(User::ROLE_CUSTOMER)) {
            $request->session()->forget(['customer_otp', 'customer_otp_demo_code']);

            return redirect()
                ->route('customer.login')
                ->withErrors(['email' => 'Enter a registered customer email address.']);
        }

        Auth::login($user);
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();
        $request->session()->forget(['customer_otp', 'customer_otp_demo_code']);

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
