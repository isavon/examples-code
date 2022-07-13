<?php

namespace Photos\Widgets\PhotoManager;

use Core\View\BaseWidget;
use Photos\Widgets\PhotoManager\Providers\PhotoManagerProvider;

/**
 * Class PhotoManagerWidget
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Widgets\PhotoManager
 */
class PhotoManagerWidget extends BaseWidget
{
    public $jsEvents;

    /**
     * @var PhotoManagerProvider
     */
    public $provider = PhotoManagerProvider::class;

    /**
     * @return string
     */
    public function run()
    {
        $provider = \Yii::$container->get($this->provider);

        return $this->render('index', [
            'photos' => $provider->getPhotos()
        ]);
    }
}
