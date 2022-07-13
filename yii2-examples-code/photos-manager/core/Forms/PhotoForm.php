<?php

namespace Photos\Core\Forms;

use Core\Forms\CompositeForm;
use Photos\Core\ActiveRecord\Photo\PhotoRecord;
use Yii;
use yii\web\UploadedFile;

/**
 * Class PhotoForm
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Forms
 */
class PhotoForm extends CompositeForm
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $translations = [];
        foreach (Yii::$app->params['languages'] as $key => $label) {
            $translations[] = new TranslationForm($key);
        }
        $this->translations = $translations;

        parent::__construct($config);
    }

    /**
     * @param PhotoRecord $photo
     * @return PhotoForm
     */
    public static function createFromModel(PhotoRecord $photo)
    {
        $translations = [];
        $form = new self();
        $form->id = $photo->id;
        foreach (Yii::$app->params['languages'] as $key => $label) {
            $translations[] = new TranslationForm($key, $photo->getTranslation($key)->one());
        }
        $form->translations = $translations;
        return $form;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            ['file', 'file', 'extensions' => 'jpg,jpeg,png', 'skipOnEmpty' => true],
        ];
    }

    /**
     * @return array
     */
    protected function internalForms(): array
    {
        return ['translations'];
    }
}
