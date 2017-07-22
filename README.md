Cron extensions (Yii 2)
=======================

Provides the logic and functionality to block console commands until they execute. 
Unlocks commands exhibited at the expiration of the block if the server is down.

Usage
------
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