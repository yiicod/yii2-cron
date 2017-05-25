<?php
/**
 * Created by PhpStorm.
 * User: orlov
 * Date: 12.04.17
 * Time: 14:38
 */

namespace yiicod\cron\commands;


use yii\console\Controller;
use yii\helpers\Console;
use yiicod\cron\commands\exceptions\IsNotRunningException;
use yiicod\cron\commands\exceptions\IsRunningException;
use yiicod\cron\commands\traits\DaemonTrait;

abstract class DaemonController extends Controller
{
    use DaemonTrait;

    /**
     * @var string
     */
    public $defaultAction = 'start';

    /**
     * Daemon worker
     *
     * @return void
     */
    abstract protected function worker();

    /**
     * Default action. Starts daemon.
     *
     * @return mixed|void
     */
    public function actionStart()
    {
        try {
            $this->stdout(sprintf("[%s] running daemon\n", $this->daemonName()), Console::FG_GREEN);
            $this->startDaemon([$this, 'worker']);
        } catch (IsRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Restart daemon.
     */
    public function actionRestart()
    {
        try {
            $this->stdout(sprintf("[%s] restarting daemon\n", $this->daemonName()), Console::FG_GREEN);
            $this->restartDaemon([$this, 'worker']);
        } catch (IsRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }

    /**
     * Stops daemon.
     *
     * @return mixed|void
     */
    public function actionStop()
    {
        try {
            $this->stopDaemon();
            $this->stdout(sprintf("[%s] stop daemon\n", $this->daemonName()), Console::FG_GREEN);
        } catch (IsNotRunningException $e) {
            $this->stdout("{$e->getMessage()}\n", Console::FG_RED);
        }
    }
}