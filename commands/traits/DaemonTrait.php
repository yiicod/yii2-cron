<?php

namespace yiicod\cron\commands\traits;

use Exception;
use Yii;
use yiicod\cron\commands\exceptions\IsNotRunningException;
use yiicod\cron\commands\exceptions\IsRunningException;
use yiicod\cron\commands\FileOutput;

trait DaemonTrait
{
    /**
     * @var int
     */
    public $daemonDelay = 15;

    /**
     * @var FileOutput
     */
    private $fileOutput;

    /**
     * Daemon name
     *
     * @return string
     */
    abstract protected function daemonName(): string;

    /**
     * Reload daemon
     * @param callable $worker
     */
    protected function restartDaemon(callable $worker)
    {
        try {
            $this->stopDaemon();
        } finally {
            $this->startDaemon($worker);
        }
    }

    /**
     * Creates daemon.
     * Check is daemon already run and if false then starts daemon and update lock file.
     *
     * @param callable $worker
     *
     * @throws Exception
     */
    protected function startDaemon(callable $worker)
    {
        if (true === $this->isAlreadyRunning()) {
            throw new IsRunningException(sprintf('[%s] is running already.', $this->daemonName()));
        } else {
            $pid = pcntl_fork();
            if ($pid == -1) {
                exit('Error while forking process.');
            } elseif ($pid) {
                exit();
            } else {
                $pid = getmypid();
                $this->addPid($pid);
            }

            // Automatically send every new message to available log routes
            Yii::getLogger()->flushInterval = 1;
            while (true) {
                // Start daemon method
                call_user_func($worker);
                sleep($this->daemonDelay);
            }
        }
    }

    /**
     * Stop daemon
     *
     * @throws Exception
     */
    protected function stopDaemon()
    {
        if (false === $this->isAlreadyRunning()) {
            throw new IsNotRunningException(sprintf('[%s] is not running.', $this->daemonName()));
        }
        if (file_exists($this->getPidsFilePath())) {
            $pids = $this->getPids();
            foreach ($pids as $pid) {
                $this->removePid($pid);
            }
        }
    }

    /**
     * Checks if daemon already running.
     *
     * @return bool
     */
    protected function isAlreadyRunning(): bool
    {
        $result = true;
        $runningPids = $this->getPids();
        if (empty($runningPids)) {
            $result = false;
        } else {
            $systemPids = explode("\n", trim(shell_exec("ps -e | awk '{print $1}'")));
            if (false === empty(array_diff($runningPids, $systemPids))) {
                foreach ($runningPids as $pid) {
                    $this->removePid($pid);
                }
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Add pid
     *
     * @param $pid
     */
    protected function addPid($pid)
    {
        $pids = $this->getPids();
        $pids[] = $pid;
        $this->setPids($pids);
    }

    /**
     * Add pid
     *
     * @param $pid
     */
    protected function removePid($pid)
    {
        $pids = $this->getPids();

        // Remove all process
        $children[] = $pid;
        while ($child = exec("pgrep -P " . reset($children))) {
            array_unshift($children, $child);
        }
        foreach ($children as $child) {
            exec("kill $child 2> /dev/null");
        }

        $pids = array_diff($pids, [$pid]);
        $this->setPids($pids);
    }

    /**
     * Get pids
     *
     * @return array
     */
    protected function getPids()
    {
        $pids = [];
        if (file_exists($this->getPidsFilePath())) {
            $pids = explode(',', trim(file_get_contents($this->getPidsFilePath())));
        }

        return array_filter($pids);
    }

    /**
     * Set pids
     *
     * @return @void
     */
    protected function setPids(array $pids)
    {
        $pidsFile = $this->getPidsFilePath();
        file_put_contents($pidsFile, implode(',', $pids));
    }

    /**
     * Pids file path
     *
     * @return string
     */
    protected function getPidsFilePath()
    {
        return $this->getDaemonFilePath('pids.bin');
    }

    /**
     * Gets path to daemon data.
     * Lock file keeps pids of started daemons.
     *
     * @param string $file
     *
     * @return string
     */
    protected function getDaemonFilePath($file): string
    {
        $path = $this->getDaemonDirPath();

        if (false === is_dir($path)) {
            @mkdir($path, 0755, true);
        }

        return $path . '/' . $file;
    }

    protected function getDaemonDirPath(): string
    {
        return Yii::$app->basePath . '/runtime/daemons/' . strtolower($this->daemonName());
    }

    /**
     * @return FileOutput
     */
    protected function output($text)
    {
        if (null === $this->fileOutput) {
            $this->fileOutput = new FileOutput($this->getDaemonFilePath('output.log'));
        }

        $this->fileOutput->stdout(trim($text));
    }
}
