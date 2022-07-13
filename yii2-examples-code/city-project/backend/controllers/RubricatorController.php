<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use backend\models\Rubricator;
use backend\models\Complex;

/**
 * RubricatorController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class RubricatorController extends Controller
{
    public function actionIndex()
    {
        $rubricators = Rubricator::find()->with('complex')->asArray()->all();
        return $this->render('index', [
            'data' => ArrayHelper::index($rubricators, null, ['type', 'listing_type']),
        ]);
    }

    public function actionSaveTower()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();

        $existsTowerIds = Rubricator::find()
            ->select('complex_id')
            ->where([
                'type' => $post['type'],
                'listing_type' => $post['listingType'],
            ])
            ->column();

        $added = [];
        $deleted = [];
        if (isset($post['complexes'])) {
            foreach ($post['complexes'] as $complexId) {
                if (in_array($complexId, $existsTowerIds)) {
                    unset($existsTowerIds[array_search($complexId, $existsTowerIds)]);
                    continue;
                }

                $model = new Rubricator([
                    'type' => $post['type'],
                    'listing_type' => $post['listingType'],
                    'complex_id' => $complexId
                ]);
                $model->save();

                $added = [
                    'id' => $model->id,
                    'name' => Complex::find()->select('name')->where(['id' => $complexId])->scalar(),
                ];
            }
        }

        if (count($existsTowerIds)) {
            foreach ($existsTowerIds as $complexId) {
                if ($model = Rubricator::findOne([
                    'type' => $post['type'],
                    'listing_type' => $post['listingType'],
                    'complex_id' => $complexId
                ])) {
                    $deleted[] = $model->id;
                    $model->delete();
                }
            }
        }

        return ['added' => $added, 'deleted' => $deleted];
    }

    public function actionChangeShowPrice($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if ($model = Rubricator::findOne($id)) {
            $model->updateAttributes(['show_price' => Yii::$app->request->post()['value']]);
            return ['success' => true];
        }

        return ['success' => false];
    }
}
