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
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
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
