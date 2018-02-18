Yii Cron extension
==================

Provide a logic and functionality to block console commands until they execute. 
Unlocks commands exhibited at the expiration of the block if the server is down.

#### Usage
```php
public function behaviors()
{
    return array(
        'LockUnLockBehavior' => array(
            'class' => 'yiicod\cron\commands\behaviors\LockUnLockBehavior',
            'timeLock' => 0 //Set time lock duration for command in seconds
        )
    );
}
```

Any command can be converted to daemon
```php
class AwesomeCommand extends DaemonController
{
    /**
     * Daemon name
     *
     * @return string
     */
    protected function daemonName(): string
    {
        return 'mail-queue';
    }

    /**
     * Run send mail
     */
    public function worker()
    {
        // Some logic that will be repeateble 
    }
}
```