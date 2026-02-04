<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class OidcController extends Controller
{
    // 1️⃣ Redirect to the IdP (SSO)
    public function redirect()
    {
//         $user =User::first();
//         Auth::login($user, true);
//         $intendedUrl = session('url.intended', '/dashboard');
// //        session()->forget('url.intended');

//         return redirect($intendedUrl);

        $query = http_build_query([
            'client_id' => config('services.oidc.client_id'),
            'redirect_uri' => config('services.oidc.redirect'),
            'response_type' => 'code',
            'state' => csrf_token(),
            'scope' => 'openid profile email bnumber affiliations',
        ]);

        return redirect(config('services.oidc.authorize_url') . '?' . $query);
    }

    // 2️⃣ Handle callback after login
    public function callback(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return redirect('/settings/servers')->withErrors(['msg' => 'Authorization code missing']);
        }

        $tokenResponse = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->withBasicAuth(
                config('services.oidc.client_id'),
                config('services.oidc.client_secret')
            )
            ->post(config('services.oidc.token_url'), [
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.oidc.redirect'),
                'code' => $code,
            ]);


        if ($tokenResponse->failed()) {
            return redirect('/welcome')->withErrors(['msg' => 'Token exchange failed', 'details' => $tokenResponse->body()]);
        }

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return redirect('/welcome')->withErrors(['msg' => 'Access token missing']);
        }

        $userResponse = Http::withToken($accessToken)->get(config('services.oidc.userinfo_url'));

        if ($userResponse->failed()) {
            return redirect('/welcome')->withErrors(['msg' => 'Failed to fetch user info', 'details' => $userResponse->body()]);
        }

        $oidcUser = $userResponse->json();

        $uniqueId = $oidcUser['udc_identifier'] ?? $oidcUser['sub'] ?? null;
        $email = $oidcUser['email'] ?? null;
        $name = $oidcUser['display_name'] ?? 'User';

        if (!$uniqueId) {
            return redirect('/welcome')->withErrors(['msg' => 'Missing unique identifier from IdP']);
        }

        $user = \App\Models\User::updateOrCreate(
            ['unique_id' => $uniqueId],
            ['name' => $name,
            'email' => $email ?? "{$uniqueId}@example.com"]
        );

        Auth::login($user, true);

        $intendedUrl = session('url.intended', '/settings/servers');
        session()->forget('url.intended');

        return redirect($intendedUrl);
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/welcome');
    }


}
