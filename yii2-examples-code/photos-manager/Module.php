<?php

namespace Photos;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Module
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos
 */
class Module extends \Core\Base\Module
{
    public function init()
    {
        parent::init();

        Yii::configure($this, ArrayHelper::merge(
            require(__DIR__ . '/Common/config/main.php'),
            require(__DIR__ . '/Common/config/main-local.php'),
            require Yii::getAlias('@module/config/main.php'),
            require Yii::getAlias('@module/config/main-local.php')
        ));

        $this->bootstrap();
    }
}
