<?php

namespace backend\controllers;

use Yii;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;
use backend\models\Ad;
use backend\models\BetCianForm;
use backend\models\Listing;
use backend\models\LList;
use backend\models\ListingAd;

/**
 * BetCianController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class BetCianController extends CommonController
{
    public function actionIndex()
    {
        $model = new BetCianForm();
        $model->load(Yii::$app->request->get());
        $stat = $model->statistics();

        if (!Yii::$app->session->has('promo')) {
            Yii::$app->session->set('promo', 'all');
        }

        if (!Yii::$app->session->has('type')) {
            Yii::$app->session->set('type', 'all');
        }

        if (!Yii::$app->session->has('layout')) {
            Yii::$app->session->set('layout', 'all');
        }

        if (!Yii::$app->session->has('tower')) {
            Yii::$app->session->set('tower', 'all');
        }

        $query = Listing::find()
            ->innerJoinWith('object')
            ->innerJoinWith('listingAds')
            ->where([
                'activity' => 1,
                'listing_ad.ad_id' => 1,
            ])
            ->union(Listing::find()
                ->where(['id' => array_keys($model->cianLists)]), true)
        ;
        if (Yii::$app->session->get('promo') !== 'all') {
            $query->andWhere(['&', 'listing_ad.promo', Yii::$app->session->get('promo')]);
        }
        if (Yii::$app->session->get('type') !== 'all') {
            $query->andWhere('listing.listing_type_id = ' . Yii::$app->session->get('type'));
        }
        if (Yii::$app->session->get('layout') !== 'all') {
            $query->andWhere('IF(listing.type_id IS NULL, object.type_id, listing.type_id) = ' . Yii::$app->session->get('layout'));
        }
        if (Yii::$app->session->get('tower') !== 'all') {
            $query->andWhere('object.tower_id = ' . Yii::$app->session->get('tower'));
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (Yii::$app->session->has('pagesize')) {
            if (Yii::$app->session->get('pagesize') > 0) {
                $dataProvider->pagination->pageSize = Yii::$app->session->get('pagesize') ? : 20;
            } else {
                $dataProvider->pagination = false;
            }
        }

        if ($sort = Yii::$app->request->get('sort')) {
            $models = $dataProvider->getModels();
            switch ($sort) {
                case 'name':
                    usort($models, function($first, $second) {
                        return Listing::$type_ru[$first->listing_type_id] > Listing::$type_ru[$second->listing_type_id];
                    });
                    break;

                case '-name':
                    usort($models, function($first, $second) {
                        return Listing::$type_ru[$first->listing_type_id] < Listing::$type_ru[$second->listing_type_id];
                    });
                    break;

                case 'type_id':
                    usort($models, function($first, $second) {
                        $fType = $first->type_id ?: $first->object->type_id;
                        $sType = $second->type_id ?: $second->object->type_id;

                        return $fType > $sType;
                    });
                    break;

                case '-type_id':
                    usort($models, function($first, $second) {
                        $fType = $first->type_id ?: $first->object->type_id;
                        $sType = $second->type_id ?: $second->object->type_id;

                        return $fType < $sType;
                    });
                    break;

                case 'listing_type_id':
                    $towerNames = ArrayHelper::map(\backend\models\Tower::find()->all(), 'id', 'name');
                    usort($models, function($first, $second) use ($towerNames) {
                        return $towerNames[$first->object->tower_id] > $towerNames[$second->object->tower_id];
                    });
                    break;

                case '-listing_type_id':
                    $towerNames = ArrayHelper::map(\backend\models\Tower::find()->all(), 'id', 'name');
                    usort($models, function($first, $second) use ($towerNames) {
                        return $towerNames[$first->object->tower_id] < $towerNames[$second->object->tower_id];
                    });
                    break;

                case 'ak_proc':
                    usort($models, function($first, $second) use ($model, $stat) {
                        $fLead = 0;
                        if (isset($stat['listingLeads'][$first->idd])) {
                            $fLead = $stat['listingLeads'][$first->idd]['lead'];
                        }
                        if (isset($model->cianLists[$first->id]) && isset($stat['listingLeads'][$model->cianLists[$first->id]['list']->id])) {
                            $fLead += $stat['listingLeads'][$model->cianLists[$first->id]['list']->id]['lead'];
                        }

                        $sLead = 0;
                        if (isset($stat['listingLeads'][$second->idd])) {
                            $sLead = $stat['listingLeads'][$second->idd]['lead'];
                        }
                        if (isset($model->cianLists[$second->id]) && isset($stat['listingLeads'][$model->cianLists[$second->id]['list']->id])) {
                            $sLead += $stat['listingLeads'][$model->cianLists[$second->id]['list']->id]['lead'];
                        }

                        return $fLead > $sLead;
                    });
                    break;

                case '-ak_proc':
                    usort($models, function($first, $second) use ($model, $stat) {
                        $fLead = 0;
                        if (isset($stat['listingLeads'][$first->idd])) {
                            $fLead = $stat['listingLeads'][$first->idd]['lead'];
                        }
                        if (isset($model->cianLists[$first->id]) && isset($stat['listingLeads'][$model->cianLists[$first->id]['list']->id])) {
                            $fLead += $stat['listingLeads'][$model->cianLists[$first->id]['list']->id]['lead'];
                        }

                        $sLead = 0;
                        if (isset($stat['listingLeads'][$second->idd])) {
                            $sLead = $stat['listingLeads'][$second->idd]['lead'];
                        }
                        if (isset($model->cianLists[$second->id]) && isset($stat['listingLeads'][$model->cianLists[$second->id]['list']->id])) {
                            $sLead += $stat['listingLeads'][$model->cianLists[$second->id]['list']->id]['lead'];
                        }

                        return $fLead < $sLead;
                    });
                    break;

                case 'ak_rub':
                    usort($models, function($first, $second) use ($model, $stat) {
                        $fSpl = 0;
                        if (isset($stat['listingLeads'][$first->idd])) {
                            $fSpl = $stat['listingLeads'][$first->idd]['lead'];
                        }
                        if (isset($model->cianLists[$first->id]) && isset($stat['listingLeads'][$model->cianLists[$first->id]['list']->id])) {
                            $fSpl += $stat['listingLeads'][$model->cianLists[$first->id]['list']->id]['lead'];
                        }
                        if ($fSpl > 0) {
                            $fSpl = $stat['budget'] / $fSpl;
                        }

                        $sSpl = 0;
                        if (isset($stat['listingLeads'][$second->idd])) {
                            $sSpl = $stat['listingLeads'][$second->idd]['lead'];
                        }
                        if (isset($model->cianLists[$second->id]) && isset($stat['listingLeads'][$model->cianLists[$second->id]['list']->id])) {
                            $sSpl += $stat['listingLeads'][$model->cianLists[$second->id]['list']->id]['lead'];
                        }
                        if ($sSpl > 0) {
                            $sSpl = $stat['budget'] / $sSpl;
                        }

                        return $fSpl > $sSpl;
                    });
                    break;

                case '-ak_rub':
                    usort($models, function($first, $second) use ($model, $stat) {
                        $fSpl = 0;
                        if (isset($stat['listingLeads'][$first->idd])) {
                            $fSpl = $stat['listingLeads'][$first->idd]['lead'];
                        }
                        if (isset($model->cianLists[$first->id]) && isset($stat['listingLeads'][$model->cianLists[$first->id]['list']->id])) {
                            $fSpl += $stat['listingLeads'][$model->cianLists[$first->id]['list']->id]['lead'];
                        }
                        if ($fSpl > 0) {
                            $fSpl = $stat['budget'] / $fSpl;
                        }

                        $sSpl = 0;
                        if (isset($stat['listingLeads'][$second->idd])) {
                            $sSpl = $stat['listingLeads'][$second->idd]['lead'];
                        }
                        if (isset($model->cianLists[$second->id]) && isset($stat['listingLeads'][$model->cianLists[$second->id]['list']->id])) {
                            $sSpl += $stat['listingLeads'][$model->cianLists[$second->id]['list']->id]['lead'];
                        }
                        if ($sSpl > 0) {
                            $sSpl = $stat['budget'] / $sSpl;
                        }

                        return $fSpl < $sSpl;
                    });
                    break;

                case 'area':
                    usort($models, function($first, $second) {
                        $fArea = $first->area ?: $first->object->area;
                        $sArea = $second->area ?: $second->object->area;

                        return $fArea > $sArea;
                    });
                    break;

                case '-area':
                    usort($models, function($first, $second) {
                        $fArea = $first->area ?: $first->object->area;
                        $sArea = $second->area ?: $second->object->area;

                        return $fArea < $sArea;
                    });
                    break;

                case 'object_id':
                    usort($models, function($first, $second) {
                        return $first->object->floor > $second->object->floor;
                    });
                    break;

                case '-object_id':
                    usort($models, function($first, $second) {
                        return $first->object->floor < $second->object->floor;
                    });
                    break;

                case 'cost_m2':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (isset($listingsCian[$first->id])) {
                            $fValue = $listingsCian[$first->id]->cost_m2 ?: $first->cost_m2;
                            $fValue = $first->convertCurrency($first->currency_id, 1, $fValue);
                        } else {
                            $fValue = $model->cianLists[$first->id]['listing']->cost_m2;
                        }
                        if (isset($listingsCian[$second->id])) {
                            $sValue = $listingsCian[$second->id]->cost_m2 ?: $second->cost_m2;
                            $sValue = $second->convertCurrency($second->currency_id, 1, $sValue);
                        } else {
                            $sValue = $model->cianLists[$second->id]['listing']->cost_m2;
                        }

                        return $fValue > $sValue;
                    });
                    break;

                case '-cost_m2':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (isset($listingsCian[$first->id])) {
                            $fValue = $listingsCian[$first->id]->cost_m2 ?: $first->cost_m2;
                            $fValue = $first->convertCurrency($first->currency_id, 1, $fValue);
                        } else {
                            $fValue = $model->cianLists[$first->id]['listing']->cost_m2;
                        }
                        if (isset($listingsCian[$second->id])) {
                            $sValue = $listingsCian[$second->id]->cost_m2 ?: $second->cost_m2;
                            $sValue = $second->convertCurrency($second->currency_id, 1, $sValue);
                        } else {
                            $sValue = $model->cianLists[$second->id]['listing']->cost_m2;
                        }

                        return $fValue < $sValue;
                    });
                    break;

                case 'cost':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (isset($listingsCian[$first->id])) {
                            $fValue = $listingsCian[$first->id]->cost ?: $first->cost;
                            $fValue = $first->convertCurrency($first->currency_id, 1, $fValue);
                        } else {
                            $fValue = $model->cianLists[$first->id]['listing']->cost;
                        }
                        if (isset($listingsCian[$second->id])) {
                            $sValue = $listingsCian[$second->id]->cost ?: $second->cost;
                            $sValue = $second->convertCurrency($second->currency_id, 1, $sValue);
                        } else {
                            $sValue = $model->cianLists[$second->id]['listing']->cost;
                        }

                        return $fValue > $sValue;
                    });
                    break;

                case '-cost':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (isset($listingsCian[$first->id])) {
                            $fValue = $listingsCian[$first->id]->cost ?: $first->cost;
                            $fValue = $first->convertCurrency($first->currency_id, 1, $fValue);
                        } else {
                            $fValue = $model->cianLists[$first->id]['listing']->cost;
                        }
                        if (isset($listingsCian[$second->id])) {
                            $sValue = $listingsCian[$second->id]->cost ?: $second->cost;
                            $sValue = $second->convertCurrency($second->currency_id, 1, $sValue);
                        } else {
                            $sValue = $model->cianLists[$second->id]['listing']->cost;
                        }

                        return $fValue < $sValue;
                    });
                    break;

                case 'bet':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (!in_array($first->id, array_keys($model->cianLists))) {
                            $fValue = $listingsCian[$first->id]->bet ?: $first->bet;
                        } else {
                            $fValue = (int) $model->cianLists[$first->id]['list']->rate;
                        }
                        if (!in_array($second->id, array_keys($model->cianLists))) {
                            $sValue = $listingsCian[$second->id]->bet ?: $second->bet;
                        } else {
                            $sValue = (int) $model->cianLists[$second->id]['list']->rate;
                        }

                        return $fValue > $sValue;
                    });
                    break;

                case '-bet':
                    $listingsCian = ArrayHelper::index(ListingAd::find()->where(['ad_id' => Ad::CIAN])->all(), 'listing_id');
                    usort($models, function($first, $second) use ($listingsCian, $model) {
                        if (!in_array($first->id, array_keys($model->cianLists))) {
                            $fValue = $listingsCian[$first->id]->bet ?: $first->bet;
                        } else {
                            $fValue = (int) $model->cianLists[$first->id]['list']->rate;
                        }
                        if (!in_array($second->id, array_keys($model->cianLists))) {
                            $sValue = $listingsCian[$second->id]->bet ?: $second->bet;
                        } else {
                            $sValue = (int) $model->cianLists[$second->id]['list']->rate;
                        }

                        return $fValue < $sValue;
                    });
                    break;
            }
            $dataProvider->setModels($models);
        }

        return $this->render('index', [
            'model' => $model,
            'stat' => $stat,
            'today' => $model->calcToday(),
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionSetDuration()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$ad = Ad::findOne(Ad::CIAN)) {
            throw new NotFoundHttpException('Запись не найдена');
        }

        $nowDate = new \DateTime('today');;
        $startDate = new \DateTime(Yii::$app->request->post('duration'));
        $duration = 0;
        for ($i = 0; $i < 30; $i++) {
            $duration += ($startDate >= $nowDate && $startDate->format('N') < 6) ? 1 : 0;
            $startDate = $startDate->add(new \DateInterval('P1D'));
        }

        $ad->updateAttributes(['package_duration' => $duration]);

        return (new BetCianForm)->calcToday();
    }

    public function actionSetBalanceType()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $model = Ad::findOne(Ad::CIAN);
        $model->updateAttributes(['balance_type' => Yii::$app->request->post('val')]);

        $form = new BetCianForm();
        $data = $form->calcToday();
        $data['balance'] = number_format($form->balance, 0, '.', ' ');

        return $data;
    }

    public function actionUpdateListBet()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$model = LList::findOne(Yii::$app->request->post('id'))) {
            throw new NotFoundHttpException('Запись не найдена');
        }

        $model->updateAttributes(['rate' => Yii::$app->request->post('bet')]);

        return ['success' => true];
    }

    public function actionUpdateListingBet()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!$model = ListingAd::findOne(['listing_id' => Yii::$app->request->post('id'), 'ad_id' => Ad::CIAN])) {
            throw new NotFoundHttpException('Запись не найдена');
        }

        $model->updateAttributes(['bet' => Yii::$app->request->post('bet')]);

        return ['success' => true];
    }

    public function actionUpdateCosts()
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        if (!isset($post['listing_id'])) {
            return ['success' => false, 'mess' => 'Не передан обязательный параметр'];
        }

        if (!$model = ListingAd::findOne(['listing_id' => $post['listing_id'], 'ad_id' => Ad::CIAN])) {
            $model = new ListingAd([
                'listing_id' => $post['listing_id'],
                'ad_id' => Ad::CIAN,
                'promo' => ListingAd::NO_PROMO
            ]);
            $model->save();
        }

        if (!$listing = Listing::findOne($post['listing_id'])) {
            return ['success' => false, 'mess' => 'Листинг не найден'];
        }

        $model->updateAttributes([
            'cost_m2' => $listing->convertCurrency(1, $listing->currency_id, $post['costM2']),
            'cost'    => $listing->convertCurrency(1, $listing->currency_id, $post['cost']),
        ]);

        return ['success' => true];
    }

    public function actionStat($id)
    {
        if (!Yii::$app->request->isAjax) {
            throw new MethodNotAllowedHttpException('Метод не поддерживается');
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        return (new BetCianForm)->getCianStat($id);
    }

    public function actionGetLeadIds()
    {
        return Yii::$app->response->sendFile(Yii::getAlias('@runtime') . '/lead-ids.txt');
    }

    public function actionGetTargetLeadIds()
    {
        return Yii::$app->response->sendFile(Yii::getAlias('@runtime') . '/target-lead-ids.txt');
    }

    public function actionUpdateBets()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $data = Yii::$app->request->post('data');

        foreach ($data as $row) {
            switch ($row['type']) {
                case 'listing':
                    if ($model = Listing::findOne($row['id'])) {
                        $model->updateAttributes(['bet' => $row['bet']]);
                    }

                    break;

                case 'list':
                    if ($model = LList::findOne($row['id'])) {
                        $model->updateAttributes(['rate' => $row['bet']]);
                    }

                    break;
            }
        }

        return ['success' => true];
    }

    public function actionChangePromo($promo)
    {
        Yii::$app->session->set('promo', $promo);
        return $this->redirect(Yii::$app->request->referrer ?: '/');
    }

    public function actionChangeType($type)
    {
        Yii::$app->session->set('type', $type);
        return $this->redirect(Yii::$app->request->referrer ?: '/');
    }

    public function actionChangeLayout($layout)
    {
        Yii::$app->session->set('layout', $layout);
        return $this->redirect(Yii::$app->request->referrer ?: '/');
    }

    public function actionChangeTower($tower)
    {
        Yii::$app->session->set('tower', $tower);
        return $this->redirect(Yii::$app->request->referrer ?: '/');
    }
}
