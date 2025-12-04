<?php

namespace EasyPack\ApiDocs\Docs;

use Illuminate\Contracts\Support\Arrayable;

class Param implements Arrayable, \JsonSerializable
{
    // Parameter Locations
    public const LOCATION_PATH = 'path';
    public const LOCATION_QUERY = 'query';
    public const LOCATION_HEADER = 'header';
    public const LOCATION_COOKIE = 'cookie';
    public const LOCATION_BODY = 'body';
    public const LOCATION_FORM = 'formData';

    // Data Types
    public const TYPE_STRING = 'string';
    public const TYPE_INT = 'integer';
    public const TYPE_NUMBER = 'number';
    public const TYPE_FLOAT = 'number';
    public const TYPE_DOUBLE = 'number';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_ARRAY = 'array';

    protected $fieldName;
    protected $required = true;
    protected $dataType;
    protected $defaultValue;
    protected $description = '';
    protected $location;
    protected $model;
    protected $collectionFormat;
    protected $items;
    protected $variable;
    protected $example;

    public function __construct($fieldName = null, $dataType = self::TYPE_STRING, $description = null, $location = null)
    {
        $this->fieldName = $fieldName;
        $this->setDataType($dataType);
        $this->location = $location;
        if (!$description && $fieldName) {
            $this->description = ucfirst(str_replace('_', ' ', $fieldName));
        } else {
            $this->description = $description;
        }
    }

    public static function getParamLocations()
    {
        return [
            self::LOCATION_HEADER,
            self::LOCATION_PATH,
            self::LOCATION_QUERY,
            self::LOCATION_FORM,
        ];
    }

    public static function getDataTypes()
    {
        return [
            self::TYPE_STRING,
            self::TYPE_INT,
            self::TYPE_NUMBER,
            self::TYPE_FLOAT,
            self::TYPE_DOUBLE,
            self::TYPE_BOOLEAN,
            self::TYPE_ARRAY,
        ];
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function required()
    {
        $this->required = true;
        return $this;
    }

    public function optional()
    {
        $this->required = false;
        return $this;
    }

    public function getDataType(): string
    {
        return ucfirst($this->dataType);
    }

    public function dataType(string $dataType)
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function defaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function description(string $description)
    {
        $this->description = $description;
        return $this;
    }

    public function getName()
    {
        return $this->fieldName;
    }

    public function field($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation(string $location)
    {
        $this->location = $location;
        return $this;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public static function getSwaggerDataType($dataType)
    {
        $dataType = strtolower($dataType);

        return match ($dataType) {
            'integer' => 'integer',
            'float', 'double' => 'number',
            'boolean' => 'boolean',
            'array' => 'array',
            'object', 'model' => 'object',
            default => 'string',
        };
    }

    public function toArray(): array
    {
        return [
            'fieldName' => $this->fieldName,
            'required' => $this->required,
            'dataType' => $this->dataType,
            'defaultValue' => $this->defaultValue,
            'location' => $this->location,
            'model' => $this->model,
            'variable' => $this->variable,
            'example' => $this->example,
            'collectionFormat' => $this->collectionFormat,
            'items' => $this->items,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function getExample()
    {
        return $this->example;
    }

    public function setExample($example)
    {
        $this->example = $example;
        return $this;
    }

    public function getVariable()
    {
        return $this->variable;
    }

    public function setVariable($variable)
    {
        // clean up and add the braces if they're not there
        $variable = '{{' . trim($variable, " \t\n\r\0\x0B{}") . '}}';
        $this->variable = trim($variable);
        return $this;
    }

    public function setDataType(string $dataType): Param
    {
        $this->dataType = $dataType;

        // set the default array type
        if ($dataType === self::TYPE_ARRAY) {
            $this->setCollectionFormat('multi');
            $this->setArrayType(self::TYPE_STRING);
        }

        return $this;
    }

    public function setDescription(?string $description): Param
    {
        $this->description = $description;
        return $this;
    }

    public function getCollectionFormat(): string
    {
        return $this->collectionFormat ?? '';
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setCollectionFormat(string $collectionFormat): Param
    {
        $this->collectionFormat = $collectionFormat;
        return $this;
    }

    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    public function setArrayType(string $dataType)
    {
        $this->items = [
            'type' => $dataType,
        ];

        return $this;
    }
}
