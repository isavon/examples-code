<?php

namespace backend\models;

use yii\base\Model;
use common\models\Cash;
use common\models\Prognosis;

/**
 * Class Statistic
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class Statistic extends Model
{
    public static function forBalance($period = false)
    {
        $query = Cash::find()->where(['status' => Cash::STATUS_CONFIRMED]);
        $prognosisQuery = Prognosis::find();
        
        if ($period) {
            preg_match('~([\d]{2}/[\d]{2}/[\d]{4})\s-\s([\d]{2}/[\d]{2}/[\d]{4})~is', $period, $match);
            
            if (isset($match[1]) && isset($match[2])) {
                $query->andWhere('created_at >= ' . strtotime($match[1] . ' 00:00:00'));
                $query->andWhere('created_at <= ' . strtotime($match[2] . ' 23:59:59'));
                
                $prognosisQuery->andWhere('created_at >= ' . strtotime($match[1] . ' 00:00:00'));
                $prognosisQuery->andWhere('created_at <= ' . strtotime($match[2] . ' 23:59:59'));
            }
        }
        
        $data = [
            'general' => 0,
            'cashin' => 0,
            'cashout' => 0,
            'buyed' => 0,
            'sales' => 0,
            'profit' => 0,
            'count' => 0,
            'cashinStat' => [],
            'cashoutStat' => [],
            'buyedStat' => [],
            'salesStat' => [],
        ];
        
        $cash = $query->all();
        foreach ($cash as $row) {
            switch ($row->type) {
                case Cash::TYPE_CASHIN:
                    $data['cashin'] += $row->value;
                    $data['cashinStat'][] = [
                        'id' => $row->id,
                        'id_user' => $row->id_user_get,
                        'username' => \common\models\User::findOne($row->id_user_get)->username,
                        'sum' => $row->value,
                        'date' => date('Y-m-d H:i:s', $row->created_at)
                    ];
                break;
                
                case Cash::TYPE_CASHOUT:
                    $data['cashout'] += $row->value;
                    $data['cashoutStat'][] = [
                        'id' => $row->id,
                        'id_user' => $row->id_user_send,
                        'username' => \common\models\User::findOne($row->id_user_send)->username,
                        'sum' => $row->value,
                        'date' => date('Y-m-d H:i:s', $row->created_at)
                    ];
                break;
                
                case Cash::TYPE_TRANSFER:
                    if ($row['id_user_get'] == -1) {
                        $data['buyed'] += $row->value;
                        $data['count']++;
                        $data['buyedStat'][] = [
                            'id' => $row->id,
                            'id_user' => $row->id_user_send,
                            'username' => \common\models\User::findOne($row->id_user_send)->username,
                            'sum' => $row->value,
                            'date' => date('Y-m-d H:i:s', $row->created_at)
                        ];
                    } 
                    
                    if ($row['id_user_send'] == -1) {
                        $data['sales'] += $row->value;
                        $data['salesStat'][] = [
                            'id' => $row->id,
                            'id_user' => $row->id_user_get,
                            'username' => \common\models\User::findOne($row->id_user_get)->username,
                            'sum' => $row->value,
                            'date' => date('Y-m-d H:i:s', $row->created_at)
                        ];
                    }
                break;
            }
        }
        $data['profit'] = $data['buyed'] - $data['sales'];
        $data['general'] = $data['cashin'] - $data['cashout'] - $data['profit'];
        
        foreach ($data as $key => &$row) {
            if ($key == 'count' || is_array($row)) {
                continue;
            }
            
            $row = number_format($row, 2, '.', false);
        }
        
        $data['prognosis'] = $prognosisQuery->count();
        $data['period'] = $period;
        
        return $data;
    }
    
    public static function balanceByUser($userId)
    {
        $query = Cash::find()->where(['status' => Cash::STATUS_CONFIRMED])->andWhere('id_user_send = ' . $userId . ' OR id_user_get = ' . $userId);
        
        $data = [
            'cashin' => 0,
            'cashout' => 0,
            'buyed' => 0,
            'sales' => 0,
            'cashinStat' => [],
            'cashoutStat' => [],
            'buyedStat' => [],
            'salesStat' => []
        ];
        
        $cash = $query->all();
        foreach ($cash as $row) {
            switch ($row->type) {
                case Cash::TYPE_CASHIN:
                    $data['cashin'] += $row->value;
                    $data['cashinStat'][] = [
                        'id' => $row->id,
                        'sum' => $row->value,
                        'date' => date('Y-m-d H:i:s', $row->created_at)
                    ];
                break;
                
                case Cash::TYPE_CASHOUT:
                    $data['cashout'] += $row->value;
                    $data['cashoutStat'][] = [
                        'id' => $row->id,
                        'sum' => $row->value,
                        'date' => date('Y-m-d H:i:s', $row->created_at)
                    ];
                break;
                
                case Cash::TYPE_TRANSFER:
                    if ($row['id_user_get'] == -1) {
                        $data['buyed'] += $row->value;
                        
                        $buyed = Cash::find()->where(['created_at' => $row->created_at, 'id_user_send' => -1])->one();
                        
                        $data['buyedStat'][] = [
                            'id' => $row->id,
                            'id_user' => $buyed->id_user_get,
                            'username' => \common\models\User::findOne($buyed->id_user_get)->username,
                            'sum' => $row->value,
                            'date' => date('Y-m-d H:i:s', $row->created_at)
                        ];
                    } 
                    
                    if ($row['id_user_send'] == -1) {
                        $data['sales'] += $row->value;
                        
                        $sales = Cash::find()->where(['created_at' => $row->created_at, 'id_user_get' => -1])->one();
                        
                        $data['salesStat'][] = [
                            'id' => $row->id,
                            'id_user' => $sales->id_user_send,
                            'username' => \common\models\User::findOne($sales->id_user_send)->username,
                            'sum' => $row->value,
                            'date' => date('Y-m-d H:i:s', $row->created_at)
                        ];
                    }
                break;
            }
        }
        
        foreach ($data as $key => &$row) {
            if (!is_array($row)) {
                $row = number_format($row, 2, '.', false);
            }
        }
        
        return $data;
    }
    
    public static function commonByUser($userId)
    {          
        $query = Prognosis::find()->where(['id_user' => $userId])->andWhere('profit IS NOT NULL')->orderBy('datetime ASC');
        
        $statistic = [
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
            'average_coef' => 0
        ];
        
        $allPrognosis = $query->asArray()->all();
        $drawdownCurrent = 0;
        foreach ($allPrognosis as $key => $prognosis) {
            $statistic['profit']   += $prognosis['profit'];
            $statistic['rev']      += $prognosis['rate'];
            $statistic['coef']     += $prognosis['coefficient'];
            
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
        
        if ($statistic['rev'] <> 0) {
            $statistic['rol'] = number_format($statistic['profit'] * 100 / $statistic['rev'], 2, '.', false);
        }
        
        return $statistic;
    }
}
