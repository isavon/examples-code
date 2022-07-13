<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use backend\models\Channel;
use backend\models\search\ChannelSearch;

/**
 * Class ChannelController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ChannelController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['administrator'],
                    ],
                ],
            ]
        ];
    }

    public function actionList()
    {
        $searchModel = new ChannelSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('list', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate()
    {
        $model = new Channel();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Канал создан');

            return $this->redirect(['list']);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Канал изменен');

            return $this->redirect(['list']);
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionDelete($id)
    {
        $model = $this->loadModel($id);

        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Канал удален');
        }

        return $this->redirect(['list']);
    }

    public function actionActivate($id)
    {
        $model = $this->loadModel($id);
        $model->updateAttributes(['status' => Channel::STATUS_ACTIVE]);

        Yii::$app->session->setFlash('success', 'Канал включен');
        return $this->redirect(['list']);
    }

    public function actionDeactivate($id)
    {
        $model = $this->loadModel($id);
        $model->updateAttributes(['status' => Channel::STATUS_HIDDEN]);

        Yii::$app->session->setFlash('success', 'Канал отключен');
        return $this->redirect(['list']);
    }

    public function actionGetListJson($q = null)
    {
        if (!Yii::$app->request->isAjax) {
            throw new \yii\web\MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $out = ['results' => ['id' => '', 'text' => '']];

        if (is_null($q)) {
            return $out;
        }

        $out['results'] = array_values(Channel::find()
            ->select(['id', 'text' => 'name'])
            ->where(['like', 'name', $q])
            ->asArray()
            ->all()
        );

        return $out;
    }

    private function loadModel($id)
    {
        if (!$model = Channel::findOne($id)) {
            throw new NotFoundHttpException('Канал не найден');
        }

        return $model;
    }
}
