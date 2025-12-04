<?php
namespace EasyPack\ApiDocs\Domain\Traits;

use Illuminate\Support\Facades\File;

trait NamesAndPathLocations
{
    /**
     * Get base docs directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getDocsDir($createIfNotExists = false): string
    {
        $dirPath = resource_path('docs');

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get storage path for API responses
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiResponsesStorageDir($createIfNotExists = false): string
    {
        $dirPath = self::getDocsDir() . DIRECTORY_SEPARATOR . 'api_responses';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get auto-generated API responses directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiResponsesAutoGenDir($createIfNotExists = false): string
    {
        $dirPath = self::getApiResponsesStorageDir() . DIRECTORY_SEPARATOR . 'auto_generated';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get manual API responses directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiResponsesManualDir($createIfNotExists = false): string
    {
        $dirPath = self::getApiResponsesStorageDir() . DIRECTORY_SEPARATOR . 'manual';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get public docs output directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    public static function getDocsOutputDir($createIfNotExists = false): string
    {
        $dirPath = public_path('docs');

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get API docs output directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiDocsOutputDir($createIfNotExists = false): string
    {
        $dirPath = self::getDocsOutputDir() . DIRECTORY_SEPARATOR . 'api';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get ApiDoc base directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiDocsDir($createIfNotExists = false): string
    {
        $dirPath = self::getDocsDir() . DIRECTORY_SEPARATOR . 'apidoc';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get auto-generated ApiDoc files directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiDocsAutoGenDir($createIfNotExists = false): string
    {
        $dirPath = self::getApiDocsDir() . DIRECTORY_SEPARATOR . 'auto_generated';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get manual ApiDoc files directory
     *
     * @param bool $createIfNotExists
     * @return string
     */
    protected static function getApiDocsManualDir($createIfNotExists = false): string
    {
        $dirPath = self::getApiDocsDir() . DIRECTORY_SEPARATOR . 'manual';

        if ($createIfNotExists && !File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return $dirPath;
    }

    /**
     * Get tests auto-generated directory path
     *
     * @param null $apiVersion
     * @return string
     */
    public static function getTestsAutoGenDir($apiVersion = null): string
    {
        $path = 'Feature' . DIRECTORY_SEPARATOR . 'AutoGen' . DIRECTORY_SEPARATOR . 'API';

        if ($apiVersion) {
            $path .= DIRECTORY_SEPARATOR . strtoupper($apiVersion);
        }

        return $path;
    }

    /**
     * Get test file path
     *
     * @param $apiVersion
     * @param $relativePath
     * @return string
     */
    public static function getTestFilePath($apiVersion, $relativePath): string
    {
        return base_path('tests/'.self::getTestsAutoGenDir($apiVersion).DIRECTORY_SEPARATOR.$relativePath);
    }

    /**
     * Delete old files in directory
     *
     * @param $dirPath
     * @param $fileExtension
     */
    public static function deleteFilesInDirectory($dirPath, $fileExtension)
    {
        array_map('unlink', glob("$dirPath/*.$fileExtension"));
    }
}
