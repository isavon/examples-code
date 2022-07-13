<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use common\models\Cash;
use common\models\Prognosis;

/**
 * Class Statistic
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class Statistic extends Model
{
    const PERIOD_30  = 30;
    const PERIOD_100 = 100;
    const PERIOD_300 = 300;
    const PERIOD_ALL = 'all';
    
    public static $periods = [
        self::PERIOD_30  => 'Последние 30 дней',
        self::PERIOD_100 => 'Последние 100 дней',
        self::PERIOD_300 => 'Последние 300 дней', 
        self::PERIOD_ALL => 'За все время',
    ];
    
    public static function findByUser($id, $period = self::PERIOD_30)
    {
        if (!in_array($period, array_keys(self::$periods))) {
            $period = self::PERIOD_30;
        }
        
        $fromDate = false;
        if ($period !== self::PERIOD_ALL) {
            $fromDate = strtotime('-' . $period . ' days');
        }
               
        $query = Prognosis::find()->where(['id_user' => $id])->andWhere('profit IS NOT NULL')->orderBy('datetime ASC');
        if ($fromDate) {
            $query->andWhere('datetime >= ' . $fromDate);
        }
        
        $statistic = [
            'period'       => $period,
            'profit'       => 0,
            'prognosis'    => $query->count(),
            'prognosisWinning' => 0,
            'prognosisReturn'  => 0,
            'prognosisLoss'    => 0,
            'rev'          => 0,
            'rol'          => 0,
            'drawdown'     => 0,
            'coef'         => 0,
            'average_rate' => 0,
            'average_coef' => 0,
            'chart'        => []
        ];
        $statistic['chart'][]  = '[0, 0]';
        
        $allPrognosis = $query->asArray()->all();
        $drawdownCurrent = 0;
        foreach ($allPrognosis as $key => $prognosis) {
            $statistic['profit']   += $prognosis['profit'];
            $statistic['rev']      += $prognosis['rate'];
            $statistic['coef']     += $prognosis['coefficient'];
            $statistic['chart'][]  = '[' . ($key + 1) . ', ' . $statistic['profit'] . ']';
            
            if ($prognosis['profit'] > 0 && $drawdownCurrent !== 0) {
                $drawdownCurrent += $prognosis['profit'];
                
                if ($drawdownCurrent > 0) {
                    $drawdownCurrent = 0;
                }
            }
            
            if ($prognosis['profit'] < 0) {
                $drawdownCurrent += $prognosis['profit'];
                
                if ($statistic['drawdown'] > $drawdownCurrent) {
                    $statistic['drawdown'] = $drawdownCurrent;
                }
            }
            
            switch ($prognosis['calculation']) {
                case Prognosis::CALCULATION_WINNING:
                case Prognosis::CALCULATION_HALF_WINNING:
                    $statistic['prognosisWinning']++;
                    break;
                
                case Prognosis::CALCULATION_RETURN:
                case Prognosis::CALCULATION_CANCEL:
                    $statistic['prognosisReturn']++;
                    break;
                
                case Prognosis::CALCULATION_LOSS:
                case Prognosis::CALCULATION_HALF_LOSS:
                    $statistic['prognosisLoss']++;
                    break;
            }
        }
        
        if ($statistic['prognosis'] > 0) {
            $statistic['average_rate'] = number_format($statistic['rev'] / $statistic['prognosis'], 2, '.', false);
            $statistic['average_coef'] = number_format($statistic['coef'] / $statistic['prognosis'], 2, '.', false);
        }
                
        $statistic['profit']   = number_format($statistic['profit'], 2, '.', false);
        $statistic['rev']      = number_format($statistic['rev'], 2, '.', false);
        $statistic['drawdown'] = number_format($statistic['drawdown'], 2, '.', false);
        $statistic['chart'] = '[' . implode($statistic['chart'], ',') . ']';
        
        if ($statistic['rev'] <> 0) {
            $statistic['rol'] = number_format($statistic['profit'] * 100 / $statistic['rev'], 2, '.', false);
        }
        
        $dataProviderQuery = Prognosis::find()->where(['id_user' => $id])->andWhere('profit IS NOT NULL');
        if ($fromDate) {
            $dataProviderQuery->andWhere('datetime >= ' . $fromDate);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $dataProviderQuery,
            'pagination' => [
                'pagesize' => 100,
            ],
            'sort' => [
                'defaultOrder' => [
                    'datetime' => SORT_DESC
                ]
            ]
        ]);
        
        return [
            'dataProvider' => $dataProvider,
            'data' => $statistic
        ];
    }
    
    public static function findForForecaster($period = self::PERIOD_30) 
    {
        if (!in_array($period, array_keys(self::$periods))) {
            $period = self::PERIOD_30;
        }
        
        $fromDate = false;
        if ($period !== self::PERIOD_ALL) {
            $fromDate = strtotime('-' . $period . ' days');
        }
        
        $query = (new Query())
            ->select([
                'u.id', 
                'u.username',
                'p.profit',
                'p.rate',
                'p.coefficient'
            ])
            ->from('user u')
            ->leftJoin('prognosis p', 'p.id_user = u.id')
            ->where(['u.status' => \common\models\User::STATUS_ACTIVE])
            ->andWhere('p.profit IS NOT NULL')
            ->orderBy('p.datetime ASC')
        ;
        
        if ($fromDate) {
            $query->andWhere('p.datetime >= ' . $fromDate);
        }
        
        $data = [];
        $userIds = [];
        $rows = $query->all();
        foreach ($rows as $row) {
            if (!isset($data[$row['id']])) {
                $data[$row['id']] = [
                    'id'           => $row['id'],
                    'username'     => $row['username'],
                    'waiting'      => 0,
                    'profit'       => 0,
                    'prognosis'    => 0,
                    'rev'          => 0,
                    'rol'          => 0,
                    'drawdown'     => 0,
                    'coef'         => 0,
                    'average_rate' => 0,
                    'average_coef' => 0,
                    'drawdown'     => 0,
                    'drawdownCurrent' => 0
                ];
            }
            
            $data[$row['id']]['profit'] += $row['profit'];
            $data[$row['id']]['prognosis']++;
            $data[$row['id']]['rev'] += $row['rate'];
            $data[$row['id']]['coef'] += $row['coefficient'];
            
            if ($row['profit'] > 0 && $data[$row['id']]['drawdownCurrent'] !== 0) {
                $data[$row['id']]['drawdownCurrent'] += $row['profit'];
                
                if ($data[$row['id']]['drawdownCurrent'] > 0) {
                    $data[$row['id']]['drawdownCurrent'] = 0;
                }
            }
            
            if ($row['profit'] < 0) {
                $data[$row['id']]['drawdownCurrent'] += $row['profit'];
                
                if ($data[$row['id']]['drawdown'] > $data[$row['id']]['drawdownCurrent']) {
                    $data[$row['id']]['drawdown'] = $data[$row['id']]['drawdownCurrent'];
                }
            }
            
            $userIds[] = $row['id'];
        }
        
        $dataWaiting = [];
        if (count($userIds)) {
            $queryWaiting = (new Query())
                ->select([
                    'COUNT(id) count', 
                    'id_user id'
                ])
                ->from('prognosis')
                ->where('id_user IN (' . implode($userIds, ',') . ')')
                ->andWhere('datetime >= ' . time())
                ->groupBy('id_user')
            ;
            $dataWaiting = \yii\helpers\ArrayHelper::index($queryWaiting->all(), 'id');
        }
        
        foreach ($data as $id => &$row) {
            if ($row['prognosis'] > 0) {
                $row['average_rate'] = number_format($row['rev'] / $row['prognosis'], 2, '.', false);
                $row['average_coef'] = number_format($row['coef'] / $row['prognosis'], 2, '.', false);
            }
            
            $row['profit']   = number_format($row['profit'], 2, '.', false);
            $row['rev']      = number_format($row['rev'], 2, '.', false);
            $row['drawdown'] = number_format($row['drawdown'], 2, '.', false);
            
            if ($row['rev'] <> 0) {
                $row['rol'] = number_format($row['profit'] * 100 / $row['rev'], 2, '.', false);
            }
            
            if (isset($dataWaiting[$id])) {
                $row['waiting'] = $dataWaiting[$id]['count'];
            }
            
            if (intval($row['prognosis']) < Yii::$app->params['minPrognosis']) {
                unset($data[$id]);
            }
        }
                
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort'  => [
                'attributes' => ['profit', 'rol', 'drawdown'],
                'defaultOrder' => [
                    'profit'   => SORT_DESC,
                    'rol'      => SORT_DESC,
                    'drawdown' => SORT_DESC
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ]
        ]);
        
        return [
            'period' => $period,
            'dataProvider' => $dataProvider
        ];
    }
    
    public static function balanceByUser($userId)
    {
        $query = Cash::find()->where(['status' => Cash::STATUS_CONFIRMED])->andWhere('id_user_send = ' . $userId . ' OR id_user_get = ' . $userId);
        
        $data = [
            'cashin' => 0,
            'cashout' => 0,
            'buyed' => 0,
            'sales' => 0
        ];
        
        $cash = $query->all();
        foreach ($cash as $row) {
            switch ($row->type) {
                case Cash::TYPE_CASHIN:
                    $data['cashin'] += $row->value;
                break;
                
                case Cash::TYPE_CASHOUT:
                    $data['cashout'] += $row->value;
                break;
                
                case Cash::TYPE_TRANSFER:
                    if ($row['id_user_get'] == -1) {
                        $data['buyed'] += $row->value;
                    } 
                    
                    if ($row['id_user_send'] == -1) {
                        $data['sales'] += $row->value;
                    }
                break;
            }
        }
        
        foreach ($data as $key => &$row) {
            $row = number_format($row, 2, '.', false);
        }
        
        return $data;
    }
    
    public function findForTop()
    {
        $query = (new Query())
            ->select([
                'u.id', 
                'u.username',
                'p.profit',
                'p.rate',
                'p.coefficient'
            ])
            ->from('user u')
            ->leftJoin('prognosis p', 'p.id_user = u.id')
            ->where(['u.status' => \common\models\User::STATUS_ACTIVE])
            ->andWhere('p.profit IS NOT NULL')
            ->andWhere('p.datetime >= ' . strtotime('-30 days'))
            ->orderBy('p.datetime ASC')
        ;
        
        $data = [];
        $userIds = [];
        $rows = $query->all();
        foreach ($rows as $row) {
            if (!isset($data[$row['id']])) {
                $data[$row['id']] = [
                    'id'        => $row['id'],
                    'username'  => $row['username'],
                    'waiting'   => 0,
                    'profit'    => 0,
                    'prognosis' => 0
                ];
            }
            
            $data[$row['id']]['profit'] += $row['profit'];
            $data[$row['id']]['prognosis']++;
            $userIds[] = $row['id'];
        }
        
        $dataWaiting = [];
        if (count($userIds)) {
            $queryWaiting = (new Query())
                ->select([
                    'COUNT(id) count', 
                    'id_user id'
                ])
                ->from('prognosis')
                ->where('id_user IN (' . implode($userIds, ',') . ')')
                ->andWhere('datetime >= ' . time())
                ->groupBy('id_user')
            ;
            $dataWaiting = \yii\helpers\ArrayHelper::index($queryWaiting->all(), 'id');
        }
        
        foreach ($data as $id => &$row) {
            $row['profit']   = number_format($row['profit'], 2, '.', false);
            
            if (isset($dataWaiting[$id])) {
                $row['waiting'] = $dataWaiting[$id]['count'];
            }
            
            if (intval($row['prognosis']) < Yii::$app->params['minPrognosis']) {
                unset($data[$id]);
            }
        }        
        
        usort($data, function ($item1, $item2) {
            if ($item1['profit'] == $item2['profit']) return 0;
            return $item1['profit'] > $item2['profit'] ? -1 : 1;
        });
        
        $data = array_slice($data, 0, Yii::$app->params['topLength']);
        
        return $data;
    }
}