<?php

namespace EasyPack\Traits;

/**
 * Adds extra API fields that are not stored in the database but can be dynamically set.
 * These fields will appear in toArray() and JSON serialization.
 *
 * Implement getExtraApiFields() in your model to define which fields are allowed.
 *
 * Example:
 * ```php
 * public function getExtraApiFields(): array
 * {
 *     return ['access_token', 'custom_field'];
 * }
 *
 * // Usage:
 * $user->setExtraApiField('access_token', 'your-token');
 * $user->toArray(); // Will include 'access_token'
 * ```
 */
trait HasExtraApiFields
{
    /**
     * Extra fields that are not stored in the database.
     *
     * @var array<string, mixed>
     */
    protected array $extraApiFields = [];

    /**
     * Get the list of allowed extra API field names.
     * Override this method in your model to define allowed fields.
     *
     * @return array<string>
     */
    public function getExtraApiFields(): array
    {
        return [];
    }

    /**
     * Set an extra API field value.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     * @throws \InvalidArgumentException If the field is not allowed
     */
    public function setExtraApiField(string $key, mixed $value): static
    {
        $allowedFields = $this->getExtraApiFields();

        if (!empty($allowedFields) && !in_array($key, $allowedFields)) {
            throw new \InvalidArgumentException("Field '{$key}' is not an allowed extra API field.");
        }

        $this->extraApiFields[$key] = $value;
        return $this;
    }

    /**
     * Get an extra API field value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getExtraApiField(string $key, mixed $default = null): mixed
    {
        return $this->extraApiFields[$key] ?? $default;
    }

    /**
     * Check if an extra API field is set.
     *
     * @param string $key
     * @return bool
     */
    public function hasExtraApiField(string $key): bool
    {
        return array_key_exists($key, $this->extraApiFields);
    }

    /**
     * Remove an extra API field.
     *
     * @param string $key
     * @return $this
     */
    public function removeExtraApiField(string $key): static
    {
        unset($this->extraApiFields[$key]);
        return $this;
    }

    /**
     * Get all extra API fields.
     *
     * @return array<string, mixed>
     */
    public function getAllExtraApiFields(): array
    {
        return $this->extraApiFields;
    }

    /**
     * Clear all extra API fields.
     *
     * @return $this
     */
    public function clearExtraApiFields(): static
    {
        $this->extraApiFields = [];
        return $this;
    }

    /**
     * Set multiple extra API fields at once.
     *
     * @param array<string, mixed> $fields
     * @return $this
     */
    public function setExtraApiFields(array $fields): static
    {
        foreach ($fields as $key => $value) {
            $this->setExtraApiField($key, $value);
        }
        return $this;
    }

    /**
     * Convert the model instance to an array.
     * Overrides the base method to include extra API fields.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Add extra API fields to the array
        foreach ($this->extraApiFields as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }
}
