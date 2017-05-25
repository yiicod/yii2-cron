Cron extensions
===============

Block console commands until executed. 
Unlock cars exhibited at the expiration of the block if the server is down

Config
------

```php
public function behaviors()
{
    return \yii\helpers\ArrayHelper::merge(parent::behaviors(), array('LockUnLockBehavior' => array(
                    'class' => 'yiicod\cron\commands\behaviors\LockUnLockBehavior',
                    'timeLock' => 'duration' //Set timeLock
                ))
    );
}
```