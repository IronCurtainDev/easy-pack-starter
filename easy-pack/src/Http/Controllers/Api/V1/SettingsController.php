<?php

namespace EasyPack\Http\Controllers\Api\V1;

use EasyPack\Http\Controllers\Controller;
use EasyPack\Entities\Settings\SettingsRepository;
use EasyPack\Entities\Settings\SettingGroupsRepository;
use EasyPack\Models\Setting;
use EasyPack\Models\SettingGroup;
use EasyPack\ApiDocs\Docs\APICall;
use EasyPack\ApiDocs\Docs\Param;
use EasyPack\ApiDocs\Docs\ParamType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsRepository $settings,
        protected SettingGroupsRepository $groups
    ) {}

    /**
     * Get all settings.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Settings')
                ->setName('Get All Settings')
                ->setDescription('Get all application settings as key-value pairs')
                ->setSuccessPaginatedObject(Setting::class);
        });

        $settings = $this->settings->getAllAsKeyValue();

        return response()->apiSuccess($settings);
    }

    /**
     * Get a specific setting.
     *
     * @param string $key
     * @return JsonResponse
     */
    public function show(string $key): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Settings')
                ->setName('Get Setting')
                ->setDescription('Get a specific setting by its key')
                ->setParams([
                    (new Param('key', ParamType::STRING, 'The setting key'))->required()->setLocation('path'),
                ])
                ->setSuccessObject(Setting::class);
        });

        $value = $this->settings->getValue($key);

        if ($value === null) {
            return response()->apiNotFound('Setting not found.');
        }

        return response()->apiSuccess(['key' => $key, 'value' => $value]);
    }

    /**
     * Update a setting (requires permission).
     *
     * @param Request $request
     * @param string $key
     * @return JsonResponse
     */
    public function update(Request $request, string $key): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Settings')
                ->setName('Update Setting')
                ->setDescription('Update a specific setting by its key (requires manage_settings permission)')
                ->setParams([
                    (new Param('key', ParamType::STRING, 'The setting key'))->required()->setLocation('path'),
                    (new Param('value', ParamType::STRING, 'The new value for the setting'))->required(),
                ])
                ->setSuccessObject(Setting::class);
        });

        $validator = Validator::make($request->all(), [
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->apiValidationError($validator->errors());
        }

        $setting = $this->settings->setValue($key, $request->value);

        return response()->apiSuccess($setting, 'Setting updated successfully.');
    }

    /**
     * Get all setting groups with settings.
     *
     * @return JsonResponse
     */
    public function groups(): JsonResponse
    {
        document(function () {
            return (new APICall())
                ->setGroup('Settings')
                ->setName('Get Setting Groups')
                ->setDescription('Get all setting groups with their associated settings')
                ->setSuccessPaginatedObject(SettingGroup::class);
        });

        $groups = $this->groups->getAllWithSettings();

        return response()->apiSuccess($groups);
    }
}
