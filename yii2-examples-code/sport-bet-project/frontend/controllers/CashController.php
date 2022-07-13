<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use frontend\models\forms\CashoutForm;
use frontend\models\forms\CashinForm;

/**
 * Class CashController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class CashController extends Controller
{
    use \frontend\extensions\ControllerTrait;
    
    public function behaviors()
    {
        
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['in', 'out', 'buy', 'validate'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['interaction', 'success', 'fail'],
                        'allow' => true,
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'out' => ['post'],
                    'in' => ['post'],
                    'buy' => ['post'],
                    'success' => ['post'],
                    'interaction' => ['post'],
                    'fail' => ['post'],
                ],
            ],
        ];
    }
    
    public function beforeAction($action) 
    {
        if (in_array($action->id, ['interaction', 'success', 'fail'])) {
            $this->enableCsrfValidation = false;
        }
        
        return parent::beforeAction($action);
    }
    
    public function actionValidate()
    {
        if (!Yii::$app->request->isAjax) {
            throw new \yii\web\HttpException(404);
        }
        
        $this->performAjaxValidation(new CashoutForm());
        $this->performAjaxValidation(new CashinForm());
    }
    
    public function actionOut()
    {
        $referrer = Yii::$app->request->referrer ? Yii::$app->request->referrer : 'index';
        $model = new CashoutForm();
        
        if ($model->load(Yii::$app->request->post())) {
            $result = $model->transaction();
            
            Yii::$app->getSession()->setFlash($result['type'], $result['mess']);
        }
        
        $this->redirect($referrer);
    }
    
    public function actionIn()
    {
        $referrer = Yii::$app->request->referrer ? Yii::$app->request->referrer : 'index';
        $model = new CashinForm();
        $errorMess = 'Возникла ошибка во время проведения оплаты! Обратитесь к администрации сайта.';
        
        if ($model->load(Yii::$app->request->post())) {
            $result = $model->transaction();
            
            $course = \common\models\Setting::findOne(['key' => 'cource'])->value;
            
            if ($result['type'] == 'success') {
                return $this->redirect('https://sci.interkassa.com/?ik_co_id=' . Yii::$app->params['interkassaHash'] . '&ik_pm_no=' . $model->id . '&ik_am='.round($model->amount * $course, 2).'&ik_cur=UAH&ik_desc=Пополнение баланса');
            }
            
            $errorMess = $result['mess'];
        }
        
        Yii::$app->getSession()->setFlash('error', $errorMess);
        $this->redirect($referrer);
    }
    
    public function actionSuccess()
    {
        Yii::$app->getSession()->setFlash('success', 'Баланс обновлен.');
        return $this->goHome();
    }
    
    public function actionInteraction()
    {
        if (!isset($_REQUEST['ik_pm_no'])) {
            file_put_contents('interkassa_error_'.date('d-m-Y H:i:s').'.txt', 'Некорректные данные!');
            return;
        }
        
        if (!$model = \common\models\Cash::find()->where(['id' => $_REQUEST['ik_pm_no'], 'status' => \common\models\Cash::STATUS_NOT_CONFIRMED])->one()) {
            file_put_contents('interkassa_error_'.date('d-m-Y H:i:s').'.txt', 'Запись транзакции не найдена! ID ' . $_REQUEST['ik_pm_no']);
            return;
        }
        
        $result = $model->cashinConfirmation();
        file_put_contents('interkassa_success_'.date('d-m-Y H:i:s').'.txt', $result['mess']);
    }
    
    public function actionFail()
    {
        Yii::$app->getSession()->setFlash('error', 'Транзакция потерпела неудачу.');
        return $this->goHome();
    }
    
    public function actionBuy()
    {
        if (!Yii::$app->request->isAjax) {
            throw new \yii\web\HttpException(404);
        }
        
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $result = \frontend\models\Transfer::buyPrognosis();
        
        echo json_encode([$result['type'] => $result['mess']]);
        Yii::$app->end();
    }
}