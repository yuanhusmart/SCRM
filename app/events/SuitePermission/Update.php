<?php

namespace app\events\SuitePermission;

use app\events\Event;
use common\models\SuitePermission;


/**
 * @method SuitePermission|static original($value = null)
 * @method SuitePermission|static permission($value = null)
 * @method array|static data($value = null)
 */
class Update extends Event
{
    /**
     * @var SuitePermission
     */
    public $original;

    /**
     * @var array
     */
    public $data;

    /**
     * @var SuitePermission
     */
    public $permission;

    public static $handlers = [
        [self::class, 'handleStatusDisable'],
    ];

    /**
     * @param self $event
     * @return void
     */
    public static function handleStatusDisable($event)
    {
        // 之前是开启, 之后是禁用需要把子集的禁用状态改为禁用
        if ($event->original->status == 1 && $event->permission->status == 2) {
            SuitePermission::updateAll([
                'status' => 2
            ], [
                'like', 'path', $event->original->path . '-%', false
            ]);
        }
    }

}