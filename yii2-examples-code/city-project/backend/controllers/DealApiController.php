<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\httpclient\Client;
use backend\models\Listing;
use backend\models\AmoDeal;
use backend\models\Type;

/**
 * DealApiController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class DealApiController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionCreate()
    {
        if (!$id = Yii::$app->request->post()['leads']['status'][0]['id']) {
            Yii::error('Не получен ID сделки АМО');
            return false;
        }

        $client = new Client(['baseUrl' => 'https://amo.mcity.ru/api/']);
        $leadResponse = $client->get('lead/' . $id, ['token' => 'hm9r5253-wtku-pyi2-2iyo-lc9he9z47qdb'])->send();

        if (!$leadResponse->isOk) {
            Yii::error('Возникла ошибка при получении данных сделки ID: ' . $id);
            return false;
        }

        $model = new AmoDeal([
            'id' => $id,
            'responsible_user_id' => $leadResponse->data['responsible_user_id']
        ]);

        foreach ($leadResponse->data['custom_fields_values'] as $customField) {
            switch ($customField['field_id']) {
                case '1728560':
                    $model->listing_type_id = array_search(mb_strtolower($customField['values'][0]['value']), Listing::$type_ru);
                    break;

                case '1728546':
                    $model->type_id = array_search($customField['values'][0]['value'], Type::ALL);
                    break;

                case '1975871':
                    $model->area_from = $customField['values'][0]['value'];
                    break;

                case '1975873':
                    $model->area_to = $customField['values'][0]['value'];
                    break;

                case '2287669':
                    $model->send_new_listings = $customField['values'][0]['value'];
                    break;
            }
        }

        if (!$model->save()) {
            Yii::error('Ошибка при сохранении данный сделки ID: ' . $id);
            return false;
        }
    }

    public function actionChange()
    {
        if (!isset(Yii::$app->request->post()['leads'])) {
            echo 'Нет данных по сделке', PHP_EOL;

            Yii::error('Нет данных по сделке');
            return false;
        }

        $leadData = Yii::$app->request->post()['leads']['update'][0];

        if (!$model = AmoDeal::findOne(['id' => $leadData['id']])) {
            $model = new AmoDeal(['id' => $leadData['id']]);
        }
        $model->responsible_user_id = $leadData['responsible_user_id'];

        foreach ($leadData['custom_fields'] as $customField) {
            switch ($customField['id']) {
                case '1728560':
                    $model->listing_type_id = array_search(mb_strtolower($customField['values'][0]['value']), Listing::$type_ru);
                    break;

                case '1728546':
                    $model->type_id = array_search($customField['values'][0]['value'], Type::ALL);
                    break;

                case '1975871':
                    $model->area_from = $customField['values'][0]['value'];
                    break;

                case '1975873':
                    $model->area_to = $customField['values'][0]['value'];
                    break;

                case '2287669':
                    $model->send_new_listings = $customField['values'][0]['value'];
                    break;

                case '2285691':
                    $model->prez_link = $customField['values'][0]['value'];
                    break;
            }
        }

        if (!$model->save()) {
            Yii::error('Ошибка при сохранении данных сделки ID: ' . $id);
            return false;
        }
    }
}
