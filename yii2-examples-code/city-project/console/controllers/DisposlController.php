<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\httpclient\Client;
use backend\models\Contact;
use backend\models\DisposlUniquCompanyStat;
use backend\models\DisposlActiveObjectStat;
use backend\models\DisposlActiveListingStat;
use backend\models\DisposlFineListingStat;

/**
 * DisposlController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class DisposlController extends Controller
{
    public function actionUniqueComponies()
    {
        $this->updateContacts();

        $disposlIds = Yii::$app->authManager->getUserIdsByRole('disposl');

        $data = Yii::$app->db->createCommand('
            SELECT
                tmp.user_id,
                COUNT(tmp.contact) total,
                SUM(IF (tmp.motivation = ' . Contact::MOTIVATION_LOW . ', 1, 0)) low,
                SUM(IF (tmp.motivation = ' . Contact::MOTIVATION_MIDDLE . ', 1, 0)) middle,
                SUM(IF (tmp.motivation = ' . Contact::MOTIVATION_HIGH . ', 1, 0)) high,
                SUM(IF (tmp.motivation IS NULL, 1, 0)) not_set
            FROM (
                SELECT
                    o.contact,
                    o.user_id,
                    c.motivation
                FROM object o
                INNER JOIN contact c ON c.id = o.contact
                WHERE c.type = 4 AND o.user_id IN (' . implode(',', $disposlIds) . ')
                GROUP BY o.contact, o.user_id
            ) tmp
            GROUP BY tmp.user_id
            ORDER BY total DESC
        ')->queryAll();

        foreach ($data as $row) {
            $model = new DisposlUniquCompanyStat([
                'user_id' => $row['user_id'],
                'total' => $row['total'],
                'low' => $row['low'],
                'middle' => $row['middle'],
                'high' => $row['high'],
                'not_set' => $row['not_set'],
                'created_at' => time()
            ]);
            $model->save();
        }
    }

    public function actionActiveObjects()
    {
        $this->updateContacts();

        $disposlIds = Yii::$app->authManager->getUserIdsByRole('disposl');

        $data = Yii::$app->db->createCommand('
            SELECT
                tmp.user_id,
                COUNT(tmp.contact) total,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_LOW . ', 1, 0)) low,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_MIDDLE . ', 1, 0)) middle,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_HIGH . ', 1, 0)) high,
                SUM(IF(tmp.motivation IS NULL, 1, 0)) not_set,
                SUM(tmp.area) area_total,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_LOW . ', tmp.area, 0)) area_low,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_MIDDLE . ', tmp.area, 0)) area_middle,
                SUM(IF(tmp.motivation = ' . Contact::MOTIVATION_HIGH . ', tmp.area, 0)) area_high,
                SUM(IF(tmp.motivation IS NULL, tmp.area, 0)) area_not_set
            FROM (
                SELECT
                    o.contact,
                    o.user_id,
                    o.area,
                    c.motivation
                FROM object o
                INNER JOIN listing l ON l.object_id = o.id
                INNER JOIN contact c ON c.id = o.contact AND c.type = 4
                WHERE l.activity = 1 AND o.user_id IN (' . implode(',', $disposlIds) . ')
                GROUP BY o.id
            ) tmp
            GROUP BY tmp.user_id
            ORDER BY total DESC
        ')->queryAll();

        foreach ($data as $row) {
            $model = new DisposlActiveObjectStat([
                'user_id' => $row['user_id'],
                'total' => $row['total'],
                'low' => $row['low'],
                'middle' => $row['middle'],
                'high' => $row['high'],
                'not_set' => $row['not_set'],
                'area_total' => $row['area_total'],
                'area_low' => $row['area_low'],
                'area_middle' => $row['area_middle'],
                'area_high' => $row['area_high'],
                'area_not_set' => $row['area_not_set'],
                'created_at' => time(),
            ]);
            $model->save();
        }
    }

    public function actionActiveListings()
    {
        $this->updateContacts();

        $disposlIds = Yii::$app->authManager->getUserIdsByRole('disposl');

        $data = Yii::$app->db->createCommand('
            SELECT
                o.user_id,
                COUNT(l.id) total,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_LOW . ', 1, 0)) low,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_MIDDLE . ', 1, 0)) middle,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_HIGH . ', 1, 0)) high,
                SUM(IF(c.motivation IS NULL, 1, 0)) not_set,
                SUM(l.area) area_total,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_LOW . ', l.area, 0)) area_low,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_MIDDLE . ', l.area, 0)) area_middle,
                SUM(IF(c.motivation = ' . Contact::MOTIVATION_HIGH . ', l.area, 0)) area_high,
                SUM(IF(c.motivation IS NULL, l.area, 0)) area_not_set
            FROM object o
            INNER JOIN listing l ON l.object_id = o.id
            LEFT JOIN contact c ON c.id = o.contact AND c.type = 4
            WHERE l.activity = 1 AND o.user_id IN (' . implode(',', $disposlIds) . ')
            GROUP BY o.user_id
            ORDER BY total DESC
        ')->queryAll();

        foreach ($data as $row) {
            $model = new DisposlActiveListingStat([
                'user_id' => $row['user_id'],
                'total' => $row['total'],
                'low' => $row['low'],
                'middle' => $row['middle'],
                'high' => $row['high'],
                'not_set' => $row['not_set'],
                'area_total' => $row['area_total'],
                'area_low' => $row['area_low'],
                'area_middle' => $row['area_middle'],
                'area_high' => $row['area_high'],
                'area_not_set' => $row['area_not_set'],
                'created_at' => time(),
            ]);
            $model->save();
        }
    }

    public function actionFineListings()
    {
        $disposlIds = Yii::$app->authManager->getUserIdsByRole('disposl');

        $data = Yii::$app->db->createCommand('
            SELECT o.user_id, COUNT(o.id) listings
            FROM listing_ad la
            INNER JOIN listing l ON l.id = la.listing_id AND l.activity = 1
            INNER JOIN object o ON o.id = l.object_id
            WHERE la.ad_id = 1 AND o.user_id IN (' . implode(',', $disposlIds) . ')
            GROUP BY o.user_id
        ')->queryAll();

        foreach ($data as $row) {
            $sends = Yii::$app->db->createCommand('
                SELECT COUNT(l.id)
                FROM listing_ad la
                INNER JOIN listing l ON l.id = la.listing_id AND l.activity = 1
                INNER JOIN object o ON o.id = l.object_id
                INNER JOIN list_listing ll ON ll.listing_id = l.id
                INNER JOIN list lt ON lt.id = ll.list_id AND lt.user_id IN (SELECT aa.user_id FROM auth_assignment aa WHERE aa.item_name = "broker")
                WHERE la.ad_id = 1 AND o.user_id = ' . $row['user_id'] . '
            ')->queryScalar();

            $model = new DisposlFineListingStat([
                'user_id' => $row['user_id'],
                'listings' => $row['listings'],
                'sends' => $sends,
                'created_at' => time(),
            ]);
            $model->save();
        }
    }

    private function updateContacts()
    {
        $updated = 0;
        $client = new Client(['baseUrl' => Yii::$app->params['amoHost']]);

        for ($i = 1; $i <= 100; $i++) {
            $response = $client->get('api/v4/companies', [
                'USER_HASH' => Yii::$app->params['USER_HASH'],
                'USER_LOGIN' => Yii::$app->params['USER_LOGIN'],
                'page' => $i,
                'limit' => 250,
            ])->send();

            if (!$response->isOk || !isset($response->data['_embedded']['companies']) || count($response->data['_embedded']['companies']) == 0) {
                break;
            }

            foreach ($response->data['_embedded']['companies'] as $row) {
                if (!$contact = Contact::findOne(['id' => $row['id'], 'type' => 4])) {
                    $contact = new Contact([
                        'id'   => intval($row['id']),
                        'type' => 4,
                    ]);
                }

                $contact->name       = $row['name'];
                $contact->created_at = $row['created_at'];

                if (isset($row['custom_fields_values'])) {
                    foreach ($row['custom_fields_values'] as $field) {
                        if ($field['field_name'] !== 'Мотивация') {
                            continue;
                        }

                        if (isset(Contact::$motivations[$field['values'][0]['value']])) {
                            $contact->motivation = Contact::$motivations[$field['values'][0]['value']];
                        }
                    }
                }

                $contact->save();
                $updated++;
            }
        }

        echo 'Updated: ' . $updated, PHP_EOL;
    }
}