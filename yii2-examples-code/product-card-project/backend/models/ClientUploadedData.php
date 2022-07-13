<?php

namespace backend\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Class ClientUploadedData
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ClientUploadedData extends ActiveRecord
{
    public static function tableName()
    {
        return '{{client_uploaded_data}}';
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

    public function getClient()
    {
        return $this->hasOne(Client::class, ['id' => 'client_id']);
    }

    public function getClientCategory()
    {
        return $this->hasOne(ClientCategory::class, ['id' => 'client_category_id']);
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'client_category_id']);
    }

    public static function getShortStatistic($clientId, $fileId)
    {
        return (new Query())
            ->select([
                'category_total'  => 'COUNT(DISTINCT cud.category_internal_code)',
                'catefory_relate' => 'COUNT(DISTINCT cud.client_category_id)',
                'feature_total'   => 'COUNT(cud.feature_name)',
                'feature_relate'  => 'COUNT(cud.feature_id)'
            ])
            ->from(['cud' => self::tableName()])
            ->where([
                'cud.client_id' => $clientId,
                'cud.client_uploaded_file_id' => $fileId
            ])
            ->one()
        ;
    }

    public static function modifyClientCategory($internalCode, $categoryId)
    {
        if (!$category = Category::findOne($categoryId)) {
            return false;
        }

        return Yii::$app->db->createCommand('UPDATE ' . self::tableName() . ' SET client_category_id = :category_id, category_name = :category_name WHERE category_internal_code = :internal_code')
            ->bindValue(':category_id', $categoryId)
            ->bindValue(':category_name', $category->name)
            ->bindValue(':internal_code', $internalCode)
        ->execute();
    }

    public function relateData($post)
    {
        /* ОБНОВЛЕНИЕ ХАР-КИ КЛИЕНТА */
        if (isset($post['client_feature_id']) && !empty($post['client_feature_id']) && intval($post['client_feature_id'])) {
            if ($feature = Feature::findOne($post['client_feature_id'])) {
                $this->updateAttributes([
                    'feature_id'   => $feature->id,
                    'feature_name' => $feature->name
                ]);
            }
        }
        /* END */

        /* ОБНОВЛЕНИЕ ХАР-КИ РВ */
        if (isset($post['system_feature_id']) && !empty($post['system_feature_id']) && intval($post['system_feature_id'])) {
            if ($feature = Feature::findOne($post['system_feature_id'])) {
                $this->updateAttributes(['feature_id' => $feature->id]);
            }
        }
        /* END */

        /* ОБНОВЛЕНИЕ ГРУППЫ ХАР-К КЛИЕНТА */
        if (isset($post['client_feature_group_id']) && !empty($post['client_feature_group_id']) && intval($post['client_feature_group_id'])) {
            // ищем группу РВ
            if ($group = FeatureGroup::findOne($post['client_feature_group_id'])) {
                if (!$clientGroup = ClientFeatureGroup::findOne(['client_id' => $this->client_id, 'name' => $group->name])) {
                    $clientGroup = new ClientFeatureGroup();
                    $clientGroup->client_id = $this->client_id;
                    $clientGroup->name      = $group->name;
                    $clientGroup->save();
                }

                $this->updateAttributes([
                    'client_feature_group_id' => $clientGroup->id,
                    'feature_group_name'      => $clientGroup->name
                ]);
            }
        }
        /* END */

        /* ОБНОВЛЕНИЕ ГРУППЫ PB */
        if (isset($post['system_feature_group_id']) && !empty($post['system_feature_group_id']) && intval($post['system_feature_group_id'])) {
            if ($group = FeatureGroup::findOne($post['system_feature_group_id'])) {
                if (CategoryFeature::find()->where(['category_id' => $this->client_category_id, 'feature_id' => $this->feature_id])->count() == 0) {
                    $categoryFeature = new CategoryFeature();
                    $categoryFeature->category_id      = $this->client_category_id;
                    $categoryFeature->feature_id       = $this->feature_id;
                    $categoryFeature->feature_group_id = $group->id;
                    $categoryFeature->save();
                }
            }
        }
        /* END */

        return ['success' => true];
    }

    public static function getCategories($fileId)
    {
        return \yii\helpers\ArrayHelper::map(self::find()->distinct()->where(['client_uploaded_file_id' => $fileId])->all(), 'client_category_id', 'category_name');
    }
}