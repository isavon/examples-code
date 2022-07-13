<?php

namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use yii\data\ActiveDataProvider;
use common\models\User;
use common\models\Prognosis;
use common\models\Comment;
use common\models\Subscribe;
use frontend\models\Statistic;

/**
 * Class ProfileController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ProfileController extends Controller
{
    use \frontend\extensions\ControllerTrait;
    
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['view'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ]
        ];
    }
    
    public function actionIndex()
    {
        $modelComment = new Comment();
        $modelComment->id_user_from = $modelComment->id_user_to = Yii::$app->user->id;
        
        if ($modelComment->load(Yii::$app->request->post()) && $modelComment->save()) {
            $modelComment = new Comment();
        }
        
        $dataProviderWaiting = new ActiveDataProvider([
            'query' => Prognosis::find()->where(['id_user' => Yii::$app->user->getIdentity()->id])->andWhere('datetime >= ' . time()),
            'pagination' => [
                'pagesize' => Yii::$app->params['profileWaitingPerPage'],
            ],
            'sort' => [
                'defaultOrder' => [
                    'datetime' => SORT_ASC
                ]
            ]
        ]);
        
        $dataProviderClosed = new ActiveDataProvider([
            'query' => Prognosis::find()->where(['id_user' => Yii::$app->user->id])->andWhere('datetime < ' . time())->andWhere('profit IS NULL'),
            'pagination' => [
                'pagesize' => Yii::$app->params['profileClosedPerPage'],
            ],
            'sort' => [
                'defaultOrder' => [
                    'datetime' => SORT_DESC
                ]
            ]
        ]);
        
        $commentCurPage = Yii::$app->request->get('comm-page') ? (int) Yii::$app->request->get('comm-page') : 0;
        $commentLimit = $commentCurPage * Yii::$app->params['pagesizeComment'] + Yii::$app->params['pagesizeComment'];
        $commentQuery = (new \yii\db\Query)
            ->select([
                'c.id',
                'c.id_user_from',
                'c.text',
                'c.created_at',
                'u.username'
            ])
            ->from('comment c')
            ->innerJoin('user u', 'u.id = c.id_user_from')
            ->where(['c.id_user_to' => Yii::$app->user->id]);
        ;
        $commentList = $commentQuery
            ->orderBy('created_at DESC')
            ->limit($commentLimit)
            ->all()
        ;
        
        return $this->render('index', [
            'count' => Prognosis::find()->where(['id_user' => Yii::$app->user->getIdentity()->id])->andWhere('profit IS NULL')->count(),
            'countWaiting' => Prognosis::find()->where(['id_user' => Yii::$app->user->getIdentity()->id])->andWhere('datetime >= ' . time())->count(),
            'dataProviderWaiting' => $dataProviderWaiting,
            'dataProviderClosed'  => $dataProviderClosed,
            'statistic' => Statistic::findByUser(Yii::$app->user->id, Yii::$app->request->get('period')),
            'subscribers' => Subscribe::findByUserTo(Yii::$app->user->id),
            'comment' => [
                'model'   => $modelComment,
                'list'    => $commentList,
                'curPage' => $commentCurPage,
                'more'    => (int) $commentQuery->count() > $commentLimit
            ]
        ]);
    }
    
    public function actionView($id)
    {
        if (!$user = User::findOne(['id' => $id, 'status' => User::STATUS_ACTIVE])) {
            throw new \yii\web\NotFoundHttpException();
        }
        
        $modelComment = new Comment();
        $modelComment->id_user_from = Yii::$app->user->id;
        $modelComment->id_user_to = $user->id;
        
        if ($modelComment->load(Yii::$app->request->post()) && $modelComment->save()) {
            $modelComment = new Comment();
        }
        
        $dataProviderWaiting = new ActiveDataProvider([
            'query' => Prognosis::find()->where(['id_user' => $user->id])->andWhere('datetime >= ' . time()),
            'pagination' => [
                'pagesize' => Yii::$app->params['profileWaitingPerPage'],
            ],
            'sort' => [
                'defaultOrder' => [
                    'datetime' => SORT_ASC
                ]
            ]
        ]);
        
        $dataProviderClosed = new ActiveDataProvider([
            'query' => Prognosis::find()->where(['id_user' => $user->id])->andWhere('datetime < ' . time())->andWhere('profit IS NULL'),
            'pagination' => [
                'pagesize' => Yii::$app->params['profileClosedPerPage'],
            ],
            'sort' => [
                'defaultOrder' => [
                    'datetime' => SORT_DESC
                ]
            ]
        ]);
        
        $commentCurPage = Yii::$app->request->get('comm-page') ? (int) Yii::$app->request->get('comm-page') : 0;
        
        $commentLimit = $commentCurPage * Yii::$app->params['pagesizeComment'] + Yii::$app->params['pagesizeComment'];
        $commentQuery = (new \yii\db\Query)
            ->select([
                'c.id',
                'c.id_user_from',
                'c.text',
                'c.created_at',
                'u.username'
            ])
            ->from('comment c')
            ->innerJoin('user u', 'u.id = c.id_user_from')
            ->where(['c.id_user_to' => $user->id]);
        ;
        $commentList = $commentQuery
            ->orderBy('created_at DESC')
            ->limit($commentLimit)
            ->all()
        ;
        
        return $this->render('view', [
            'user' => $user,
            'count' => Prognosis::find()->where(['id_user' => $user->id])->andWhere('profit IS NULL')->count(),
            'countWaiting' => Prognosis::find()->where(['id_user' => $user->id])->andWhere('datetime >= ' . time())->count(),
            'dataProviderWaiting' => $dataProviderWaiting,
            'dataProviderClosed'  => $dataProviderClosed,
            'statistic' => Statistic::findByUser($user->id, Yii::$app->request->get('period')),
            'subscribers' => Subscribe::findByUserTo($user->id),
            'comment' => [
                'model'   => $modelComment,
                'list'    => $commentList,
                'curPage' => $commentCurPage,
                'more'    => (int) $commentQuery->count() > $commentLimit
            ]
        ]);
    }
}
