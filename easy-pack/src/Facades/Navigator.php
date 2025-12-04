<?php

namespace EasyPack\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void addItem(array $item, string $section = 'default')
 * @method static array getItems(string $section = 'default')
 * @method static array getAllSections()
 * @method static void clear()
 * @method static bool hasItems(string $section)
 * @method static string|null getUrl(array $item)
 * @method static bool isActive(array $item)
 *
 * @see \EasyPack\Services\Navigator
 */
class Navigator extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'navigator';
    }
}
