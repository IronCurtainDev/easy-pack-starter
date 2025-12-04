<?php

namespace EasyPack\ApiDocs\Domain\Traits;

use Symfony\Component\Process\Process;

trait HandlesProcess
{
    /**
     * Verify pre-requisite software exists
     *
     * @param array $list Array of command => name pairs
     * @return bool
     * @throws \RuntimeException
     */
    protected static function verifyRequiredCommandsExist(array $list): bool
    {
        foreach ($list as $command => $name) {
            $process = Process::fromShellCommandline($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException("{$name} not found. This is required to proceed. Check by typing `{$command}` and press enter.");
            }
        }

        return true;
    }

    /**
     * Run a shell command and return the process
     *
     * @param string $command
     * @param string|null $cwd
     * @param int $timeout
     * @return Process
     */
    protected static function runCommand(string $command, ?string $cwd = null, int $timeout = 60): Process
    {
        $process = Process::fromShellCommandline($command, $cwd);
        $process->setTimeout($timeout);
        $process->run();

        return $process;
    }

    /**
     * Run a shell command that must succeed
     *
     * @param string $command
     * @param string|null $cwd
     * @param int $timeout
     * @return Process
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    protected static function runCommandMustSucceed(string $command, ?string $cwd = null, int $timeout = 60): Process
    {
        $process = Process::fromShellCommandline($command, $cwd);
        $process->setTimeout($timeout);
        $process->mustRun();

        return $process;
    }
}
