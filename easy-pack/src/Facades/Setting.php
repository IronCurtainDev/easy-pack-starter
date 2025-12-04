<?php

namespace EasyPack\Facades;

use EasyPack\Services\SettingsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \EasyPack\Models\Setting set(string $key, mixed $value = null, ?string $dataType = null, ?string $description = null)
 * @method static \EasyPack\Models\Setting updateById(int $id, array $data)
 * @method static \EasyPack\Models\Setting setOrUpdate(string $key, ?string $value = null, ?string $dataType = null, ?string $description = null)
 * @method static \EasyPack\Models\Setting setByArray(array $data)
 * @method static mixed get(string $key, mixed $default = '')
 * @method static void forget(string $key)
 *
 * @see \EasyPack\Services\SettingsManager
 */
class Setting extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SettingsManager::class;
    }
}
