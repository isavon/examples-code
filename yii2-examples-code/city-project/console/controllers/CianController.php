<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\httpclient\Client;
use backend\models\CianStat;
use backend\models\LList;
use backend\models\Tower;
use backend\models\CianOffer;
use backend\models\Listing;

/**
 * CianController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class CianController extends Controller
{
    const URL = 'https://public-api.cian.ru/v1';
    const TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VySWQiOjc1NzI1Mjl9.8QXo5Ws4xHP0x_xQaid-3wNPaPlWji8eT_gHkOZeH-Y';

    public function actionIndex()
    {
        $client = new Client(['baseUrl' => self::URL]);
        $headers = ['Authorization' => 'Bearer ' . self::TOKEN];
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo = date('Y-m-d');

        $orders = $client->get('get-order', null, $headers)->send();
        if ($orders->isOk) {
            $orders = json_decode($orders->content);
        }

        foreach ($orders->result->offers as $offer) {
            $model = new CianStat([
                'idd' => $offer->externalId,
                'created_at' => strtotime($dateFrom),
            ]);

            $statCoverage = $client->get('get-search-coverage', [
                'offerId' => $offer->offerId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ], $headers)->send();

            if ($statCoverage->isOk) {
                $statCoverage = json_decode($statCoverage->content);
                $model->coverage = $statCoverage->result->coverage;
                $model->searches_count = $statCoverage->result->searchesCount;
                $model->shows_count = $statCoverage->result->showsCount;
            }

            $statPhone = $client->get('get-views-statistics-by-days', [
                'offerId' => $offer->offerId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ], $headers)->send();

            if ($statPhone->isOk) {
                $statPhone = json_decode($statPhone->content);
                $model->phone_shows = $statPhone->result->phoneShowsByDays[0]->phoneShows;
            }

            $model->save();
        }

        echo 'DONE!', PHP_EOL;
    }

    public function actionUpdateLinks()
    {
        $client = new Client(['baseUrl' => Yii::$app->params['cianApiUrl']]);

        $orders = $client->get('get-order', null, ['Authorization' => 'Bearer ' . Yii::$app->params['cianApiToken']])->send();
        if (!$orders->isOk) {
            return false;
        }

        $orders = json_decode($orders->content);
        foreach ($orders->result->offers as $offer) {
            if (!$model = LList::findOne($offer->externalId)) {
                continue;
            }

            $model->updateAttributes(['link' => $offer->url]);
        }

        echo 'DONE!', PHP_EOL;
    }

    public function actionPullOffers()
    {
        $towers = Tower::find()->select(['id', 'complex_id', 'cian_address'])->where(['is not', 'cian_address', null])->asArray()->all();

        foreach ($towers as $tower) {
            $offers = Yii::$app->dbCian->createCommand('
                    SELECT
                        o.*,
                        u.name competitor,
                        (SELECT hp.services FROM history_promo hp WHERE hp.id = o.id ORDER BY UNIX_TIMESTAMP(hp.date) DESC LIMIT 1) promo
                    FROM offers o
                    LEFT JOIN users u ON u.id = o.cianUserId
                    WHERE o.address_string = :address
                        AND o.category IN("office", "shoppingArea", "flat")
                        AND o.cianUserId != 9383110
                ')
                ->bindValue(':address', $tower['cian_address'])
                ->queryAll();

            foreach ($offers as $offer) {
                if (!$model = CianOffer::findOne($offer['id'])) {
                    $model = new CianOffer(['id' => $offer['id']]);
                }

                $model->tower_id = $tower['id'];
                $model->complex_id = $tower['complex_id'];
                $model->type = CianOffer::$types[$offer['category']];
                $model->listing_type = CianOffer::$listingType[$offer['dealType']];
                $model->floor = $offer['floorNumber'];
                $model->area = $offer['totalArea'];
                $model->price = $offer['priceRur'];
                $model->competitor = $offer['competitor'];
                $model->bet = $offer['auction_currentBet'];
                $model->promo = $offer['promo'];
                $model->updated_at = strtotime($offer['updated_at']);

                $model->save();
            }

            echo 'Tower ID: ', $tower['id'], ' done', PHP_EOL;
        }

        $listingsModel = Listing::find()
            ->innerJoin('object o', 'o.id = listing.object_id')
            ->innerJoin('tower t', 't.id = o.tower_id')
            ->innerJoin('cian_offer co', 'co.complex_id = t.complex_id')
            ->where([
                'listing.activity' => 1,
                'listing.is_in_basket' => 0,
                'co.status' => 'tracked',
                'send_amo_competitor_price_low' => false,
            ])
            ->andWhere('co.updated_at >= ' . strtotime('-24 hours', time()))
            ->andWhere('listing.listing_type_id = co.listing_type')
            ->andWhere('o.floor = co.floor')
            ->andWhere('')
            ->andWhere('IF(listing.type_id, listing.type_id, o.type_id) = co.type')
            ->andWhere('co.area >= IF (listing.area, listing.area, o.area) * .9')
            ->andWhere('co.area <= IF (listing.area, listing.area, o.area) * 1.1')
            ->andWhere('co.price < listing.cost')
            ->groupBy('listing.id')
            ->all();

        foreach ($listingsModel as $listingModel) {
            $listingModel->sendAmoTask('У конкурента цена ниже - связаться актуализировать цену');
            $listingModel->updateAttributes(['send_amo_competitor_price_low' => true]);
        }

        echo 'ALL DONE', PHP_EOL;
    }
}