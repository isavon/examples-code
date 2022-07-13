<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\httpclient\Client;
use backend\models\Ad;

/**
 * BetCianForm
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class BetCianForm extends Model
{
    public $dateFrom;
    public $dateTo;
    public $cianApiClient;
    public $cianApiHeaders;

    private $_budget = null;
    private $_lists = [];
    
    const CIAN_CATEGORY = [
        2 => 'office',
        3 => 'shoppingArea',
        1 => 'apartments',
    ];
    const CIAN_DEAL_TYPE = [
        1 => 'rent',
        2 => 'sale',
    ];

    public function rules()
    {
        return [
            [['dateFrom', 'dateTo'], 'safe']
        ];
    }

    public function init()
    {
        if (!$this->dateFrom) {
            $this->dateFrom = date('d.m.Y', strtotime('-1 month'));
        }

        if (!$this->dateTo) {
            $this->dateTo = date('d.m.Y');
        }

        $this->cianApiClient = new Client(['baseUrl' => Yii::$app->params['cianApiUrl']]);
        $this->cianApiHeaders = ['Authorization' => 'Bearer ' . Yii::$app->params['cianApiToken']];

        parent::init();
    }

    public function statistics()
    {
        $data = [
            'budget' => $this->budget,
            'balance' => $this->balance,
            'lead' => 0,
            'targetLead' => 0,
            'listingLeads' => [],
            'cpl' => 0,
            'cplTarget' => 0,
        ];

        $idsForLead = [];
        $idsForTargetLead = [];

        $amoApiClient = new Client(['baseUrl' => Yii::$app->params['amoHost']]);
        for ($i = 1; $i <= 80; $i++) {
            $amoResponse = $amoApiClient->get('api/v4/leads', [
                'USER_HASH' => Yii::$app->params['USER_HASH'],
                'USER_LOGIN' => Yii::$app->params['USER_LOGIN'],
                'page' => $i,
                'limit' => 250,
                'filter' => [
                    'pipeline_id' => 409170,
                    'created_at' => [
                        'from' => strtotime($this->dateFrom . ' 00:00:00'),
                        'to' => strtotime($this->dateTo . '23:59:59'),
                    ],

                ],
            ])->send();

            if (!$amoResponse->isOk || !isset($amoResponse->data['_embedded']['leads']) || count($amoResponse->data['_embedded']['leads']) == 0) {
                break;
            }

            $keys = [];
            foreach ($amoResponse->data['_embedded']['leads'] as $key => $row) {
                if (!isset($row['custom_fields_values'])) {
                    continue;
                }

                foreach ($row['custom_fields_values'] as $fields) {
                    if ($fields['field_name'] !== 'roistat') {
                        continue;
                    }

                    if (in_array(mb_strtolower($fields['values'][0]['value']), ['циан общая', 'циан сборное'])) {
                        $data['lead']++;
                        $keys[] = $key;
                        $idsForLead[] = $row['id'];

                        if ($row['price'] > 0) {
                            $data['targetLead']++;
                            $idsForTargetLead[] = $row['id'];
                        }

                        break;
                    }
                }
            }

            foreach ($keys as $key) {
                if (!isset($amoResponse->data['_embedded']['leads'][$key]['custom_fields_values'])) {
                    continue;
                }

                foreach ($amoResponse->data['_embedded']['leads'][$key]['custom_fields_values'] as $fields) {
                    if ($fields['field_id'] !== 1904182) {
                        continue;
                    }

                    $id = $fields['values'][0]['value'];
                    if (!isset($data['listingLeads'][$id])) {
                        $data['listingLeads'][$id] = [
                            'lead' => 0,
                            'targetLead' => 0,
                        ];
                    }

                    $data['listingLeads'][$id]['lead']++;

                    if ($amoResponse->data['_embedded']['leads'][$key]['price'] > 0) {
                        $data['listingLeads'][$id]['targetLead']++;
                    }
                }
            }
        }
        file_put_contents(Yii::getAlias('@runtime') . '/lead-ids.txt', implode(', ', $idsForLead));
        file_put_contents(Yii::getAlias('@runtime') . '/target-lead-ids.txt', implode(', ', $idsForTargetLead));

        if ($data['lead'] > 0) {
            $data['cpl'] = $data['budget'] / $data['lead'];
        }

        if ($data['targetLead'] > 0) {
            $data['cplTarget'] = $data['budget'] / $data['targetLead'];
        }

        return $data;
    }

    public function calcToday()
    {
        $model = Ad::findOne(Ad::CIAN);

        $plan = $model->package_duration > 0 ? $this->balance / $model->package_duration : 0;

        $betListings = Yii::$app->db->createCommand('
            SELECT SUM(IF(la.bet, la.bet, l.bet))
            FROM listing_ad la
            JOIN listing l ON l.id = la.listing_id AND l.activity = 1
            WHERE la.ad_id = ' . Ad::CIAN)->queryScalar();
        $betList = Yii::$app->db->createCommand('
            SELECT SUM(l.rate) FROM `list` l WHERE l.type = "' . LList::TYPE_CIAN . '"
        ')->queryScalar();
        $bet = $betListings + $betList;

        $data = [
            'duration' => $model->package_duration,
            'plan' => number_format($plan, 0, '.', ' '),
            'fact' => number_format($bet, 0, '.', ' '),
            'rest' => number_format($plan - $bet, 0, '.', ' '),
        ];

        return $data;
    }

    public function getBudget()
    {
        if (!$this->_budget) {
            for ($i = 1; $i <= 25; $i++) {
                $budget = $this->cianApiClient->get('get-operations', [
                    'from' => date('c', strtotime($this->dateFrom)),
                    'to' => date('c', strtotime($this->dateTo)),
                    'operationType' => 'withdrawal',
                    'page' => $i,
                    'pageSize' => 500,
                ], $this->cianApiHeaders)->send();
                if (!$budget->isOk) {
                    break;
                }

                $budget = json_decode($budget->content);

                if (count($budget->result->operations) == 0) {
                    break;
                }

                foreach ($budget->result->operations as $row) {
                    if ($row->logObjectType == 'user') {
                        $this->_budget += $row->amount;
                    }
                }
            }
        }

        return $this->_budget;
    }

    public function getAd()
    {
        return Ad::findOne(Ad::CIAN);
    }

    public function getBalance()
    {
        $balance = 0;
        $response = $this->cianApiClient->get('get-my-balance', null, $this->cianApiHeaders)->send();
        if ($response->isOk) {
            $response = json_decode($response->content);

            if (isset($response->result->auctionPoints[0])) {
                $balance = $response->result->auctionPoints[0]->amount;
            }

            if ($this->ad->balance_type == Ad::CIAN_BALANCE_TYPE_TOTAL && isset($response->result->totalBalance)) {
                $balance += $response->result->totalBalance;
            }
        }

        return $balance;
    }

    public function getMaxListingIds()
    {
        return Yii::$app->db->createCommand('
            SELECT listing.id
            FROM list_listing
            JOIN listing ON listing.id = list_listing.listing_id,
                (SELECT ll.list_id, MAX(l.area) `area`
                 FROM `list`
                 JOIN list_listing ll ON ll.list_id = list.id
                 JOIN listing l ON l.id = ll.listing_id
                 WHERE list.type = "cian"
                 GROUP BY ll.list_id
                ) tmp
            WHERE list_listing.list_id = tmp.list_id AND listing.area = tmp.area
        ')->queryColumn();
    }

    public function getCianLists()
    {
        if (!count($this->_lists)) {
            $query = LList::find()->where(['type' => LList::TYPE_CIAN]);

            if (Yii::$app->session->get('promo') !== 'all') {
                $query->andWhere(['allocation' => LList::$adAllocations[Yii::$app->session->get('promo')]]);
            }

            $lists = $query->all();

            foreach ($lists as $list) {
                if ($maxListing = $list->listingWithMaxArea) {
                    $this->_lists[$maxListing->id] = [
                        'listing' => $maxListing,
                        'list' => $list,
                    ];
                }
            }

            foreach ($this->_lists as $key => $list) {
                if (Yii::$app->session->get('type') !== 'all' && $list['listing']->listing_type_id !== (int) Yii::$app->session->get('type')) {
                    unset($this->_lists[$key]);
                }

                if (Yii::$app->session->get('layout') !== 'all' && $list['listing']->type1 !== (int) Yii::$app->session->get('layout')) {
                    unset($this->_lists[$key]);
                }

                if (Yii::$app->session->get('tower') !== 'all' && $list['listing']->object->tower_id !== (int) Yii::$app->session->get('tower')) {
                    unset($this->_lists[$key]);
                }
            }
        }

        return $this->_lists;
    }

    public function getCianLink($id, $type)
    {
        $orders = $this->cianApiClient->get('get-order', null, $this->cianApiHeaders)->send();
        if (!$orders->isOk) {
            return false;
        }

        $orders = json_decode($orders->content);
        foreach ($orders->result->offers as $offer) {
            if ((int) $offer->externalId == (int) $id) {
                if ($type == 'list') {
                    $model = LList::findOne($id);
                } else {
                    $listing = Listing::findOne(['idd' => $id]);
                    $model = ListingAd::findOne(['listing_id' => $listing->id, 'ad_id' => Ad::CIAN]);
                }

                if ($model) {
                    $model->updateAttributes(['link' => $offer->url]);
                }

                return $offer->url;
            }
        }

        return false;
    }

    public function getCianStat($id)
    {
        $data = [
            'showsCount' => 0,
            'shows' => 0,
            'views' => 0,
            'posCity' => 'Не определено',
            'posTower' => 'Не определено',
            'posFloor' => 'Не определено',
        ];

        $orders = $this->cianApiClient->get('get-order', null, $this->cianApiHeaders)->send();
        if (!$orders->isOk) {
            return false;
        }

        $offerId = null;
        $orders = json_decode($orders->content);
        foreach ($orders->result->offers as $order) {
            if ((int) $order->externalId == (int) $id) {
                $offerId = $order->offerId;
            }
        }

        if (is_null($offerId)) {
            return false;
        }

        $coverageResponse = $this->cianApiClient->get('get-search-coverage', [
            'offerId' => $offerId,
            'dateFrom' => date('Y-m-d', strtotime('-179 day')),
            'dateTo' => date('Y-m-d', strtotime($this->dateTo)),
        ], $this->cianApiHeaders)->send();
        if ($coverageResponse->isOk) {
            $coverageData = json_decode($coverageResponse->content);
            $data['showsCount'] = $coverageData->result->showsCount;
        }

        $viewsResponse = $this->cianApiClient->get('get-views-statistics', [
            'offersIds' => $offerId,
        ], $this->cianApiHeaders)->send();
        if ($viewsResponse->isOk) {
            $viewsData = json_decode($viewsResponse->content);
            $data['shows'] = $viewsData->result->statistics[0]->phoneShows;
            $data['views'] = $viewsData->result->statistics[0]->totalViews;
        }

        if ($listing = Listing::findOne(['idd' => $id])) {
            $filtersData = Yii::$app->dbCian->createCommand('
                SELECT *
                FROM search_sources
                WHERE category = :category
                      AND dealType = :dealType
            ')
            ->bindValue(':category', self::CIAN_CATEGORY[$listing->type1])
            ->bindValue(':dealType', self::CIAN_DEAL_TYPE[$listing->listing_type_id])
            ->queryAll();
            
            foreach ($filtersData as $filterData) {
                $params = json_decode($filterData['params']);

            }
        }

        return $data;
    }

    public function getLastProcessDate()
    {
        $info = $this->cianApiClient->get('get-last-order-info', null, $this->cianApiHeaders)->send();
        if (!$info->isOk) {
            return false;
        }

        return date('d.m.Y H:i', strtotime(json_decode($info->content)->result->lastProcessDate));
    }
}