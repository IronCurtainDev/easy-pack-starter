<?php

namespace EasyPack\Services;

use EasyPack\Entities\Settings\SettingsRepository;
use EasyPack\Models\Setting;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class SettingsManager
{
    private SettingsRepository $settingsRepo;

    public function __construct(SettingsRepository $settingsRepo)
    {
        $this->settingsRepo = $settingsRepo;
    }

    /**
     * Set a setting.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $dataType
     * @param string|null $description
     * @return Setting
     */
    public function set(string $key, mixed $value = null, ?string $dataType = null, ?string $description = null): Setting
    {
        $data = [
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_data_type' => $dataType,
            'description' => $description,
        ];

        if ($dataType) {
            if ($dataType === Setting::DATA_TYPE_JSON) {
                $data['setting_value'] = json_encode($value);
            }

            if (!in_array($dataType, $this->validDataTypes(), true)) {
                throw new InvalidArgumentException("The data type `{$dataType}` is invalid");
            }
        }

        return $this->setByArray($data);
    }

    /**
     * Update by ID.
     *
     * @param int $id
     * @param array $data
     * @return Setting
     */
    public function updateById(int $id, array $data): Setting
    {
        $this->validate($data, false);
        $setting = $this->settingsRepo->find($id);

        if (!$setting) {
            throw new InvalidArgumentException("Invalid setting ID");
        }

        // if the key is not editable, ignore it
        if (!$setting->is_key_editable) {
            unset($data['setting_key']);
        }

        // if value is not editable, ignore both key and value
        if (!$setting->is_value_editable) {
            unset($data['setting_key'], $data['setting_value']);
        }

        return $this->settingsRepo->update($setting, $data);
    }

    /**
     * Set or update an existing setting.
     *
     * @param string $key
     * @param string|null $value
     * @param string|null $dataType
     * @param string|null $description
     * @return Setting
     */
    public function setOrUpdate(string $key, ?string $value = null, ?string $dataType = null, ?string $description = null): Setting
    {
        $existingSetting = Setting::where('setting_key', $key)->first();

        if ($existingSetting) {
            return $this->settingsRepo->update($existingSetting, [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_data_type' => $dataType,
                'description' => $description,
            ]);
        }

        return $this->set($key, $value, $dataType, $description);
    }

    /**
     * Set a new setting by an array.
     *
     * @param array $data
     * @return Setting
     */
    public function setByArray(array $data): Setting
    {
        $this->validate($data);

        return $this->settingsRepo->create($data);
    }

    /**
     * Retrieve a setting from the database.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = ''): mixed
    {
        $setting = Setting::where('setting_key', $key)->first();

        if ($setting) {
            if (isset($setting->setting_data_type) && $setting->setting_data_type !== null) {
                return $this->castToType($setting->setting_value, $setting->setting_data_type);
            }
            return $setting->setting_value;
        }

        return $default;
    }

    /**
     * Remove a setting if it exists.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $setting = Setting::where('setting_key', $key)->first();

        // only delete if they're editable
        if ($setting && $setting->is_key_editable) {
            $setting->delete();
        }
    }

    /**
     * Validate setting data.
     *
     * @param array $data
     * @param bool $isNewRecord
     * @return void
     */
    protected function validate(array $data, bool $isNewRecord = true): void
    {
        $rules = [
            'setting_key' => 'required|unique:settings,setting_key',
            'setting_value' => 'present',
        ];

        if (!$isNewRecord) {
            // if this is not a new record, it won't be unique in the DB
            $rules['setting_key'] = 'required';
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $message = implode(' ', $validator->messages()->all());
            throw new InvalidArgumentException($message);
        }
    }

    /**
     * Explicit type casting.
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function castToType(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'bool', 'boolean' => (bool) $value,
            'float', 'double', 'real' => (float) $value,
            Setting::DATA_TYPE_JSON => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Get valid data types.
     *
     * @return array
     */
    private function validDataTypes(): array
    {
        return [
            'int', 'integer',
            'bool', 'boolean',
            'float', 'double', 'real',
            'string',
            Setting::DATA_TYPE_JSON,
        ];
    }
}
