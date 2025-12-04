<?php

namespace EasyPack\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SettingGroup extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'sort_order',
    ];

    /**
     * The attributes that should be visible for serialization.
     * Used for API responses and Swagger documentation.
     *
     * @var list<string>
     */
    protected $visible = [
        'id',
        'slug',
        'name',
        'description',
        'sort_order',
        'created_at',
        'updated_at',
    ];

    /**
     * Searchable fields for the model.
     */
    public array $searchable = [
        'name',
        'slug',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function getCreateRules(): array
    {
        return [
            'name' => 'required|min:2',
        ];
    }

    public function getUpdateRules(): array
    {
        return [
            'name' => 'required|min:2',
        ];
    }

    /**
     * Relationship to settings.
     */
    public function settings()
    {
        return $this->hasMany(Setting::class, 'setting_group_id');
    }
}
