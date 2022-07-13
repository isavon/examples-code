<?php
namespace backend\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use common\models\User;
use backend\models\BObject;
use backend\models\Listing;
use backend\models\Contact;

/**
 * DisposalDashboardForm
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class DisposalDashboardForm extends Model
{
    public $dateFrom;
    public $dateTo;
    public $userId = null;

    private $_users     = null;
    private $_disposals = null;

    public function rules()
    {
        return [
            [['dateFrom', 'dateTo', 'userId'], 'safe'],
        ];
    }

    public function init()
    {
        if (!$this->dateFrom) {
            $this->dateFrom = date('Y-m-d', strtotime('-1 month'));
        }

        if (!$this->dateTo) {
            $this->dateTo = date('Y-m-d');
        }

        parent::init();
    }

    public function attributeLabels()
    {
        return [
            'userId' => 'Диспозл'
        ];
    }

    public function search()
    {
        return [
            'w1' => $this->getUnique(),
            'w2' => $this->getActiveObjects(),
            'w3' => $this->getActiveListings(),
            'w4' => $this->getNewObjectsListings(),
            'w5' => $this->getUpdatedListings(),
            'w6' => $this->getActivity(),
            'w7' => $this->getPhotos(),
            'w8' => $this->getFineListings(),
            'w9' => $this->getRevisionListings()
        ];
    }

    public function getUnique()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'ducs.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'ducs.created_at >= ' . strtotime($this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $where[] = 'ducs.created_at <= ' . strtotime($this->dateTo . ' 23:59:59');
        }

        $data = Yii::$app->db->createCommand('
            SELECT
                ducs.user_id,
                u.name,
                ROUND(AVG(ducs.total)) total,
                ROUND(AVG(ducs.low)) low,
                ROUND(AVG(ducs.middle)) middle,
                ROUND(AVG(ducs.high)) high,
                ROUND(AVG(ducs.not_set)) not_set
            FROM disposl_unique_company_stat ducs
            INNER JOIN user u ON u.id = ducs.user_id
            ' . (count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '') . '
            GROUP BY ducs.user_id
            ORDER BY total DESC
        ')->queryAll();

        return $data;
    }

    public function getFineListings()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'o.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'UNIX_TIMESTAMP(llj.created_at) >= ' . strtotime($this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $where[] = 'UNIX_TIMESTAMP(llj.created_at) <= ' . strtotime($this->dateTo . ' 23:59:59');
        }

        $data = Yii::$app->db->createCommand('
            SELECT o.user_id, COUNT(o.id) listings FROM (
                SELECT l.id, l.object_id
                FROM listing_ad la
                JOIN listing l ON l.id = la.listing_id
                WHERE la.ad_id = 1 AND la.promo IS NOT NULL
                UNION
                SELECT l.id, l.object_id
                FROM `list`
                JOIN list_listing ll ON ll.list_id = list.id
                JOIN listing l ON l.id = ll.listing_id
                WHERE list.type = "cian"
            ) tmp
            JOIN object o ON o.id = tmp.object_id
            WHERE o.user_id ' . ($this->userId ? ' = ' . $this->userId : 'IN (' . implode(',', array_keys($this->disposals)) . ')') . '
            GROUP BY o.user_id
        ')->queryAll();

        foreach ($data as &$row) {
            $sends = Yii::$app->db->createCommand('
                SELECT COUNT(l.id) sends
                FROM listing_ad la
                INNER JOIN listing l ON l.id = la.listing_id AND l.activity = 1
                INNER JOIN object o ON o.id = l.object_id
                INNER JOIN list_listing_journal llj ON llj.listing_id = l.id
                INNER JOIN `list` lt ON lt.id = llj.list_id AND lt.user_id IN (SELECT aa.user_id FROM auth_assignment aa WHERE aa.item_name = "broker")
                WHERE la.ad_id = 1 AND o.user_id = ' . $row['user_id'] . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '')
            )->queryScalar();

            $row['name'] = $this->disposals[$row['user_id']];
            $row['sends'] = $sends;
        }

        return $data;
    }

    public function getActiveObjects()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'daos.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'daos.created_at >= ' . strtotime($this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $where[] = 'daos.created_at <= ' . strtotime($this->dateTo . ' 23:59:59');
        }

        $data = Yii::$app->db->createCommand('
            SELECT
                daos.user_id,
                u.name,
                ROUND(AVG(daos.total)) total,
                ROUND(AVG(daos.low)) low,
                ROUND(AVG(daos.middle)) middle,
                ROUND(AVG(daos.high)) high,
                ROUND(AVG(daos.not_set)) not_set,
                AVG(daos.area_total) area_total,
                AVG(daos.area_low) area_low,
                AVG(daos.area_middle) area_middle,
                AVG(daos.area_high) area_high,
                AVG(daos.area_not_set) area_not_set
            FROM disposl_active_object_stat daos
            INNER JOIN `user` u ON u.id = daos.user_id
            ' . (count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '') . '
            GROUP BY daos.user_id
            ORDER BY total DESC
        ')->queryAll();

        return $data;
    }

    public function getActiveListings()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'dals.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'dals.created_at >= ' . strtotime($this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $where[] = 'dals.created_at <= ' . strtotime($this->dateTo . ' 23:59:59');
        }

        $data = Yii::$app->db->createCommand('
            SELECT
                dals.user_id,
                u.name,
                ROUND(AVG(dals.total)) total,
                ROUND(AVG(dals.low)) low,
                ROUND(AVG(dals.middle)) middle,
                ROUND(AVG(dals.high)) high,
                ROUND(AVG(dals.not_set)) not_set,
                AVG(dals.area_total) area_total,
                AVG(dals.area_low) area_low,
                AVG(dals.area_middle) area_middle,
                AVG(dals.area_high) area_high,
                AVG(dals.area_not_set) area_not_set
            FROM disposl_active_listing_stat dals
            INNER JOIN `user` u ON u.id = dals.user_id
            ' . (count($where) > 0 ? ' WHERE ' . implode(' AND ', $where) : '') . '
            GROUP BY dals.user_id
            ORDER BY total DESC
        ')->queryAll();

        return $data;
    }

    public function getNewObjectsListings()
    {
        $query = (new Query)->from(['o' => BObject::tableName()])
            ->where(['in', 'o.user_id', array_keys($this->disposals)])
            ->groupBy('o.user_id');

        if ($this->userId) {
            $query->where(['o.user_id' => $this->userId]);
        }

        $objectsQuery = (clone $query);
        $objectsQuery->select(['o.user_id', 'objects' => 'COUNT(o.id)', 'objects_m2' => 'SUM(o.area)'])
            ->orderBy(['objects' => SORT_DESC]);

        $listinsQuery = (clone $query);
        $listinsQuery->select(['o.user_id', 'listings' => 'COUNT(l.id)', 'listings_m2' => 'SUM(l.area)'])
            ->leftJoin(['l' => Listing::tableName()], 'l.object_id = o.id');

        if ($this->dateFrom) {
            $dateFrom = strtotime($this->dateFrom);
            $objectsQuery->andWhere(['>=', 'o.created_at', $dateFrom]);
            $listinsQuery->andWhere(['>=', 'l.created_at', $dateFrom]);
        }

        if ($this->dateTo) {
            $dateTo = strtotime($this->dateTo);
            $objectsQuery->andWhere(['<=', 'o.created_at', $dateTo]);
            $listinsQuery->andWhere(['<=', 'l.created_at', $dateTo]);
        }

        $objects = $objectsQuery->indexBy('user_id')->all();
        $listins = $listinsQuery->indexBy('user_id')->all();

        foreach ($objects as &$row) {
            $user = $this->getUser($row['user_id']);
            $listing = isset($listins[$row['user_id']]) ? $listins[$row['user_id']] : [];

            $row['username'] = $user['name'];
            $row['listings'] = !empty($listing['listings']) ? $listing['listings'] : 0;
            $row['listings_m2'] = !empty($listing['listings_m2']) ? $listing['listings_m2'] : 0;
        }

        return $objects;
    }

    public function getUpdatedListings()
    {
        $listinsQuery = (new Query)
            ->select(['o.user_id', 'u.name', 'listings' => 'COUNT(l.id)'])
            ->from(['o' => BObject::tableName()])
            ->leftJoin(['l' => Listing::tableName()], 'l.object_id = o.id')
            ->innerJoin(['u' => User::tableName()], 'u.id = o.user_id')
            ->where(['in', 'o.user_id', array_keys($this->disposals)])
            ->groupBy('o.user_id')
            ->orderBy(['listings' => SORT_DESC]);

        if ($this->userId) {
            $listinsQuery->where(['o.user_id' => $this->userId]);
        }

        if ($this->dateFrom) {
            $dateFrom = strtotime($this->dateFrom);
            $listinsQuery->andWhere(['>=', 'l.updated_at', $dateFrom]);
        }

        if ($this->dateTo) {
            $dateTo = strtotime($this->dateTo);
            $listinsQuery->andWhere(['<=', 'l.updated_at', $dateTo]);
        }

        return $listinsQuery->indexBy('user_id')->all();
    }

    public function getActivity()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'h.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'UNIX_TIMESTAMP(h.created_at) >= ' . strtotime($this->dateFrom);
        }

        if ($this->dateTo) {
            $where[] = 'UNIX_TIMESTAMP(h.created_at) <= ' . strtotime($this->dateTo);
        }

        $costDown = Yii::$app->db->createCommand('
            SELECT h.user_id, u.name, COUNT(DISTINCT h.entity_id) cost_changes
            FROM history h
            INNER JOIN listing l ON l.id = h.entity_id
            INNER JOIN `user` u ON u.id = h.user_id
            WHERE h.changes LIKE "%cost%" AND h.user_id IN (' . implode(',', array_keys($this->disposals)) . ') AND l.cost < CAST(IF(LEFT(h.changes, 1)="\"", json_extract(REPLACE(REPLACE(SUBSTRING(h.changes, 2, LENGTH(h.changes)-2), "\\\\\\\\\\\\\"", ""), "\\\\\"", "\""), "$.cost"), json_extract(h.changes, "$.cost")) AS DECIMAL(10,2))
            ' . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '') . '
            GROUP BY h.user_id
            ORDER BY cost_changes DESC
        ')->queryAll();

        $bargainSet = Yii::$app->db->createCommand('
            SELECT h.user_id, COUNT(DISTINCT h.entity_id) bargaining_set
            FROM history h
            INNER JOIN listing l ON l.id = h.entity_id
            WHERE h.changes LIKE "%bargaining_before%" AND h.user_id IN (' . implode(',', array_keys($this->disposals)) . ') AND l.bargaining_before != 0 AND IF(LEFT(h.changes, 1)="\"", json_extract(REPLACE(REPLACE(SUBSTRING(h.changes, 2, LENGTH(h.changes)-2), "\\\\\\\\\\\\\"", ""), "\\\\\"", "\""), "$.bargaining_before"), json_extract(h.changes, "$.bargaining_before")) != l.bargaining_before
            ' . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '') . '
            GROUP BY h.user_id
            ORDER BY bargaining_set DESC
        ')->queryAll();
        $bargainSet = ArrayHelper::map($bargainSet, 'user_id', 'bargaining_set');

        $bargainDown = Yii::$app->db->createCommand('
            SELECT h.user_id, COUNT(DISTINCT h.entity_id) bargain_changes
            FROM history h
            INNER JOIN listing l ON l.id = h.entity_id
            WHERE h.changes LIKE "%bargaining_before%" AND h.user_id IN (' . implode(',', array_keys($this->disposals)) . ') AND l.bargaining_before < CAST(IF(LEFT(h.changes, 1)="\"", json_extract(REPLACE(REPLACE(SUBSTRING(h.changes, 2, LENGTH(h.changes)-2), "\\\\\\\\\\\\\"", ""), "\\\\\"", "\""), "$.bargaining_before"), json_extract(h.changes, "$.bargaining_before")) AS DECIMAL(10,2))
            ' . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '') . '
            GROUP BY h.user_id
            ORDER BY bargain_changes DESC
        ')->queryAll();
        $bargainDown = ArrayHelper::map($bargainDown, 'user_id', 'bargain_changes');

        $contractSet = Yii::$app->db->createCommand('
            SELECT h.user_id, COUNT(DISTINCT h.entity_id) contract_set
            FROM history h
            WHERE h.changes LIKE "%contract_sign%" AND h.user_id IN (' . implode(',', array_keys($this->disposals)) . ') AND IF(LEFT(h.changes, 1)="\"", json_extract(REPLACE(REPLACE(SUBSTRING(h.changes, 2, LENGTH(h.changes)-2), "\\\\\\\\\\\\\"", ""), "\\\\\"", "\""), "$.contract_sign"), json_extract(h.changes, "$.contract_sign")) = 0
            ' . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '') . '
            GROUP BY h.user_id
            ORDER BY contract_set DESC
        ')->queryAll();
        $contractSet = ArrayHelper::map($contractSet, 'user_id', 'contract_set');

        foreach ($costDown as &$row) {
            $row['bargaining_set']  = isset($bargainSet[$row['user_id']])  ? $bargainSet[$row['user_id']]  : 0;
            $row['bargain_changes'] = isset($bargainDown[$row['user_id']]) ? $bargainDown[$row['user_id']] : 0;
            $row['contract_set']    = isset($contractSet[$row['user_id']]) ? $contractSet[$row['user_id']] : 0;
        }

        return $costDown;
    }

    public function getPhotos()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'o.user_id = ' . $this->userId;
        }

        if ($this->dateFrom) {
            $where[] = 'gi.created_at >= ' . strtotime($this->dateFrom . ' 00:00:00');
        }

        if ($this->dateTo) {
            $where[] = 'gi.created_at <= ' . strtotime($this->dateTo . ' 23:59:59');
        }

        $photos = Yii::$app->db->createCommand('
            SELECT
                o.user_id,
                u.name,
                gi.description,
                COUNT(DISTINCT gi.ownerid) photos
            FROM gallery_image gi
                INNER JOIN listing l ON l.id = gi.ownerid
                INNER JOIN object o ON o.id = l.object_id
                INNER JOIN `user` u ON u.id = o.user_id
            WHERE gi.description IN(8,9) AND o.user_id IN (' . implode(',', array_keys($this->disposals)) . ')
            ' . (count($where) > 0 ? ' AND ' . implode(' AND ', $where) : '') . '
            GROUP BY o.user_id, gi.description
            ORDER BY photos DESC
        ')->queryAll();

        $data = [];
        foreach ($photos as $row) {
            $data[$row['user_id']] = [
                'name' => $row['name'],
                'photos' => 0,
                'plans' => 0,
            ];

            switch (intval($row['description'])) {
                case 8:
                    $data[$row['user_id']]['photos'] = $row['photos'];
                    break;
                case 9:
                    $data[$row['user_id']]['plans']  = $row['photos'];
                    break;
            }
        }

        return $data;
    }

    public function getRevisionListings()
    {
        $where = [];

        if ($this->userId) {
            $where[] = 'o.user_id = ' . $this->userId;
        }

        $data = Yii::$app->db->createCommand('
            SELECT
                o.user_id,
                u.name,
                COUNT(l.id) listings
            FROM listing l
            INNER JOIN object o ON o.id = l.object_id
            INNER JOIN `user` u ON u.id = o.user_id
            WHERE l.to_revision IS NOT NULL
            GROUP BY o.user_id
            ORDER BY listings DESC
        ')->queryAll();

        return $data;
    }

    public function getDisposals()
    {
        if (!$this->_disposals) {
            $disposals = User::find()
                ->select(['id', 'name'])
                ->where(['in', 'id', Yii::$app->authManager->getUserIdsByRole('disposl')])
                ->asArray()
                ->all();

            $this->_disposals = ArrayHelper::map($disposals, 'id', 'name');
        }

        return $this->_disposals;
    }

    private function getUser($id)
    {
        if (!$this->_users) {
            $this->_users = User::find()->indexBy('id')->asArray()->all();
        }

        return isset($this->_users[$id]) ? $this->_users[$id] : [];
    }
}
