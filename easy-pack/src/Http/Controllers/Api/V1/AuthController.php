<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\User;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Register')
                ->setDescription('Register a new user account')
                ->setParams([
                    (new Param('name', ParamType::STRING, 'Full name of the user'))->required(),
                    (new Param('email', ParamType::STRING, 'Email address of user'))->required(),
                    (new Param('password', ParamType::STRING, 'Password. Must be at least 8 characters.'))->required(),
                    (new Param('password_confirmation', ParamType::STRING, 'Password confirmation'))->required(),
                    (new Param('device_id', ParamType::STRING, 'Unique ID of the device'))->required(),
                    (new Param('device_type', ParamType::STRING, 'Type of the device: `apple`, `android`, or `web`'))->required(),
                    (new Param('device_push_token', ParamType::STRING, 'Unique push token for the device'))->optional(),
                ])
                ->setSuccessObject(User::class);
        });

        if (!has_feature('auth.public_users_can_register')) {
            return response()->apiError('Registration is disabled.', 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'device_id' => 'required|string|max:255',
            'device_type' => 'required|string|in:apple,android,web',
            'device_push_token' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createDeviceToken(
            $request->device_type . '-device',
            $request->device_id,
            $request->device_type,
            $request->device_push_token,
            $request->ip()
        );

        $user->setExtraApiField('access_token', $token->plainTextToken);

        return response()->apiSuccess($user, 'User registered successfully.', 201);
    }

    /**
     * Login user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Login')
                ->setDescription('Login with email and password')
                ->setParams([
                    (new Param('email', ParamType::STRING, 'Email address'))->required(),
                    (new Param('password', ParamType::STRING, 'Password'))->required(),
                    (new Param('device_id', ParamType::STRING, 'Unique ID of the device'))->required(),
                    (new Param('device_type', ParamType::STRING, 'Type of the device: `apple`, `android`, or `web`'))->required(),
                    (new Param('device_push_token', ParamType::STRING, 'Unique push token for the device'))->optional(),
                ])
                ->setSuccessObject(User::class);
        });

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_id' => 'required|string|max:255',
            'device_type' => 'required|string|in:apple,android,web',
            'device_push_token' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->apiUnauthorized('Invalid credentials.');
        }

        // Check if user is disabled
        if ($user->is_disabled ?? false) {
            return response()->apiError('Your account has been disabled.', 403);
        }

        $token = $user->createDeviceToken(
            $request->device_type . '-device',
            $request->device_id,
            $request->device_type,
            $request->device_push_token,
            $request->ip()
        );

        $user->setExtraApiField('access_token', $token->plainTextToken);

        return response()->apiSuccess($user, 'Login successful.');
    }

    /**
     * Logout from current device.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Logout')
                ->setDescription('Logout the current user from the current device')
                ->setSuccessMessageOnly();
        });

        $request->user()->currentAccessToken()->delete();

        return response()->apiSuccess(null, 'Logged out successfully.');
    }

    /**
     * Logout from all devices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutAll(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Logout All Devices')
                ->setDescription('Logout from all devices by revoking all access tokens')
                ->setSuccessMessageOnly();
        });

        $count = $request->user()->logoutAllDevices();

        return response()->apiSuccess(['devices_logged_out' => $count], 'Logged out from all devices.');
    }

    /**
     * Get current authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Get Current User')
                ->setDescription('Get the currently authenticated user information')
                ->setSuccessObject(User::class);
        });

        $user = $request->user();
        $user->load('roles', 'permissions');

        return response()->apiSuccess($user);
    }

    /**
     * Update push token for current device.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Update Push Token')
                ->setDescription('Update the push notification token for the current device')
                ->setParams([
                    (new Param('device_push_token', ParamType::STRING, 'Unique push token for the device'))->optional(),
                ])
                ->setSuccessMessageOnly();
        });

        $validator = Validator::make($request->all(), [
            'device_push_token' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $token = $request->user()->currentAccessToken();
        $token->update(['device_push_token' => $request->device_push_token]);

        return response()->apiSuccess(null, 'Push token updated successfully.');
    }

    /**
     * Refresh current token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Refresh Token')
                ->setDescription('Refresh the current access token. Generates a new token and extends expiry.')
                ->setSuccessMessageOnly();
        });

        $currentToken = $request->user()->currentAccessToken();
        $newToken = $currentToken->refreshToken();

        return response()->apiSuccess([
            'access_token' => $newToken,
        ], 'Token refreshed successfully.');
    }

    /**
     * Verify email with OTP code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Verify Email')
                ->setDescription('Verify user email address using the OTP code sent via email')
                ->setParams([
                    (new Param('code', ParamType::STRING, 'The verification code sent to the user\'s email'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = $request->user();

        // Check if user is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->apiError('Email already verified.', 400);
        }

        // Check verification code
        if ($user->email_verification_code !== $request->code) {
            return response()->apiError('Invalid verification code.', 422);
        }

        // Check if code has expired (15 minutes)
        if ($user->email_verification_code_sent_at && 
            $user->email_verification_code_sent_at->addMinutes(15)->isPast()) {
            return response()->apiError('Verification code has expired. Please request a new one.', 422);
        }

        // Mark email as verified
        $user->markEmailAsVerified();
        $user->update([
            'email_verification_code' => null,
            'email_verification_code_sent_at' => null,
        ]);

        return response()->apiSuccess(null, 'Email verified successfully.');
    }

    /**
     * Resend verification code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resendCode(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Auth')
                ->setName('Resend Verification Code')
                ->setDescription('Resend the email verification code to the authenticated user\'s email address')
                ->setSuccessMessageOnly();
        });

        $user = $request->user();

        // Check if user is already verified
        if ($user->hasVerifiedEmail()) {
            return response()->apiError('Email already verified.', 400);
        }

        // Rate limiting - prevent resending too frequently
        if ($user->email_verification_code_sent_at && 
            $user->email_verification_code_sent_at->addMinutes(1)->isFuture()) {
            return response()->apiError('Please wait before requesting another code.', 429);
        }

        // Generate new code
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'email_verification_code' => $code,
            'email_verification_code_sent_at' => now(),
        ]);

        // Send verification email
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->apiSuccess(null, 'Verification code sent successfully.');
    }
}
