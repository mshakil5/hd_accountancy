<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientCredential;
use App\Models\ClientPasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PassportAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $client = ClientCredential::where('email', $request->email)->first();

        if (!$client || !Hash::check($request->password, $client->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'error'   => 'Unauthenticated',
            ], 401);
        }

        if (!$client->status) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact support.',
                'error'   => 'Unauthorized',
            ], 403);
        }

        $token = $client->createToken('ClientApp')->accessToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'         => $client->id,
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $client->email,
                'phone'      => $client->phone,
            ],
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Logged out successfully.',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $client = ClientCredential::where('email', $request->email)->first();

        if (!$client) {
            return response()->json([
                'message' => 'If this email is registered, a reset code has been sent.',
            ], 200);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        ClientPasswordResetToken::where('email', $request->email)->delete();

        ClientPasswordResetToken::create([
            'email'      => $request->email,
            'token'      => Hash::make($otp),
            'created_at' => now(),
        ]);

        Mail::send('emails.client_otp', ['otp' => $otp, 'name' => $client->first_name], function ($mail) use ($request) {
            $mail->to($request->email)
                ->subject('Your Password Reset Code — HD Accountancy');
        });

        return response()->json([
            'message' => 'A 6-digit reset code has been sent to your email.',
        ], 200);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|digits:6',
        ]);

        $record = ClientPasswordResetToken::where('email', $request->email)
            ->latest('created_at')
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            $record->delete();
            return response()->json(['message' => 'Reset code has expired. Please request a new one.'], 422);
        }

        if (!Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Invalid code. Please try again.'], 422);
        }

        $resetToken = Str::random(64);
        $record->update(['token' => Hash::make($resetToken)]);

        return response()->json([
            'message'      => 'Code verified successfully.',
            'reset_token'  => $resetToken,
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'reset_token'           => 'required|string',
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        $record = ClientPasswordResetToken::where('email', $request->email)
            ->latest('created_at')
            ->first();

        if (!$record || !Hash::check($request->reset_token, $record->token)) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 422);
        }

        if (Carbon::parse($record->created_at)->addMinutes(30)->isPast()) {
            $record->delete();
            return response()->json(['message' => 'Reset session expired. Please start again.'], 422);
        }

        $client = ClientCredential::where('email', $request->email)->firstOrFail();
        $client->update(['password' => Hash::make($request->password)]);

        $record->delete();

        return response()->json([
            'message' => 'Password reset successfully. Please log in.',
        ], 200);
    }

    public function me(Request $request)
    {
        $client = $request->user();

        return response()->json([
            'user' => [
                'id'         => $client->id,
                'first_name' => $client->first_name,
                'last_name'  => $client->last_name,
                'email'      => $client->email,
                'phone'      => $client->phone,
            ],
        ], 200);
    }
}