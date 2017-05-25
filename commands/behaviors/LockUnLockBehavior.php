<?php

namespace yiicod\cron\commands\behaviors;

use Yii;
use yii\base\Behavior;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * @author Orlov Alexey <aaorlov88@gmail.com>
 */
class LockUnLockBehavior extends Behavior
{
    /**
     * @var bool
     */
    public $enabled = true;

    /**
     * Time live file, 8 hour 28800
     */
    public $timeLock = 28800;

    /**
     * File path
     */
    protected $lockFilePath;

    /**
     * Declares events and the corresponding event handler methods.
     * If you override this method, make sure you merge the parent result to the return value.
     *
     * @return array events (array keys) and the corresponding event handler methods (array values)
     *
     * @see CBehavior::events
     */
    public function events()
    {
        return array_merge(parent::events(), [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
            Controller::EVENT_AFTER_ACTION => 'afterAction',
        ]);
    }

    public function attach($owner)
    {
        if ($this->enabled) {
            parent::attach($owner);
        }
    }

    /**
     * Parses the command line arguments and determines which action to perform.
     *
     * @param array $args command line arguments
     *
     * @return array the action name, named options (name=>value), and unnamed options
     *
     * @since 1.1.5
     */
    protected function resolveRequest($args)
    {
        $options = []; // named parameters
        $params = []; // unnamed parameters
        foreach ($args as $arg) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $arg, $matches)) {  // an option
                $name = $matches[1];
                $value = isset($matches[3]) ? $matches[3] : true;
                if (isset($options[$name])) {
                    if (!is_array($options[$name])) {
                        $options[$name] = [$options[$name]];
                    }
                    $options[$name][] = $value;
                } else {
                    $options[$name] = $value;
                }
            } elseif (isset($action)) {
                $params[] = $arg;
            } else {
                $action = $arg;
            }
        }
        if (!isset($action)) {
            $action = $this->defaultAction;
        }

        //Change "/" for "." if action not default (like "controller/action")
        $action = str_replace('/', '.', $action);

        return [$action, $options, $params];
    }

    /**
     * @param $event
     *
     * @return bool
     */
    public function beforeAction($event)
    {
        $this->prepareLockFilePath();

        if (false === $this->lock()) {
            $event->isValid = false;
            $this->owner->stdout("Cron has run\n", Console::FG_RED);
        }
    }

    /**
     * @param $event
     */
    public function afterAction($event)
    {
        $this->unLock();
    }

    /**
     * Prepare lock file path
     */
    protected function prepareLockFilePath()
    {
        $filePath = sprintf('%s/runtime/locks', Yii::$app->basePath);
        if (false === is_dir($filePath)) {
            @mkdir($filePath, 0755, true);
        }
        $argv = array_diff($_SERVER['argv'], ['yii']);
        list($action, $options, $args) = $this->resolveRequest($argv);
        $this->lockFilePath = sprintf('%s/%s.bin', $filePath, $action . preg_replace('/[^A-Za-z0-9-]+/', '_', trim(implode(' ', $args)) . trim(implode(' ', $options))));
    }

    /**
     * Check the end of the process.
     * If a thread is not locked, it is locked and start command.
     *
     * @return bool
     */
    protected function lock()
    {
        $lockFilePath = $this->lockFilePath;

        // current time
        if (false === file_exists($lockFilePath)) {
            file_put_contents($lockFilePath, time());

            return true;
        } else {
            $timeSec = time();
            // time change file
            $timeFile = @filemtime($lockFilePath) ? @filemtime($lockFilePath) : time();

            // Now find out how much time has passed (in seconds)
            if (($timeSec - $timeFile) > $this->timeLock) {
                $this->unLock();
                file_put_contents($lockFilePath, time());

                return true;
            }

            return false;
        }
    }

    /**
     * Unlocking the process of sending letters
     *
     * @return bool
     */
    protected function unLock()
    {
        $lockFilePath = $this->lockFilePath;

        if (true === file_exists($lockFilePath)) {
            return unlink($lockFilePath);
        } else {
            return true;
        }
    }
}
