<?php

namespace EasyPack\ApiDocs\Domain\Vendors;

use EasyPack\ApiDocs\Domain\Traits\HandlesProcess;
use Symfony\Component\Process\Process;

class CsFixer
{
    use HandlesProcess;

    /**
     * Check if PHP-CS-Fixer is installed
     *
     * @return bool
     */
    public static function isInstalled(): bool
    {
        try {
            $requiredCommands = [
                'php-cs-fixer --version' => 'PHP CS Fixer',
            ];
            return self::verifyRequiredCommandsExist($requiredCommands);
        } catch (\RuntimeException $e) {
            // Try vendor binary
            $vendorPath = base_path('vendor/bin/php-cs-fixer');
            if (file_exists($vendorPath)) {
                return true;
            }
            return false;
        }
    }

    /**
     * Fix code style in the given path
     *
     * @param string $path
     * @return Process
     */
    public static function fix(string $path): Process
    {
        // Try vendor binary first
        $vendorPath = base_path('vendor/bin/php-cs-fixer');
        $command = file_exists($vendorPath)
            ? "$vendorPath fix $path"
            : "php-cs-fixer fix $path";

        return self::runCommand($command);
    }

    /**
     * Get the installation instructions
     *
     * @return string
     */
    public static function getInstallInstructions(): string
    {
        return "PHP-CS-Fixer is not installed. Install it with:\n" .
               "  composer require --dev friendsofphp/php-cs-fixer\n\n" .
               "Or install it globally:\n" .
               "  composer global require friendsofphp/php-cs-fixer";
    }
}
