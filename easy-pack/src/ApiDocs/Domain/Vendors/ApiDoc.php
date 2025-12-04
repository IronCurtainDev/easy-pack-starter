<?php

namespace EasyPack\ApiDocs\Domain\Vendors;

use EasyPack\ApiDocs\Domain\Traits\HandlesProcess;
use EasyPack\ApiDocs\Domain\Traits\NamesAndPathLocations;
use Symfony\Component\Process\Process;

class ApiDoc
{
    use HandlesProcess;
    use NamesAndPathLocations;

    /**
     * Check if ApiDoc.js is installed
     *
     * @return bool
     */
    public static function isInstalled(): bool
    {
        try {
            $requiredCommands = [
                'apidoc --help' => 'ApiDoc.js',
            ];
            return self::verifyRequiredCommandsExist($requiredCommands);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    /**
     * Compile ApiDoc documentation
     *
     * @return Process
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public static function compile(): Process
    {
        $command = implode(' ', [
            'apidoc',
            '--input', self::getApiDocsDir(true),
            '--output', self::getApiDocsOutputDir(true),
        ]);

        return self::runCommandMustSucceed($command);
    }

    /**
     * Get the installation instructions
     *
     * @return string
     */
    public static function getInstallInstructions(): string
    {
        return "\n" .
               "  ╔══════════════════════════════════════════════════════════════════════════════╗\n" .
               "  ║  ApiDoc.js is NOT installed - HTML documentation will NOT be generated!     ║\n" .
               "  ╚══════════════════════════════════════════════════════════════════════════════╝\n" .
               "\n" .
               "  To generate HTML documentation, install ApiDoc.js using one of these methods:\n" .
               "\n" .
               "  Option 1 - Global installation (recommended):\n" .
               "    npm install -g apidoc\n" .
               "\n" .
               "  Option 2 - Local dev dependency:\n" .
               "    npm install --save-dev apidoc\n" .
               "\n" .
               "  After installation, run 'php artisan generate:docs' again.\n" .
               "\n" .
               "  Note: Swagger (JSON/YAML) and OpenAPI files will still be generated.\n" .
               "        Only the HTML documentation requires ApiDoc.js.\n";
    }
}
