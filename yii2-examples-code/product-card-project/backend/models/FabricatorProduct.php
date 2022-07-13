<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;
use yii\helpers\ArrayHelper;
use ZipArchive;
use common\models\{Fabricator, User};

/**
 * Class FabricatorProduct
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class FabricatorProduct extends ActiveRecord
{
    const FOLDER = 'fabricator_products';

    const DESCRIPTION_TYPE_COPYPASTE = 'copypaste';
    const DESCRIPTION_TYPE_COPYRIGHT = 'copyright';

    const STATUS_ACTIVE  = 'active';
    const STATUS_HIDDEN  = 'hidden';
    const STATUS_DELETED = 'deleted';

    public $descriptionTypes = [
        self::DESCRIPTION_TYPE_COPYPASTE => 'Копипаст',
        self::DESCRIPTION_TYPE_COPYRIGHT => 'Копирайт',
    ];

    public static $statuses = [
        self::STATUS_ACTIVE  => 'Активный',
        self::STATUS_HIDDEN  => 'Отключен',
        self::STATUS_DELETED => 'Удален',
    ];

    public $images       = [];
    public $certificates = [];
    public $manuals      = [];
    public $riches       = [];
    public $sourceIds    = [];
    public $sources      = [];
    public $aliasIds     = [];
    public $aliases      = [];
    public $videoIds     = [];
    public $videos       = [];
    public $channels;
    public $productChannels;

    public static function tableName()
    {
        return 'fabricator_product';
    }

    public function behaviors()
    {
        return [
            \yii\behaviors\TimestampBehavior::class,
            [
                'class' => \backend\behaviors\LogChangeBehavior::class,
                'excludedAttributes' => ['updated_at']
            ]
        ];
    }

    public function rules()
    {
        return [
            [['sku', 'fabricator_id', 'brand_id', 'name', 'model'], 'required'],
            ['fabricator_id', 'in', 'range' => Fabricator::find()->select('id')->asArray()->column()],
            ['brand_id', 'in', 'range' => Brand::find()->select('id')->asArray()->column()],
            ['category_id', 'in', 'range' => Category::find()->select('id')->asArray()->column()],
            ['description_type', 'in', 'range' => array_keys($this->descriptionTypes)],
            ['description_type', 'default', 'value' => self::DESCRIPTION_TYPE_COPYPASTE],
            ['status', 'in', 'range' => array_keys(self::$statuses)],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            [['category_id', 'description', 'fullname', 'internal_code', 'vendor_code', 'barcode', 'images',
              'certificates', 'manuals', 'riches', 'sourceIds', 'sources', 'aliasIds', 'aliases', 'videoIds', 'videos', 'channels'], 'safe']
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'name'          => 'Краткое название',
            'fullname'      => 'Полное название',
            'sku'           => 'SKU',
            'vendor_code'   => 'Артикул',
            'brand_id'      => 'Бренд',
            'model'         => 'Модель',
            'category_id'   => 'Категория',
            'fabricator_id' => 'Производитель',
            'user_id'       => 'Создал',
            'barcode'       => 'EAN (штрих-код)',
            'internal_code' => 'Внутренний код товара клиента',
            'description'   => 'Описание',
            'channels'      => 'Каналы',
            'status'        => 'Статус',
            'created_at'    => 'Создан',
            'updated_at'    => 'Изменен',
            'description_type' => 'Тип описания',
        ];
    }

    public function beforeValidate()
    {
        $this->images  = UploadedFile::getInstances($this, 'images');
        $this->manuals = UploadedFile::getInstances($this, 'manuals');
        $this->riches  = UploadedFile::getInstances($this, 'riches');
        $this->certificates = UploadedFile::getInstances($this, 'certificates');

        return parent::beforeValidate();
    }

    public function afterFind()
    {
        // выборка изображений
        $this->images = FabricatorProductImage::find()
            ->where(['product_id' => $this->id])
            ->all();

        // выборка сертификатов и инструкций
        $files = FabricatorProductFile::find()->where(['product_id' => $this->id])->all();
        foreach ($files as $file) {
            switch ($file->type) {
                case FabricatorProductFile::TYPE_CERTIFICATE:
                    $this->certificates[] = $file;
                    break;

                case FabricatorProductFile::TYPE_MANUAL:
                    $this->manuals[] = $file;
                    break;

                case FabricatorProductFile::TYPE_RICH:
                    $this->riches[] = $file;
                    break;
            }
        }

        // выборка источников данных, алиасов, видео
        $additionals = FabricatorProductAdditional::find()
            ->select(['id', 'value', 'type'])
            ->where(['product_id' => $this->id])
            ->asArray()
            ->all();
        foreach ($additionals as $additional) {
            switch ($additional['type']) {
                case FabricatorProductAdditional::TYPE_SOURCE:
                    $this->sourceIds[] = $additional['id'];
                    $this->sources[]   = $additional['value'];
                    break;

                case FabricatorProductAdditional::TYPE_ALIAS:
                    $this->aliasIds[] = $additional['id'];
                    $this->aliases[]  = $additional['value'];
                    break;

                case FabricatorProductAdditional::TYPE_VIDEO:
                    $this->videoIds[] = $additional['id'];
                    $this->videos[]   = $additional['value'];
                    break;
            }
        }

        $this->channels = ArrayHelper::index($this->channel, 'id');
        $this->productChannels = ArrayHelper::index($this->productChannel, 'channel_id');

        return parent::afterFind();
    }

    public function afterSave($insert, $changedAttributes)
    {
        // создание папки для файлов товара
        if (!file_exists($this->path)) {
            mkdir($this->path, 0755);
        }

        // сохранение изображений
        if ($this->images) {
            foreach ($this->images as $image) {
                $productImage = new FabricatorProductImage([
                    'product_id' => $this->id,
                    'user_id'    => Yii::$app->user->id,
                    'image'      => $image
                ]);
                $productImage->save();
            }
        }

        // сохранение сертификатов
        if ($this->certificates) {
            foreach ($this->certificates as $certificate) {
                $productFile = new FabricatorProductFile([
                    'product_id' => $this->id,
                    'user_id'    => Yii::$app->user->id,
                    'file'       => $certificate,
                    'name'       => $certificate->name,
                    'type'       => FabricatorProductFile::TYPE_CERTIFICATE
                ]);
                $productFile->save();
            }
        }

        // сохранение инструкций
        if ($this->manuals) {
            foreach ($this->manuals as $manual) {
                $productFile = new FabricatorProductFile([
                    'product_id' => $this->id,
                    'user_id'    => Yii::$app->user->id,
                    'file'       => $manual,
                    'name'       => $manual->name,
                    'type'       => FabricatorProductFile::TYPE_MANUAL
                ]);
                $productFile->save();
            }
        }

        // сохранение rich контента
        if ($this->riches) {
            foreach ($this->riches as $rich) {
                $productFile = new FabricatorProductFile([
                    'product_id' => $this->id,
                    'user_id'    => Yii::$app->user->id,
                    'file'       => $rich,
                    'name'       => $rich->name,
                    'type'       => FabricatorProductFile::TYPE_RICH
                ]);
                $productFile->save();
            }
        }

        // сохранение источников данных
        $this->updateSources();

        // сохранение алиасы
        $this->updateAliases();

        // сохранение видео
        $this->updateVideo();

        return parent::afterSave($insert, $changedAttributes);
    }

    public function getPath()
    {
        return Yii::getAlias('@frontend') . '/web/uploads/' . self::FOLDER . '/' . $this->id . '/';
    }

    public function getBrand()
    {
        return $this->hasOne(Brand::class, ['id' => 'brand_id']);
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'category_id']);
    }

    public function getFabricator()
    {
        return $this->hasOne(Fabricator::class, ['id' => 'fabricator_id']);
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getChannel()
    {
        return $this->hasMany(Channel::class, ['id' => 'channel_id'])
            ->viaTable(FabricatorProductChannel::tableName(), ['product_id' => 'id']);
    }

    public function getProductChannel()
    {
        return $this->hasMany(FabricatorProductChannel::class, ['product_id' => 'id']);
    }

    /**
     * Выборка полной информации о характеристиках товара по выбранной категории
     *
     * @return boolean|array
     */
    public function getFeatures()
    {
        $features = $this->featuresBySystem();

        if (count($features) == 0) {
            return false;
        }

        // выборка значений характеристик товара
        $featuresValues = $this->getFeaturesValues();

        $data = [];

        // обработка данных хар-к, подготовка к выдаче фронту
        foreach ($features as &$feature) {
            // инициализация массива группы
            if (!isset($data[$feature['group_name']])) {
                $data[$feature['group_name']] = [
                    'id' => $feature['group_id'],
                    'class' => null,
                    'features'  => []
                ];
            }

            // установка класса группы
            if ($feature['important'] && is_null($data[$feature['group_name']]['class'])) {
                $data[$feature['group_name']]['class'] = 'card-accent-info';
            }
            if ($feature['mandatory']) {
                $data[$feature['group_name']]['class'] = 'card-accent-danger';
            }

            // установка названия типа хар-ки
            switch ($feature['type']) {
                case 'multiselect': $feature['type_name'] = 'Несколько из многих'; break;
                case 'select':      $feature['type_name'] = 'Один из многих';      break;
                case 'numerical':   $feature['type_name'] = 'Число';               break;
                case 'range':       $feature['type_name'] = 'Диапазон';            break;
                case 'boolean':     $feature['type_name'] = 'Да/Нет';              break;
                case 'text':        $feature['type_name'] = 'Текст';               break;
            }

            $currentValues   = [];
            $currentValue    = null;
            $unitId          = NULL;
            $featureValue    = NULL;
            $featureValues   = [];
            $featureValueIds = [];

            // все существующие размерности хар-ки $feature['feature_id']
            $units = [];
            if ($feature['dimensions']) {
                $raw = explode('##', $feature['dimensions']);

                for ($i = 0; $i < count($raw); $i++) {
                    $dataDimension = explode('@@', $raw[$i]);
                    $units[$dataDimension[0]] = $dataDimension[1];
                }
            }

            if ($feature['restricted_values']) {
                $feature['restricted_values'] = explode('; ', $feature['restricted_values']);
            }

            // все значения товара в хар-ке $feature['feature_id']
            if (!empty($featuresValues[$feature['feature_id']])) {
                foreach ($featuresValues[$feature['feature_id']] as $row) {
                    $unitId = $row['dimension_unit_id'];
                    $featureValue = $row['value'];
                    $currentValues[] = $featureValue . (!empty($units[$unitId]) ? ' ' . $units[$unitId] : '');
                    $featureValueId = $row['id'];
                    $featureValueIds[] = $featureValueId;
                    $featureValues[$featureValueId] = $featureValue;
                }
                $currentValue = implode(';', $currentValues);
            }

            /**
             * Предустановленные значения хар-к
             */
            if (in_array($feature['type'], ['select', 'multiselect']) && is_array($feature['restricted_values'])) {
                // меняем ключи по-умолчанию на ключи выбранных значений
                if (count($featureValues) > 0) {
                    foreach ($featureValues as $key => $value) {
                        unset($feature['restricted_values'][array_search($value, $feature['restricted_values'])]);
                        $feature['restricted_values'][$key] = $value;
                    }
                }

                $featureValues = $feature['restricted_values'];
            }

            $feature['unitId'] = $unitId;
            $feature['units']  = $units;
            $feature['currentValue']    = $currentValue;
            $feature['featureValue']    = $featureValue;
            $feature['featureValues']   = $featureValues;
            $feature['featureValueIds'] = $featureValueIds;

            $data[$feature['group_name']]['features'][] = $feature;
        }

        return $data;
    }

    private function featuresBySystem()
    {
        return Yii::$app->db->createCommand('
            SELECT
                cf.feature_id,
                f.name,
                f.type,
                (
                    SELECT
                        GROUP_CONCAT(CONCAT(du.id, "@@", du.unit) ORDER BY du.sort SEPARATOR "##")
                    FROM dimension_unit du
                    WHERE du.dimension_id = f.dimension_id
                    ORDER BY du.sort
                ) AS dimensions,
                f.dimension_id,
                cf.mandatory,
                cf.important,
                fg.name AS group_name,
                fg.id AS group_id,
                (
                    SELECT GROUP_CONCAT(fv.value SEPARATOR "; ")
                    FROM category_feature_restricted_value cfrv
                        JOIN feature_value fv
                          ON fv.id = cfrv.feature_value_id
                    WHERE cfrv.feature_id = f.id
                          AND cfrv.category_id = :category_id
                ) AS restricted_values
            FROM category_feature cf
                LEFT JOIN feature f
                  ON f.id = cf.feature_id
                LEFT JOIN feature_group fg
                  ON fg.id = cf.feature_group_id
            WHERE cf.category_id = :category_id
            ORDER BY cf.sort, cf.feature_id
        ')
        ->bindValue(':category_id', $this->category_id)
        ->queryAll();
    }

    private function getFeaturesValues()
    {
        $data = FabricatorProductFeatureValue::find()
            ->select(['id', 'feature_id', 'value', 'dimension_unit_id'])
            ->where(['product_id' => $this->id])
            ->asArray()
            ->all();

        return \yii\helpers\ArrayHelper::index($data, null, 'feature_id');
    }

    private function updateSources()
    {
        if (count($this->sources) == 0) {
            return false;
        }

        foreach ($this->sources as $key => $source) {
            // существующие данные обновляем
            if ($this->sourceIds[$key] && $model = FabricatorProductAdditional::findOne([
                'id' => $this->sourceIds[$key],
                'product_id' => $this->id
            ])) {
                $model->updateAttributes(['value' => $source]);
                continue;
            }

            $model = new FabricatorProductAdditional([
                'product_id' => $this->id,
                'type'       => FabricatorProductAdditional::TYPE_SOURCE,
                'value'      => $source
            ]);
            $model->save();
        }

        return true;
    }

    private function updateAliases()
    {
        if (count($this->aliases) == 0) {
            return false;
        }

        foreach ($this->aliases as $key => $alias) {
            // существующие данные обновляем
            if ($this->aliasIds[$key] && $model = FabricatorProductAdditional::findOne([
                'id' => $this->aliasIds[$key],
                'product_id' => $this->id
            ])) {
                $model->updateAttributes(['value' => $alias]);
                continue;
            }

            $model = new FabricatorProductAdditional([
                'product_id' => $this->id,
                'type'       => FabricatorProductAdditional::TYPE_ALIAS,
                'value'      => $alias
            ]);
            $model->save();
        }

        return true;
    }

    private function updateVideo()
    {
        if (count($this->videos) == 0) {
            return false;
        }

        foreach ($this->videos as $key => $video) {
            // существующие данные обновляем
            if ($this->videoIds[$key] && $model = FabricatorProductAdditional::findOne([
                'id' => $this->videoIds[$key],
                'product_id' => $this->id
            ])) {
                $model->updateAttributes(['value' => $video]);
                continue;
            }

            $model = new FabricatorProductAdditional([
                'product_id' => $this->id,
                'type'       => FabricatorProductAdditional::TYPE_VIDEO,
                'value'      => $video
            ]);
            $model->save();
        }

        return true;
    }

    public function getSizeAllImages()
    {
        if (count($this->images) == 0) {
            return 0;
        }

        $size = 0;
        foreach ($this->images as $image ) {
            $size += filesize($image->path . $image->image);
        }

        return str_replace('.', ',', round($size / 1048576, 2)) . ' mb';
    }

    public function generateZipAllImages()
    {
        $tmpDir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        $zipName = $tmpDir . '/all-images-' . date('dmYHi') . '.zip';

        $zip = new ZipArchive();
        if (!$zip->open($zipName, ZipArchive::CREATE)) {
            throw new \Exception('Cannot create a zip file');
        }

        foreach($this->images as $image){
            $zip->addFile($image->path . $image->image, $image->image);
        }

        $zip->close();

        return $zipName;
    }

    public function generateZipSelectedImages($ids)
    {
        $tmpDir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
        $zipName = $tmpDir . '/selected-images-' . date('dmYHi') . '.zip';

        $zip = new ZipArchive();
        if (!$zip->open($zipName, ZipArchive::CREATE)) {
            throw new \Exception('Cannot create a zip file');
        }

        foreach($this->images as $image){
            if (in_array($image->id, $ids)) {
                $zip->addFile($image->path . $image->image, $image->image);
            }
        }

        $zip->close();

        return $zipName;
    }

    public function getMainImage()
    {
        foreach ($this->images as $image) {
            if ($image->main == FabricatorProductImage::MAIN) {
                return $image;
            }
        }

        return $this->images[0];
    }
}
