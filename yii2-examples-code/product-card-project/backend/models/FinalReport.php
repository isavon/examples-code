<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\i18n\Formatter;
use yii\helpers\Url;
use moonland\phpexcel\Excel;

/**
 * Class FinalReport
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class FinalReport extends ActiveRecord
{
    const FOLDER = 'final-report';

    public static function tableName()
    {
        return '{{final_report}}';
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
            [['date', 'client_id', 'order_id'], 'required'],
            ['client_id', 'in', 'range' => Client::find()->select('id')->asArray()->column()],
            ['order_id', 'in', 'range' => Order::find()->select('id')->asArray()->column()]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'date'       => 'Месяц',
            'client_id'  => 'Клиент',
            'order_id'   => 'Заказ',
            'filename'   => 'Отчет',
            'created_at' => 'Создан'
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->date = $this->date ? strtotime($this->date) : null;

            return true;
        }

        return false;
    }

    public function afterFind()
    {
        $this->date = date('d.m.Y', $this->date);

        return parent::afterFind();
    }

    public function afterSave($insert, $changedAttributes)
    {
        $fileName = 'Финальный отчет-' . time();

        $stat = Yii::$app->db->createCommand('
            SELECT
                p.sku,
                p.name product_name,
                p.barcode,
                cc.name category_name,
                cc.internal_code,
                (SELECT cud.client_uploaded_file_id FROM client_uploaded_data cud WHERE cud.client_category_id = p.category_id AND cud.client_id = :client_id ORDER BY id DESC LIMIT 1) file_id,
                (SELECT COUNT(id) FROM client_uploaded_data cud WHERE cud.client_uploaded_file_id = file_id) features
            FROM product p
            LEFT JOIN client_category cc
                ON cc.client_id = p.client_id
			    AND cc.category_id = p.category_id
            WHERE p.order_id = :order_id
        ')
        ->bindValue(':client_id', $this->order->client_id)
        ->bindValue(':order_id', $this->order->id)
        ->queryAll();

        if ($orderUploadDate = LogSSH::getLastDateByOrder($this->order_id)) {
            $orderUploadDate = date('d.m.Y', $orderUploadDate);
        }

        $data = [];
        foreach ($stat as $row) {
            $rate = Rate::getByIM($this->order->client_id, $row['file_id'], $row['features']);

            $data[] = [
                'month' => date('d.m.Y', $this->date),
                'order_date' => date('d.m.Y', $this->order->created_at),
                'order_upload' => $orderUploadDate,
                'order_name' => $this->order->name,
                'category_name' => $row['category_name'],
                'category_code' => $row['internal_code'],
                'sku'  => $row['sku'],
                'product_name' => $row['product_name'],
                'barcode' => $row['barcode'],
                'rate' => $rate['name'],
                'order_upload_name' => $this->order->name
            ];
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
                'month:text:Месяц',
                'order_date:text:Дата заказа',
                'order_upload:text:Дата отгрузки',
                'order_name:text:Название заказа',
                'category_name:text:Категория',
                'category_code:text:ID категории',
                'sku:text:ID',
                'product_name:text:Название товара',
                'barcode:text:EAN',
                'rate:text:Тариф',
                'order_upload_name:text:Название выгрузки'
            ]
        ]);

        $this->updateAttributes(['filename' => $fileName]);

        return parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete()
    {
        $file = $this->path . $this->filename . '.xls';

        if (file_exists($file)) {
            \unlink($file);
        }

        return parent::afterDelete();
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
}