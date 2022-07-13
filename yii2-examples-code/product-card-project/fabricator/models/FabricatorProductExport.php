<?php

namespace fabricator\models;

use Yii;
use yii\db\ActiveRecord;
use yii\i18n\Formatter;
use moonland\phpexcel\Excel;

/**
 * Class FabricatorProductExport
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class FabricatorProductExport extends ActiveRecord
{
    const FORMAT_HORIZONTAL = 'horizontal';
    const FORMAT_VERTICAL   = 'vertical';

    const STATUS_PROCESSED = 'processed';
    const STATUS_ERROR     = 'error';

    public static $formats = [
        self::FORMAT_HORIZONTAL => 'Excel (горизонтальный)',
        self::FORMAT_VERTICAL   => 'Excel (вертикальный)',
    ];

    public static $statuses = [
        self::STATUS_PROCESSED => 'Обработан',
        self::STATUS_ERROR     => 'Ошибка',
    ];

    public static function tableName()
    {
        return '{{fabricator_product_export}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'updatedAtAttribute' => false
            ]
        ];
    }

    public function rules()
    {
        return [
            [['fabricator_id', 'name', 'format'], 'required'],
            ['format', 'in', 'range' => array_keys(self::$formats)],
            ['products', 'integer'],
            ['status', 'in', 'range' => array_keys(self::$statuses)],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name'       => 'Скачать файл ',
            'format'     => 'Формат экспорта',
            'products'   => 'Товары',
            'status'     => 'Статус',
            'created_at' => 'Дата запроса',
        ];
    }

    public static function generateHorizontal($products)
    {
        $columns = [
            'sku:text:id',
            'category_code:text:id категории',
            'barcode:text:GTIN',
            'name:text:Наименование',
            'description:text:Маркетинговое описание',
            'brand_name:text:Бренд',
        ];

        $data = [];
        foreach ($products as $key => $product) {
            if (!$features = $product->features) {
                continue;
            }

            $data[$key] = [
                'sku'           => $product->sku,
                'category_code' => $product->category->id,
                'barcode'       => $product->barcode,
                'name'          => html_entity_decode($product->name),
                'description'   => html_entity_decode(strip_tags($product->description)),
                'brand_name'    => $product->brand->name,
            ];

            foreach ($features as $groupFeatures) {
                foreach ($groupFeatures['features'] as $feature) {
                    $column = 'feature_' . $feature['feature_id'] . ':raw:' . $feature['name'];
                    if (!in_array($column, $columns)) {
                        $columns[] = $column;
                    }

                    $data[$key]['feature_' . $feature['feature_id']] = !is_null($feature['featureValue']) ? $feature['featureValue'] . ' ' : '';
                }
            }
        }

        $filename = date('dmYHi') . '-H-' . count($products);

        Excel::export([
            'fileName' => $filename,
            'savePath' => self::getPath(),
            'formatter' => [
                'class' => Formatter::class,
                'nullDisplay' => ''
            ],
            'models' => $data,
            'columns' => $columns
        ]);

        return $filename . '.xlsx';
    }

    public static function generateVertical($products)
    {
        $data = [];
        foreach ($products as $product) {
            if (!$features = $product->features) {
                continue;
            }

            $categoryId = $product->category->id;

            foreach ($features as $groupFeatures) {
                foreach ($groupFeatures['features'] as $feature) {
                    switch ($feature['type']) {
                        case 'select':
                        case 'multiselect':
                            $value = implode('; ', $feature['featureValues']);
                            break;

                        case 'numerical':
                            $value = !is_null($feature['featureValue']) ? $feature['featureValue'] . ' ' : '';
                            break;

                        case 'boolean':
                            $value = !is_null($feature['currentValue'])
                                ? ProductFeatureValue::$yesno[$feature['currentValue']]
                                : '';
                            break;

                        default:
                            $value = $feature['currentValue'];
                    }

                    $data[] = [
                        'sku'           => $product->sku,
                        'category_code' => $categoryId,
                        'feature_name'  => $feature['name'],
                        'feature_value' => $value
                    ];
                }
            }
        }

        $filename = date('dmYHi') . '-V-' . count($products);

        Excel::export([
            'fileName' => $filename,
            'savePath' => self::getPath(),
            'formatter' => [
                'class' => Formatter::class,
                'nullDisplay' => ''
            ],
            'models' => $data,
            'columns' => [
                'sku:text:код товара',
                'category_code:text:тип товара',
                'feature_name:text:имя свойства',
                'feature_value:text:значение свойства',
            ]
        ]);

        return $filename . '.xlsx';
    }

    public static function getPath()
    {
        $path = Yii::getAlias('@fabricator') . '/web/uploads/export/' . Yii::$app->user->getIdentity()->correctUserId . '/';

        if (!file_exists($path)) {
            mkdir($path, 0755);
        }

        return $path;
    }

    public function getFileWithPath()
    {
        return Yii::$app->getUrlManager()->getHostInfo() . Yii::$app->getUrlManager()->baseUrl . '/uploads/export/' . Yii::$app->user->getIdentity()->correctUserId . '/' . $this->name;
    }
}
