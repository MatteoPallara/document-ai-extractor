<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use ESolution\DBEncryption\Encrypter;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $credentials = $this->getEncryptedCredentials($request);
        $authenticated = $this->attemptLogin($credentials);

        if ($authenticated) {
            $url = $this->getRedirectUrl();

            $this->updateLastLogin(Auth::user());

            return redirect($url);
        }

        return redirect('login')->withErrors('Email o password errati.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    private function getEncryptedCredentials(Request $request): array
    {
        $credentials = $request->only('email', 'password');
        $credentials['email'] = Encrypter::encrypt($credentials['email']);
        return $credentials;
    }

    private function attemptLogin(array $credentials): bool
    {
        return Auth::attempt($credentials);
    }

    private function getRedirectUrl(): string
    {
        if (Auth::user()->hasRole('admin')) {
            return '/admin/stats';
        }
        return '/user/home';
    }

    private function updateLastLogin(User $user): void
    {
        $user->last_login = Carbon::now();
        $user->save();
    }
}