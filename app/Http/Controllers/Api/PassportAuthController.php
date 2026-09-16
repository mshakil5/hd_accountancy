<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClientCredential;
use App\Models\ClientPasswordResetToken;
use App\Models\User;
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

        if ($client && Hash::check($request->password, $client->password)) {
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
                'role'    => 'client',
                'user'    => [
                    'id'         => $client->id,
                    'first_name' => $client->first_name,
                    'last_name'  => $client->last_name,
                    'email'      => $client->email,
                    'phone'      => $client->phone,
                ],
            ], 200);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if (!$user->status) {
                return response()->json([
                    'message' => 'Your account is inactive. Please contact support.',
                    'error'   => 'Unauthorized',
                ], 403);
            }

            $typeMap = [0 => 'user', 1 => 'admin', 2 => 'manager', 3 => 'staff'];
            $role = $typeMap[$user->getRawOriginal('type')] ?? 'user';

            $token = $user->createToken('ClientApp')->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'token'   => $token,
                'role'    => $role,
                'user'    => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                    'type'       => $user->getRawOriginal('type'),
                ],
            ], 200);
        }

        return response()->json([
            'message' => 'Invalid credentials.',
            'error'   => 'Unauthenticated',
        ], 401);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user instanceof User) {
            $user->tokens()->delete();
        } else {
            try {
                $request->user()->token()->revoke();
            } catch (\Exception $e) {
                $user->tokens()->delete();
            }
        }

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
        $user = User::where('email', $request->email)->first();

        if (!$client && !$user) {
            return response()->json([
                'message' => 'If this email is registered, a reset code has been sent.',
            ], 200);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $email = $client ? $client->email : ($user ? $user->email : '');
        $name = $client ? $client->first_name : ($user ? $user->first_name : '');

        if ($client) {
            ClientPasswordResetToken::where('email', $request->email)->delete();
            ClientPasswordResetToken::create([
                'email'      => $request->email,
                'token'      => Hash::make($otp),
                'created_at' => now(),
            ]);
        }

        Mail::send('emails.client_otp', ['otp' => $otp, 'name' => $name], function ($mail) use ($request) {
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
        $user = $request->user();

        if ($user instanceof User) {
            $typeMap = [0 => 'user', 1 => 'admin', 2 => 'manager', 3 => 'staff'];
            return response()->json([
                'user' => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                    'type'       => $user->getRawOriginal('type'),
                    'role'       => $typeMap[$user->getRawOriginal('type')] ?? 'user',
                ],
            ], 200);
        }

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
            ],
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'     => 'required',
            'new_password'         => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        $user = $request->user();

        if ($user instanceof User) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
            $user->update(['password' => Hash::make($request->new_password)]);
        } else {
            $client = $user;
            if (!Hash::check($request->current_password, $client->password)) {
                return response()->json(['message' => 'Current password is incorrect.'], 422);
            }
            $client->update(['password' => Hash::make($request->new_password)]);
        }

        return response()->json([
            'message' => 'Password changed successfully.',
        ], 200);
    }
}