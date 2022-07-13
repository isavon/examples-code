<?php
namespace backend\models\search;

use yii\data\ActiveDataProvider;
use common\models\{Fabricator, User};
use backend\models\FabricatorProduct;
use backend\models\{Brand, Category};

/**
 * Class FabricatorProductSearch
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class FabricatorProductSearch extends FabricatorProduct
{
    public function rules()
    {
        return [
            ['id', 'integer'],
            [['name', 'sku', 'model', 'brand_id', 'category_id', 'fabricator_id', 'user_id'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = self::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ]
        ]);

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
                $q->where(['like', Brand::tableName() . '.name', $this->brand_id]);
            }]);
        }

        // фильтр по категории
        if (!empty($this->category_id)) {
            $query->innerJoinWith(['category' => function($q) {
                $q->where(['like', Category::tableName() . '.name', $this->category_id]);
            }]);
        }

        // фильтр по производителю
        if (!empty($this->fabricator_id)) {
            $query->innerJoinWith(['fabricator' => function($q) {
                $q->where(['like', Fabricator::tableName() . '.name', $this->fabricator_id]);
            }]);
        }

        // фильтр по создателю
        if (!empty($this->user_id)) {
            $query->innerJoinWith(['user' => function($q) {
                $q->where(['like', User::tableName() . '.email', $this->user_id]);
            }]);
        }

        return $dataProvider;
    }
}
