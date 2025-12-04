<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Setting extends Model
{
    public const DATA_TYPE_JSON = 'json';
    public const DATA_TYPE_TEXT = 'text';
    public const DATA_TYPE_STRING = 'string';
    public const DATA_TYPE_BOOLEAN = 'bool';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_data_type',
        'description',
        'setting_group_id',
    ];

    protected $hidden = [
        'setting_key',
        'setting_value',
        'setting_data_type',
        'description',
        'is_key_editable',
        'is_value_editable',
    ];

    protected $appends = [
        'key',
        'value',
    ];

    protected $visible = [
        'id',
        'key',
        'value',
        'created_at',
        'updated_at',
    ];

    /**
     * Searchable fields for the model.
     */
    public array $searchable = [
        'setting_key',
        'setting_value',
        'description',
    ];

    public function getCreateRules(): array
    {
        return [
            'setting_key' => 'required|unique:settings,setting_key',
        ];
    }

    public function getUpdateRules($id = null): array
    {
        return [
            'setting_key' => [
                'required',
                Rule::unique('settings', 'setting_key')->ignore($id),
            ],
        ];
    }

    public function getIsKeyEditableAttribute($value): bool
    {
        if (isset($this->attributes['is_key_editable'])) {
            return (bool) $this->attributes['is_key_editable'];
        }

        return true;
    }

    public function getIsValueEditableAttribute($value): bool
    {
        if (isset($this->attributes['is_value_editable'])) {
            return (bool) $this->attributes['is_value_editable'];
        }

        return true;
    }

    /**
     * Returns the setting key.
     */
    public function getKeyAttribute(): ?string
    {
        return $this->setting_key;
    }

    /**
     * Returns the setting value.
     */
    public function getValueAttribute(): mixed
    {
        return $this->setting_value;
    }

    /**
     * Relationship to the setting group.
     */
    public function group()
    {
        return $this->belongsTo(SettingGroup::class, 'setting_group_id');
    }
}
