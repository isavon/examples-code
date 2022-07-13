<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use backend\models\Contact;

/**
 * ContactApiController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ContactApiController extends Controller
{
    public function beforeAction($action)
    {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionCreate()
    {
        Yii::info(Yii::$app->request->post());

        $enity = Yii::$app->request->post()['contacts']['add'][0];

        if (!$contact = Contact::findOne(['id' => $enity['id'], 'type' => 4])) {
            $contact = new Contact([
                'id'   => intval($enity['id']),
                'type' => 4,
            ]);
        }

        $contact->name       = $enity['name'];
        $contact->created_at = $enity['date_create'];

        if (isset($enity['custom_fields'])) {
            foreach ($enity['custom_fields'] as $customField) {
                if ($customField['name'] !== 'Мотивация') {
                    continue;
                }

                if (isset(Contact::$motivations[$customField['values'][0]['value']])) {
                    $contact->motivation = Contact::$motivations[$customField['values'][0]['value']];
                }
            }
        }

        $contact->save();
    }

    public function actionUpdate()
    {
        $enity = Yii::$app->request->post()['contacts']['update'][0];
        $contact = $this->loadModel($enity['id']);
        $contact->updateAttributes([
            'name' => $enity['name'],
            'created_at' => $enity['date_create'],
        ]);

        foreach ($enity['custom_fields'] as $customField) {
            if ($customField['name'] !== 'Мотивация') {
                continue;
            }

            if (isset(Contact::$motivations[$customField['values'][0]['value']])) {
                $contact->updateAttributes(['motivation' => Contact::$motivations[$customField['values'][0]['value']]]);
            }
        }
    }

    public function actionDelete()
    {
        Yii::info(Yii::$app->request->post());

        $enity = Yii::$app->request->post()['contacts']['delete'][0];
        $this->loadModel($enity['id'])->delete();
    }

    private function loadModel($id)
    {
        if (!$model = Contact::findOne(['id' => $id, 'type' => 4])) {
            throw new NotFoundHttpException('Контакт не найден');
        }

        return $model;
    }
}
