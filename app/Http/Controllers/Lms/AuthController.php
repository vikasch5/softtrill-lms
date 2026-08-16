<?php

namespace App\Http\Controllers\Lms;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('lms.dashboard');
        }
        return view('auth.login');
    }

    public function doLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Enforce license limits for non-admin users at login
            if (!$user->hasRole('Admin')) {
                /** @var \App\Services\License\EntitlementManager $entitlement */
                $entitlement = app(\App\Services\License\EntitlementManager::class);
                $max = $entitlement->maxUsers();

                if ($max > 0) {
                    // Get the IDs of the first $max allowed users (by creation date)
                    $allowedUserIds = User::withoutRole('Admin')
                        ->orderBy('id', 'asc')
                        ->limit($max)
                        ->pluck('id')
                        ->toArray();

                    if (!in_array($user->id, $allowedUserIds)) {
                        Auth::logout();
                        return response()->json([
                            'status' => false,
                            'message' => 'Your account is disabled due to a license downgrade. Please contact your administrator.'
                        ], 403);
                    }
                }
            }
           
            // Immediately mark user as online so the heartbeat picks it up
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', Auth::id())
                ->update(['last_activity_at' => now()]);

            // Notify license server that a user came online
            app(\App\Services\License\LicenseManager::class)->sendHeartbeat();

            return response()->json([
                'status' => true,
                'redirect' => route('lms.dashboard')
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    public function register()
    {
        return view('lms.auth.register');
    }

    public function doRegister(Request $request)
    {
        // Handle registration logic here
    }

    public function logout(Request $request)
    {
        // Touch last_activity_at one last time before logout to ensure it's tracked
        if (Auth::check()) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', Auth::id())
                ->update(['last_activity_at' => null]); // clear online status immediately
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Notify license server that a user went offline
        app(\App\Services\License\LicenseManager::class)->sendHeartbeat();

        return redirect()->route('login');
    }

    public function keepAlive(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $lastActivity = $user->last_activity_at ? \Carbon\Carbon::parse($user->last_activity_at) : null;
            $wasOffline = !$lastActivity || $lastActivity->diffInMinutes(now()) >= 2;

            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', $user->id)
                ->update(['last_activity_at' => now()]);
                
            if ($wasOffline || !\Illuminate\Support\Facades\Cache::has('license_heartbeat_sent')) {
                \Illuminate\Support\Facades\Cache::put('license_heartbeat_sent', true, 60);
                app(\App\Services\License\LicenseManager::class)->sendHeartbeat();
            }
        }
        return response()->json(['status' => 'ok']);
    }

    public function markOffline(Request $request)
    {
        if (Auth::check()) {
            \Illuminate\Support\Facades\DB::table('users')
                ->where('id', Auth::id())
                ->update(['last_activity_at' => null]);
                
            app(\App\Services\License\LicenseManager::class)->sendHeartbeat();
        }
        return response()->json(['status' => 'ok']);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
        ]);

        // Prevent resend within 60 seconds
        // if (session()->has('otp_last_sent')) {
        //     if (now()->diffInSeconds(session('otp_last_sent')) < 60) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Please wait before requesting another OTP.'
        //         ], 429);
        //     }
        // }

        $otp = random_int(100000, 999999);

        // Store securely in session
        session([
            'register_email' => $request->email,
            'register_otp' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(5),
            'otp_attempts' => 0,
            'otp_last_sent' => now(),
        ]);

        // Send Email
        Mail::raw("Your OTP is: $otp. It expires in 5 minutes.", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('Your Registration OTP');
        });

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully!'
        ]);
    }
}
