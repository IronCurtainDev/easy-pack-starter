<?php

namespace EasyPack\Entities\Settings;

use EasyPack\Entities\BaseRepository;
use EasyPack\Models\Setting;

class SettingsRepository extends BaseRepository
{
    protected function getModelClass(): string
    {
        return Setting::class;
    }

    /**
     * Find a setting by its key.
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->findFirstBy('setting_key', $key);
    }

    /**
     * Get setting value by key.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            return $default;
        }

        return $this->castValue($setting->setting_value, $setting->setting_data_type);
    }

    /**
     * Set a setting value by key.
     */
    public function setValue(string $key, mixed $value, ?string $dataType = null): Setting
    {
        $setting = $this->findByKey($key);

        $dataType = $dataType ?? $this->detectDataType($value);
        $stringValue = $this->valueToString($value, $dataType);

        if ($setting) {
            $setting->update([
                'setting_value' => $stringValue,
                'setting_data_type' => $dataType,
            ]);
            return $setting->fresh();
        }

        return $this->create([
            'setting_key' => $key,
            'setting_value' => $stringValue,
            'setting_data_type' => $dataType,
        ]);
    }

    /**
     * Delete a setting by key.
     */
    public function deleteByKey(string $key): bool
    {
        $setting = $this->findByKey($key);

        if (!$setting) {
            return false;
        }

        return $setting->delete();
    }

    /**
     * Get all settings in a group.
     */
    public function getByGroup(int $groupId)
    {
        return $this->where('setting_group_id', $groupId)->all();
    }

    /**
     * Get settings as key-value pairs.
     */
    public function getAllAsKeyValue(): array
    {
        $settings = $this->all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $this->castValue(
                $setting->setting_value,
                $setting->setting_data_type
            );
        }

        return $result;
    }

    /**
     * Cast value based on data type.
     */
    protected function castValue(mixed $value, ?string $dataType): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($dataType) {
            Setting::DATA_TYPE_JSON => json_decode($value, true),
            Setting::DATA_TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            Setting::DATA_TYPE_TEXT, Setting::DATA_TYPE_STRING => (string) $value,
            default => $value,
        };
    }

    /**
     * Convert value to string for storage.
     */
    protected function valueToString(mixed $value, string $dataType): string
    {
        if ($dataType === Setting::DATA_TYPE_JSON) {
            return json_encode($value);
        }

        if ($dataType === Setting::DATA_TYPE_BOOLEAN) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * Detect the data type of a value.
     */
    protected function detectDataType(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            return Setting::DATA_TYPE_JSON;
        }

        if (is_bool($value)) {
            return Setting::DATA_TYPE_BOOLEAN;
        }

        return Setting::DATA_TYPE_STRING;
    }
}
