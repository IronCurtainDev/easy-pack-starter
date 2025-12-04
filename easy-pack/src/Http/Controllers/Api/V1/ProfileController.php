<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Models\User;
use EasyPack\Models\PersonalAccessToken;
use EasyPack\Models\NotificationPreference;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Get Profile')
                ->setDescription('Get the authenticated user\'s profile information')
                ->setSuccessObject(User::class);
        });

        $user = $request->user();
        $user->load('roles');

        return response()->apiSuccess($user);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Update Profile')
                ->setDescription('Update the authenticated user\'s profile information')
                ->setParams([
                    (new Param('name', ParamType::STRING, 'Full name of the user'))->optional(),
                    (new Param('email', ParamType::STRING, 'Email address'))->optional(),
                ])
                ->setSuccessObject(User::class);
        });

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user->update($request->only(['name', 'email']));

        return response()->apiSuccess($user, 'Profile updated successfully.');
    }

    /**
     * Update the authenticated user's password.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Update Password')
                ->setDescription('Update the authenticated user\'s password')
                ->setParams([
                    (new Param('current_password', ParamType::STRING, 'Current password'))->required(),
                    (new Param('password', ParamType::STRING, 'New password'))->required(),
                    (new Param('password_confirmation', ParamType::STRING, 'New password confirmation'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->apiError('Current password is incorrect.', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->apiSuccess(null, 'Password updated successfully.');
    }

    /**
     * Update the authenticated user's avatar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Update Avatar')
                ->setDescription('Update the authenticated user\'s avatar image')
                ->setConsumes(['multipart/form-data'])
                ->setParams([
                    (new Param('avatar', ParamType::FILE, 'Avatar image file (jpeg, png, gif, webp). Max 5MB.'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,gif,webp|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = $request->user();
        $user->replaceMediaFile($request->file('avatar'), 'avatar');

        return response()->apiSuccess([
            'avatar_url' => $user->avatar_url,
            'avatar_thumb_url' => $user->avatar_thumb_url,
        ], 'Avatar updated successfully.');
    }

    /**
     * Delete the authenticated user's avatar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Delete Avatar')
                ->setDescription('Delete the authenticated user\'s avatar image')
                ->setSuccessMessageOnly();
        });

        $user = $request->user();
        $user->clearMediaCollection('avatar');

        return response()->apiSuccess(null, 'Avatar deleted successfully.');
    }

    /**
     * Get the authenticated user's devices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function devices(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Get Devices')
                ->setDescription('Get a list of all devices logged into the user\'s account')
                ->setSuccessPaginatedObject(PersonalAccessToken::class);
        });

        $devices = $request->user()->getDevices()->map->toDeviceArray();

        return response()->apiSuccess($devices);
    }

    /**
     * Logout from a specific device.
     *
     * @param Request $request
     * @param string $deviceId
     * @return JsonResponse
     */
    public function logoutDevice(Request $request, string $deviceId): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Logout Device')
                ->setDescription('Logout from a specific device by device ID')
                ->setParams([
                    (new Param('deviceId', ParamType::STRING, 'The device ID to logout'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken->device_id === $deviceId) {
            return response()->apiError('Cannot logout from current device. Use /auth/logout instead.', 422);
        }

        $success = $request->user()->logoutDevice($deviceId);

        if (!$success) {
            return response()->apiNotFound('Device not found.');
        }

        return response()->apiSuccess(null, 'Device logged out successfully.');
    }

    /**
     * Get notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function notificationPreferences(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Get Notification Preferences')
                ->setDescription('Get the user\'s notification preferences')
                ->setSuccessObject(NotificationPreference::class);
        });

        $preferences = $request->user()->getNotificationPreferences();

        return response()->apiSuccess($preferences);
    }

    /**
     * Update notification preferences.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Update Notification Preferences')
                ->setDescription('Update the user\'s notification preferences')
                ->setParams([
                    (new Param('push_enabled', ParamType::BOOLEAN, 'Enable/disable push notifications'))->optional(),
                    (new Param('email_enabled', ParamType::BOOLEAN, 'Enable/disable email notifications'))->optional(),
                    (new Param('sms_enabled', ParamType::BOOLEAN, 'Enable/disable SMS notifications'))->optional(),
                    (new Param('quiet_hours_enabled', ParamType::BOOLEAN, 'Enable/disable quiet hours'))->optional(),
                    (new Param('quiet_hours_start', ParamType::STRING, 'Quiet hours start time (HH:MM)'))->optional(),
                    (new Param('quiet_hours_end', ParamType::STRING, 'Quiet hours end time (HH:MM)'))->optional(),
                    (new Param('categories', ParamType::ARRAY, 'Notification category preferences'))->optional(),
                ])
                ->setSuccessObject(NotificationPreference::class);
        });

        $validator = Validator::make($request->all(), [
            'push_enabled' => 'sometimes|boolean',
            'email_enabled' => 'sometimes|boolean',
            'sms_enabled' => 'sometimes|boolean',
            'quiet_hours_enabled' => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|date_format:H:i',
            'quiet_hours_end' => 'sometimes|date_format:H:i',
            'categories' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $preferences = $request->user()->getNotificationPreferences();
        $preferences->update($request->only([
            'push_enabled',
            'email_enabled',
            'sms_enabled',
            'quiet_hours_enabled',
            'quiet_hours_start',
            'quiet_hours_end',
            'categories',
        ]));

        return response()->apiSuccess($preferences, 'Notification preferences updated.');
    }

    /**
     * Delete account.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Profile')
                ->setName('Delete Account')
                ->setDescription('Permanently delete the authenticated user\'s account')
                ->setParams([
                    (new Param('password', ParamType::STRING, 'Current password to confirm deletion'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        if (!has_feature('account_deletion')) {
            return response()->apiError('Account deletion is disabled.', 403);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->apiError('Password is incorrect.', 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete user
        $user->delete();

        return response()->apiSuccess(null, 'Account deleted successfully.');
    }
}
