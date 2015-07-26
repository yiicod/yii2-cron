<?php

namespace yiicod\cron\commands\behaviors;

use Yii;
use yii\base\Behavior;
use yii\console\Controller;

/**
 * @author Orlov Alexey <aaorlov88@gmail.com>
 */
class LockUnLockBehavior extends Behavior
{

    /**
     * File path
     */
    protected $filePath;

    /**
     * Time live file, 8 hour 28800
     */
    public $timeLock = 28800;

    /**
     * Declares events and the corresponding event handler methods.
     * If you override this method, make sure you merge the parent result to the return value.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     * @see CBehavior::events
     */
    public function events()
    {
        return array_merge(parent::events(), array(
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
            Controller::EVENT_AFTER_ACTION => 'afterAction',
        ));
    }

    /**
     * Parses the command line arguments and determines which action to perform.
     * @param array $args command line arguments
     * @return array the action name, named options (name=>value), and unnamed options
     * @since 1.1.5
     */
    protected function resolveRequest($args)
    {
        $options = array(); // named parameters
        $params = array(); // unnamed parameters
        foreach ($args as $arg) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $arg, $matches)) {  // an option
                $name = $matches[1];
                $value = isset($matches[3]) ? $matches[3] : true;
                if (isset($options[$name])) {
                    if (!is_array($options[$name]))
                        $options[$name] = array($options[$name]);
                    $options[$name][] = $value;
                } else
                    $options[$name] = $value;
            }
            elseif (isset($action))
                $params[] = $arg;
            else
                $action = $arg;
        }
        if (!isset($action))
            $action = $this->defaultAction;

        return array($action, $options, $params);
    }

    public function beforeAction($event)
    {
        if (empty($this->filePath)) {
            $argv = array_diff($_SERVER['argv'], array('yiic'));
            list($action, $options, $args) = $this->resolveRequest($argv);
            $this->filePath = '/runtime/' . $event->action->id . preg_replace('/[^A-Za-z0-9-]+/', '_', trim(implode(' ', $args)) . ' ' . trim(implode(' ', $options))) . '.txt';
        }

        if (!$this->_lock()) {
            Yii::$app->end();
        }
    }

    public function afterAction($event)
    {
        $this->_unLock();
    }

    /**
     * Check the end of the process. 
     * If a thread is not locked, it is locked and start command. 
     * @return boolean
     */
    protected function _lock()
    {
        $lockFilePaht = Yii::$app->basePath . $this->filePath;

        // current time
        if (false === file_exists($lockFilePaht)) {
            file_put_contents($lockFilePaht, time());
            return true;
        } else {
            $timeSec = time();
            // time change file
            $timeFile = @filemtime($lockFilePaht) ? @filemtime($lockFilePaht) : time();

            // Now find out how much time has passed (in seconds)
            if (($timeSec - $timeFile) > $this->timeLock) {
                $this->_unLock();

                file_put_contents($lockFilePaht, time());

                return true;
            }
            echo "Cron run\n";
            return false;
        }
    }

    /**
     * Unlocking the process of sending letters
     * @return boolean
     */
    protected function _unLock()
    {
        $lockFilePaht = Yii::$app->basePath . $this->filePath;
        if (true === file_exists($lockFilePaht)) {
            return unlink($lockFilePaht);
        } else {
            return true;
        }
    }

}
