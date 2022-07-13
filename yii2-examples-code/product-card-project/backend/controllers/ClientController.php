<?php
namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\MethodNotAllowedHttpException;
use backend\models\Client;
use backend\models\Order;
use backend\models\ExportData;
use backend\models\ClientUploadedFile;
use backend\models\ClientUploadedData;
use backend\models\ClientExportModel;
use backend\models\Feature;
use backend\models\FeatureGroup;
use backend\models\LogSSH;
use backend\models\search\ClientSearch;
use backend\models\search\ClientProductSearch;
use backend\models\search\ClientUploadedDataSearch;

/**
 * Class ClientController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ClientController extends Controller
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
            ],
        ];
    }

    protected function performAjaxValidation($model)
    {
        if (Yii::$app->request->isAjax && $model->load(Yii::$app->request->post())) {
            echo json_encode(\yii\bootstrap\ActiveForm::validate($model));
            Yii::$app->end();
        }
    }

    public function actionList()
    {
        $searchModel  = new ClientSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('list', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionCreate()
    {
        $model = new Client();

        $this->performAjaxValidation($model);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Клиент создан!');

            return $this->redirect(['list']);
        }

        return $this->render('create', [
            'model' => $model
        ]);
    }

    public function actionUpdate($id)
    {
        if (!$model = Client::findOne($id)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $this->performAjaxValidation($model);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Клиент обновлен!');

            return $this->redirect(['list']);
        }

        return $this->render('update', [
            'model' => $model
        ]);
    }

    public function actionDelete($id)
    {
        if (!$model = Client::findOne($id)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        if ($model->delete()) {
            Yii::$app->getSession()->setFlash('success', 'Клиент удален.');
        }

        return $this->redirect(['list']);
    }

    public function actionOrders($id)
    {
        if (!$client = Client::findOne($id)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => Order::find()->where(['client_id' => $id]),
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC
                ]
            ]
        ]);

        return $this->render('orders', [
            'client' => $client,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionOrderCreate($clientId)
    {
        if (!$client = Client::findOne($clientId)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $model = new Order();
        $model->client_id = $clientId;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Заказ создан!');

            return $this->redirect(['orders', 'id' => $clientId]);
        }

        return $this->render('order-create', [
            'client' => $client,
            'model'  => $model
        ]);
    }

    public function actionOrderUpdate($id)
    {
        if (!$model = Order::findOne($id)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Заказ обновлен!');

            return $this->redirect(['orders', 'id' => $model->client->id]);
        }

        return $this->render('order-update', [
            'client' => $model->client,
            'model'  => $model
        ]);
    }

    public function actionOrderDelete($id)
    {
        if (!$model = Order::findOne($id)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        if ($model->delete()) {
            Yii::$app->getSession()->setFlash('success', 'Заказ удален.');
        }

        return $this->redirect(['orders', 'id' => $model->client->id]);
    }

    public function actionConfigurate($id)
    {
        if (!$model = Order::findOne($id)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $searchModel = new ClientProductSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('configurate', [
            'model' => $model,
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionGetOrdersJson($id, $q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = Order::find()->select(['id', 'text' => 'name'])->where(['client_id' => $id]);

        if (!is_null($q)) {
            $query->andWhere(['like', 'name', $q]);
        }

        return ['items' => $query->asArray()->all()];
    }

    public function actionGetImJson($id, $q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $query = ClientUploadedFile::find()->select(['id', 'text' => 'filename'])->where(['client_id' => $id]);

        if (!is_null($q)) {
            $query->andWhere(['like', 'filename', $q]);
        }

        return ['items' => $query->asArray()->all()];
    }

    public function actionUploadFiles($id)
    {
        if (!$model = Order::findOne($id)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => LogSSH::find()->where(['order_id' => $id]),
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ]
        ]);

        return $this->render('upload-files', [
            'model' => $model,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionStartUploadFiles($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$model = Order::findOne($id)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        $className = 'app\models\upload\\' . $model->client->ssh_model;
        if (!class_exists($className)) {
            throw new NotFoundHttpException('SSH модель "' . $model->client->ssh_model . '" не найдена');
        }

        $uploadModel = new $className($model->id);
        return ['success' => $uploadModel->run()];
    }

    public function actionInfomodel($clientId)
    {
        if (!$client = Client::findOne($clientId)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $model = new ClientUploadedFile(['client_id' => $clientId]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'ИМ загружена');

            return $this->refresh();
        }

        $uploadedFiles = ClientUploadedFile::getList($clientId);

        return $this->render('infomodel', [
            'client' => $client,
            'model'  => $model,
            'uploadedFiles' => $uploadedFiles
        ]);
    }

    public function actionStartRelate()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $oldApp = Yii::$app;
        new \yii\console\Application([
            'id' => 'basic-console',
            'basePath' => '@app',
            'controllerNamespace' => 'app\commands',
            'components' => [
                'db' => $oldApp->db
            ]
        ]);
        Yii::$app->runAction('import/relate-data', [false]);
        Yii::$app = $oldApp;

        return ['success' => true];
    }

    public function actionInfomodelEdit($id)
    {
        if (!$model = ClientUploadedFile::findOne($id)) {
            throw new NotFoundHttpException('Файл ИМ не найден');
        }

        return $this->render('infomodel-edit', [
            'model'     => $model,
            'statistic' => ClientUploadedData::getShortStatistic($model->client_id, $model->id)
        ]);
    }

    public function actionFeaturesRelateIM($id, $categoryId = null)
    {
        if (!$modelIM = ClientUploadedFile::findOne($id)) {
            throw new NotFoundHttpException('Файл ИМ не найден');
        }

        if (!$client = Client::findOne($modelIM->client_id)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $searchModel = new ClientUploadedDataSearch();
        $dataProvider = $searchModel->searchFeatures(Yii::$app->request->queryParams, $client->id, $id, $categoryId);
        $dataProvider->pagination->pageSize = 100;

        return $this->render('features-relate-i-m', [
            'modelIM' => $modelIM,
            'client'  => $client,
            'currentCategoryId' => $categoryId,
            'categories' => ClientUploadedData::getCategories($id),
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionFeaturesRelateIMUpdate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$model = ClientUploadedData::findOne($id)) {
            return ['error' => 'Запись не найдена'];
        }

        return $model->relateData(Yii::$app->request->post());
    }

    public function actionStartExport($orderId, $exportModelId)
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$order = Order::findOne($orderId)) {
            throw new NotFoundHttpException('Заказ не найден');
        }

        if (!$clientExportModel = ClientExportModel::findOne($exportModelId)) {
            throw new NotFoundHttpException('Модель экспорта не найдена');
        }

        $className = 'app\models\export\\' . $clientExportModel->model;
        if (!class_exists($className)) {
            throw new NotFoundHttpException('Модель экспорта "' . $clientExportModel->model . '" не найдена');
        }

        $exportModel = new $className($orderId, $order->client->id);
        $filename = $exportModel->run();

        $exportData = new ExportData([
            'client_id'       => $order->client->id,
            'order_id'        => $orderId,
            'export_model_id' => $exportModelId,
            'file'            => $filename
        ]);

        if ($exportData->save()) {
            return ['success' => true];
        }

        return ['success' => false];
    }

    public function actionOrdersFinished($clientId)
    {
        if (!Client::findOne($clientId)) {
            throw new NotFoundHttpException('Клиент не найден');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => ExportData::find()->where(['client_id' => $clientId]),
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC
                ]
            ],
        ]);

        return $this->render('orders-finished', [
            'dataProvider' => $dataProvider
        ]);
    }

    public function actionLoadFeatures($q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $out = ['results' => ['id' => '', 'text' => '']];

        if (is_null($q)) {
            return $out;
        }

        $out['results'] = array_values(Feature::listByName($q));

        return $out;
    }

    public function actionLoadFeatureGroups($q = null)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $out = ['results' => ['id' => '', 'text' => '']];

        if (is_null($q)) {
            return $out;
        }

        $out['results'] = array_values(FeatureGroup::listByName($q));

        return $out;
    }

    public function actionFileCategoryUpdate($clientId, $fileId, $categoryName = NULL, $categoryCode = NULL)
    {
        if (Yii::$app->request->post()) {
            if (ClientUploadedData::modifyClientCategory($categoryCode, Yii::$app->request->post()['new_category_id'])) {
                return $this->redirect(['client/categories-relate', 'clientId' => $clientId, 'fileId' => $fileId]);
            }

            throw new NotFoundHttpException('Ошибка во время сохранения');
        }

        if (!$fileModel = ClientUploadedFile::findOne($fileId)) {
            throw new NotFoundHttpException('Файл не найден');
        }

        $clientUploadedDataQuery = ClientUploadedData::find()->where([
            'client_id'               => $fileModel->client_id,
            'client_uploaded_file_id' => $fileId,
            'category_internal_code'  => urldecode($categoryCode)
        ]);

        return $this->render('file-category-update', [
            'clientUploadData' => $clientUploadedDataQuery->one(),
            'file' => $fileModel
        ]);
    }
}
