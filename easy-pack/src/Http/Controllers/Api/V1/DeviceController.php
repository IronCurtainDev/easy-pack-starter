<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * List all devices/tokens for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('List Devices')
                ->setDescription('Get a list of all active devices/sessions for the authenticated user')
                ->setSuccessMessageOnly();
        });

        $devices = $request->user()->getDevices();
        $deviceList = $devices->map(fn ($device) => $device->toDeviceArray());

        return response()->apiSuccess([
            'devices' => $deviceList,
            'total' => $deviceList->count(),
        ]);
    }

    /**
     * Get details of a specific device.
     *
     * @param Request $request
     * @param string $deviceId
     * @return JsonResponse
     */
    public function show(Request $request, string $deviceId): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('Get Device')
                ->setDescription('Get details of a specific device by device ID')
                ->setParams([
                    (new Param('deviceId', ParamType::STRING, 'The device ID'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $device = $request->user()->getDevice($deviceId);

        if (!$device) {
            return response()->apiNotFound('Device not found.');
        }

        return response()->apiSuccess($device->toDeviceArray());
    }

    /**
     * Logout from a specific device (revoke its token).
     *
     * @param Request $request
     * @param string $deviceId
     * @return JsonResponse
     */
    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('Logout Device')
                ->setDescription('Logout from a specific device by revoking its token. Cannot logout from the current device using this endpoint.')
                ->setParams([
                    (new Param('deviceId', ParamType::STRING, 'The device ID to logout'))->required()->setLocation('path'),
                ])
                ->setSuccessMessageOnly();
        });

        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        // Find the device
        $device = $user->getDevice($deviceId);

        if (!$device) {
            return response()->apiNotFound('Device not found.');
        }

        // Prevent logging out from current device via this endpoint
        if ($currentToken && $device->id === $currentToken->id) {
            return response()->apiError('Cannot logout from current device. Use the logout endpoint instead.', 400);
        }

        $user->logoutDevice($deviceId);

        return response()->apiSuccess(null, 'Device logged out successfully.');
    }

    /**
     * Update push token for the current device.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('Update Push Token')
                ->setDescription('Update the push notification token for the current device')
                ->setParams([
                    (new Param('device_push_token', ParamType::STRING, 'The new push notification token'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'device_push_token' => 'required|string',
        ]);

        $currentToken = $request->user()->currentAccessToken();

        if (!$currentToken) {
            return response()->apiError('No active token found.', 400);
        }

        $currentToken->updatePushToken($request->device_push_token);

        return response()->apiSuccess([
            'device_push_token' => $request->device_push_token,
        ], 'Push token updated successfully.');
    }

    /**
     * Update push token for a specific device by device_id.
     *
     * @param Request $request
     * @param string $deviceId
     * @return JsonResponse
     */
    public function updateDevicePushToken(Request $request, string $deviceId): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('Update Device Push Token')
                ->setDescription('Update the push notification token for a specific device')
                ->setParams([
                    (new Param('deviceId', ParamType::STRING, 'The device ID'))->required()->setLocation('path'),
                    (new Param('device_push_token', ParamType::STRING, 'The new push notification token'))->required(),
                ])
                ->setSuccessMessageOnly();
        });

        $request->validate([
            'device_push_token' => 'required|string',
        ]);

        $updated = $request->user()->updateDevicePushToken($deviceId, $request->device_push_token);

        if (!$updated) {
            return response()->apiNotFound('Device not found.');
        }

        return response()->apiSuccess([
            'device_id' => $deviceId,
            'device_push_token' => $request->device_push_token,
        ], 'Push token updated successfully.');
    }

    /**
     * Logout from all other devices except current.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logoutOthers(Request $request): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Devices')
                ->setName('Logout Other Devices')
                ->setDescription('Logout from all devices except the current one')
                ->setSuccessMessageOnly();
        });

        $count = $request->user()->logoutOtherDevices();

        return response()->apiSuccess([
            'devices_logged_out' => $count,
        ], "Successfully logged out from {$count} other device(s).");
    }
}
