<?php

namespace EasyPack\ApiDocs\Docs;

class ParamType
{
    const STRING = 'string';
    const INTEGER = 'integer';
    const BOOLEAN = 'boolean';
    const NUMBER = 'number';
    const ARRAY = 'array';
    const OBJECT = 'object';
    const FILE = 'file';
    const MODEL = 'model';

    public static function getDataTypes(): array
    {
        return [
            self::STRING,
            self::INTEGER,
            self::BOOLEAN,
            self::NUMBER,
            self::ARRAY,
            self::OBJECT,
            self::FILE,
            self::MODEL,
        ];
    }
}
