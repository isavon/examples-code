<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\i18n\Formatter;
use yii\helpers\Url;
use moonland\phpexcel\Excel;
use common\models\User;

/**
 * Class TimesheetArtist
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class TimesheetArtist extends ActiveRecord
{
    const FOLDER = 'timeshets-artist';

    private $_user = [];

    public static function tableName()
    {
        return '{{timesheet_artist}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => \yii\behaviors\TimestampBehavior::class,
                'updatedAtAttribute' => false
            ],
            \backend\behaviors\LogChangeBehavior::class
        ];
    }

    public function rules()
    {
        return [
            [['client_id', 'order_id'], 'required'],
            ['client_id', 'in', 'range' => Client::find()->select('id')->asArray()->column()],
            ['order_id', 'in', 'range' => Order::find()->select('id')->asArray()->column()]
        ];
    }

    public function attributeLabels()
    {
        return [
            'client_id'  => 'Клиент',
            'order_id'   => 'Заказ',
            'filename'   => 'Табель',
            'created_at' => 'Создан'
        ];
    }

    public function afterSave($insert, $changedAttributes)
    {
        $fileName = 'Табель-' . $this->order->name . '-' . time();

        $categories = Yii::$app->db->createCommand('
            SELECT
                p.category_id,
                c.name category_name,
                (SELECT cud.client_uploaded_file_id FROM client_uploaded_data cud WHERE cud.client_category_id = p.category_id AND cud.client_id = :client_id ORDER BY id DESC LIMIT 1) file_id,
                (SELECT COUNT(id) FROM client_uploaded_data cud WHERE cud.client_uploaded_file_id = file_id) features,
                SUM(IF (p.description_type = "copypaste", 1, 0)) copypaste,
                SUM(IF (p.description_type = "copyright", 1, 0)) copyright
            FROM product p
            JOIN category c
                ON c.id = p.category_id
            WHERE p.order_id = :order_id
            GROUP BY p.category_id
        ')
        ->bindValue(':client_id', $this->order->client_id)
        ->bindValue(':order_id', $this->order->id)
        ->queryAll();

        $data = [];
        foreach ($categories as $category) {
            if (!$category['file_id']) continue;

            $userData = [];
            $rate = Rate::getByIM($this->order->client_id, $category['file_id'], $category['features']);

            $images = ProductImage::statByOrderAndCategory($this->order_id, $category['category_id']);
            foreach ($images as $image) {
                $userData[$image['user_id']]['photo'] = $image['images'];
            }

            $files = ProductFile::statByOrderAndCategory($this->order_id, $category['category_id']);
            foreach ($files as $file) {
                $userData[$file['user_id']][$file['type']] = $file['files'];
            }

            $features = ProductFeatureValue::statByOrderAndCategory($this->order_id, $category['category_id']);
            foreach ($features as $feature) {
                $userData[$feature['user_id']]['feature'] = $feature['features_values'];
            }

            $copies = Product::statByOrderAndCategory($this->order_id, $category['category_id']);
            foreach ($copies as $copy) {
                $userData[$copy['user_id']]['copypaste'] = $copy['copypaste'];
                $userData[$copy['user_id']]['copyright'] = $copy['copyright'];
            }

            foreach ($userData as $userId => $row) {
                $photosCount = $manualsCount = $certificatesCount = $featuresCount = $copypasteCount = $copyrightCount = '';
                $photosTotal = $manualsTotal = $certificatesTotal = $featuresTotal = $copypasteTotal = $copyrightTotal = 0;

                if (isset($row['photo'])) {
                    $photosCount = $row['photo'];
                    $photosTotal = $photosCount * $rate['cost_photos'];
                }

                if (isset($row['manual'])) {
                    $manualsCount = $row['manual'];
                    $manualsTotal = $manualsCount * $rate['cost_manuals'];
                }

                if (isset($row['certificate'])) {
                    $certificatesCount = $row['certificate'];
                    $certificatesTotal = $certificatesCount * $rate['cost_certificates'];
                }

                if (isset($row['feature'])) {
                    $featuresCount = $row['feature'];
                    $featuresTotal = $featuresCount * $rate['cost_features'];
                }

                if ($row['copypaste'] > 0) {
                    $copypasteCount = $row['copypaste'];
                    $copypasteTotal = $copypasteCount * $rate['cost_copypaste'];
                }

                if ($row['copyright'] > 0) {
                    $copyrightCount = $row['copyright'];
                    $copyrightTotal = $copyrightCount * $rate['cost_copyright'];
                }

                $total = $photosTotal + $manualsTotal + $certificatesTotal + $featuresTotal + $copypasteTotal + $copyrightTotal;

                $data[] = [
                    'category_name' => $category['category_name'],
                    'username' => $this->getUserFio($userId),
                    'ratename' => $rate['name'],
                    'photos' => $photosCount,
                    'manuals' => $manualsCount,
                    'certificates' => $certificatesCount,
                    'features' => $featuresCount,
                    'copypaste' => $copypasteCount,
                    'copyright' => $copyrightCount,
                    'total' => $total
                ];
            }
        }

        Excel::export([
            'fileName' => $fileName,
            'savePath' => $this->path,
            'formatter' => [
                'class' => Formatter::class,
                'nullDisplay' => ''
            ],
            'models' => $data,
            'columns' => [
                'category_name:text:Категория',
                'username:text:ФИО',
                'ratename:text:Тариф',
                'photos:text:Количество фото',
                'manuals:text:Инструкция',
                'certificates:text:Сертификат',
                'features:text:Характерстики',
                'copypaste:text:Описание копипаст',
                'copyright:text:Описание копирайт',
                'total:text:Всего'
            ]
        ]);

        $this->updateAttributes(['filename' => $fileName]);

        return parent::afterSave($insert, $changedAttributes);
    }

    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::class, ['id' => 'order_id']);
    }

    public function getPath()
    {
        $path = Yii::getAlias('@backend') . '/web/uploads/reports/' . self::FOLDER . '/';

        if (!file_exists($path)) {
            mkdir($path, 0755);
        }

        return $path;
    }

    public function getFileWithPath()
    {
        return Url::to('@web/uploads/reports/' . self::FOLDER . '/' . $this->filename . '.xls');
    }

    private function getUserFio($id)
    {
        if (!isset($this->_user[$id])) {
            if (!$this->_user[$id] = User::findOne($id)) {
                return '';
            }
        }

        return $this->_user[$id]->fio;
    }
}