Yii Cron extension
==================

[![Latest Stable Version](https://poser.pugx.org/yiicod/yii2-cron/v/stable)](https://packagist.org/packages/yiicod/yii2-cron) [![Total Downloads](https://poser.pugx.org/yiicod/yii2-cron/downloads)](https://packagist.org/packages/yiicod/yii2-cron) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/yiicod/yii2-cron/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/yiicod/yii2-cron/?branch=master)[![Code Climate](https://codeclimate.com/github/yiicod/yii2-cron/badges/gpa.svg)](https://codeclimate.com/github/yiicod/yii2-cron)

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