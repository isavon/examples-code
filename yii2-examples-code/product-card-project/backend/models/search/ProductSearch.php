<?php
namespace backend\models\search;

use yii\data\ActiveDataProvider;
use backend\models\Product;

/**
 * Class ProductSearch
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ProductSearch extends Product
{
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['name', 'sku', 'model', 'brand_id', 'category_id', 'client_id', 'order_id', 'user_id'], 'safe']
        ];
    }

    public function search($params)
    {
        $query = self::find()->where([self::tableName() . '.verified' => self::VERIFIED_YES]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query
        ]);

        if (isset($params['categoryProducts'])) {
            $query->andFilterWhere(['category_id' => $params['categoryProducts']]);
        }

        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([self::tableName() . '.id' => $this->id])
              ->andFilterWhere(['like', self::tableName() . '.name', $this->name])
              ->andFilterWhere(['like', self::tableName() . '.sku', $this->sku])
              ->andFilterWhere(['like', self::tableName() . '.model', $this->model]);

        // фильтр по бренду
        if (!empty($this->brand_id)) {
            $query->innerJoinWith(['brand' => function($q) {
                $q->where(['like', 'brand.name', $this->brand_id]);
            }]);
        }

        // фильтр по категории
        if (!empty($this->category_id)) {
            $query->innerJoinWith(['category' => function($q) {
                $q->where(['like', 'category.name', $this->category_id]);
            }]);
        }

        // фильтр по клиенту
        if (!empty($this->client_id)) {
            $query->innerJoinWith(['client' => function($q) {
                $q->where(['like', 'client.name', $this->client_id]);
            }]);
        }

        // фильтр по заказу
        if (!empty($this->order_id)) {
            $query->innerJoinWith(['order' => function($q) {
                $q->where(['like', 'order.name', $this->order_id]);
            }]);
        }

        // фильтр по пользователю
        if (!empty($this->user_id)) {
            $query->innerJoinWith(['user' => function($q) {
                $q->where(['like', 'user.email', $this->user_id]);
            }]);
        }

        return $dataProvider;
    }
}