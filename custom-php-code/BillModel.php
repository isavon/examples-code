<?php

namespace rpd\backend\models;

/**
 * Bill Model
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package rpd\backend\models
 */
class BillModel extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    const TABLE = 'bill';

    const STATUS_ENABLED  = 'enabled';
    const STATUS_DISABLED = 'disabled';
    const STATUS_REMOVED = 'removed';
    const STATUS_HIDDEN = 'hidden';

    public static function getStatuses() : array
    {
        return [
            self::STATUS_ENABLED => [
                'title' => Lang::to('Enabled'),
                'class' => 'primary',
            ],
            self::STATUS_DISABLED => [
                'title' => Lang::to('Disable'),
                'class' => 'warning',
            ],
            self::STATUS_REMOVED => [
                'title' => Lang::to('Removed'),
                'class' => 'danger',
            ],
            self::STATUS_HIDDEN => [
                'title' => Lang::to('Hidden'),
                'class' => 'default',
            ],
        ];
    }

    /**
     * Get bill by Id
     *
     * @param int $id
     * @return array
     */
    public function getById(int $id) : array
    {
        return $this->db->query('
            SELECT * 
            FROM ' . self::TABLE . ' 
            WHERE `id` = ' . $id
        )->fetch_assoc() ?? [];
    }

    /**
     * Update information
     *
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function update(array $data, int $id) : bool
    {
        $stmt = $this->db->prepare('
            UPDATE ' . self::TABLE . ' SET
                `client_id` = ?,
                `user_id` = ?,
                `date_start` = ?,
                `date_end` = ?,
                `display` = ?,
                `status` = ?
            WHERE id = ?
        ');
        $stmt->bind_param('iissssi',
            $data['client_id'],
            $data['user_id'],
            $data['date_start'],
            $data['date_end'],
            $data['display'],
            $data['status'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Delete bill
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id) : bool
    {
        return $this->db->query('DELETE FROM ' . self::TABLE . ' WHERE `id` = ' . $id);
    }

    /**
     * Get bills by client id
     *
     * @param int $id
     * @return array
     */
    public function getByClientId(int $id) : array
    {
        return $this->db->query('
            SELECT * 
            FROM ' . self::TABLE . ' 
            WHERE `client_id` = ' . $id
            )->fetch_all() ?? [];
    }

    /**
     * Search bills
     *
     * @param array $search
     * @param bool|int $pagination
     * @return array
     */
    public function search(array $search, $pagination = false) : array
    {
        $where = [];
        if (count($search)) {
            if (!empty($search['client_id'])) {
                $where[] = '`tc`.`client_id` = ' . $search['client_id'];
            }

            if (!empty($search['keys'])) {
                $where[] = '(`tb`.`comment` LIKE "%' . $search['keys'] . '%" OR `tc`.`full_name` LIKE "%' . $search['keys'] . '%")';
            }

            if (!empty($search['date_end'])) {
                $where[] = 'DATEDIFF(`tb`.`date_end`, NOW()) BETWEEN 0 AND 7';
            }

            if (!empty($search['status'])) {
                $where[] = '`tb`.`status` = ' . $search['status'];
            }
        }

        $query = '
            SELECT
                SQL_CALC_FOUND_ROWS
                `tb`.*,
                `tc`.`full_name`,
                `tc`.`position`
            FROM ' . self::TABLE . ' `tb`
            LEFT JOIN ' . ClientModel::TABLE . ' `tc`
                ON `tc`.`client_id` = `tb`.`client_id`
        ';
        if (count($where)) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $query .= ' LIMIT ' . (($search['page'] - 1) * $pagination) . ', ' . $pagination;

        return $this->db->query($query)->fetch_assoc() ?? [];
    }
}