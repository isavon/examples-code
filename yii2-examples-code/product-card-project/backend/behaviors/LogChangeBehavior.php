<?php

namespace backend\behaviors;

use Yii;
use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\StringHelper;

/**
 * Class LogChangeBehavior
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class LogChangeBehavior extends Behavior
{
    const TYPE_CREATE = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    /**
     * @var array
     */
    public $excludedAttributes = [];

    /**
     * @return array
     */
    public function events()
    {
        if (Yii::$app->id == 'basic-console') {
            return [];
        }

        return [
            ActiveRecord::EVENT_AFTER_INSERT  => 'logCreate',
            ActiveRecord::EVENT_AFTER_UPDATE  => 'logUpdate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'logDelete',
        ];
    }

    /**
     * @inheritdoc
     */
    public function logCreate()
    {
        $logItem = new LogItem([
            'object' => $this->owner,
            'data'   => $this->owner->getAttributes(),
            'type'   => self::TYPE_CREATE
        ]);
        $logItem->save();
    }

    /**
     * @param Event $event
     */
    public function logUpdate(Event $event)
    {
        $owner = $this->owner;
        $changedAttributes = $event->changedAttributes;

        $diff = [];
        foreach ($changedAttributes as $attrName => $attrVal) {
            $newAttrVal = $owner->getAttribute($attrName);

            $newAttrVal = is_float($newAttrVal) ? StringHelper::floatToString($newAttrVal) : $newAttrVal;
            $attrVal = is_float($attrVal) ? StringHelper::floatToString($attrVal) : $attrVal;

            if ($newAttrVal != $attrVal) {
                $diff[$attrName] = [$attrVal, $newAttrVal];
            }
        }

        if ($diff = $this->applyExclude($diff)) {
            $logItem = new LogItem([
                'object' => $owner,
                'data'   => $diff,
                'type'   => self::TYPE_UPDATE
            ]);
            $logItem->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function logDelete()
    {
        $logItem = new LogItem([
            'object' => $this->owner,
            'data'   => $this->owner->getAttributes(),
            'type'   => self::TYPE_DELETE
        ]);
        $logItem->save();
    }

    /**
     * @param array $diff
     * @return array
     */
    private function applyExclude(array $diff)
    {
        foreach ($this->excludedAttributes as $attr) {
            unset($diff[$attr]);
        }

        return $diff;
    }
}
