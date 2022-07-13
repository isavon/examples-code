<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use common\models\User;

/**
 * RBAC Roles
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class RbacRoleController extends Controller
{
    private $auth;

    public function __construct($id, $module, $config = array())
    {
        $this->auth = Yii::$app->authManager;

        return parent::__construct($id, $module, $config);
    }

    /**
     * List all roles
     */
    public function actionList()
    {
        $roles = $this->auth->getRoles();
        foreach ($roles as $role) {
            echo $role->name  . ' - ' . $role->description . PHP_EOL;
        }
    }

    /**
     * Create role
     */
    public function actionCreate()
    {
        $name = $this->prompt('Name:', ['required' => true]);

        if ($this->auth->getRole($name)) {
            throw new Exception('Role already exists');
        }

        $desctiption = $this->prompt('Description:', ['required' => true]);

        $role = $this->auth->createRole($name);
        $role->description = $desctiption;

        $this->auth->add($role);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Delete role
     */
    public function actionDelete()
    {
        $name = $this->prompt('Name:', ['required' => true]);

        if (!$role = $this->auth->getRole($name)) {
            throw new Exception('Role is not found');
        }

        if (!$this->confirm('Are you sure?')) {
            return false;
        }

        $this->auth->remove($role);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Assign role to user
     */
    public function actionAssign()
    {
        $userId = $this->prompt('User ID:', ['required' => true]);
        if (!$user = User::findOne($userId)) {
            throw new Exception('User is not found');
        }

        $roleName = $this->select('Role:', ArrayHelper::map($this->auth->getRoles(), 'name', 'description'));
        $role = $this->auth->getRole($roleName);

        $this->auth->revokeAll($user->id);
        $this->auth->assign($role, $user->id);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Revoke role from user
     */
    public function actionRevoke()
    {
        $userId = $this->prompt('User ID:', ['required' => true]);
        if (!$user = User::findOne($userId)) {
            throw new Exception('User is not found');
        }

        $roleName = $this->select('Role:', ArrayHelper::merge(
            ['all' => 'All Roles'],
            ArrayHelper::map($this->auth->getRolesByUser($user->id), 'name', 'description'))
        );

        if ($roleName == 'all') {
            $this->auth->revokeAll($user->id);
        } else {
            $role = $this->auth->getRole($roleName);
            $this->auth->revoke($role, $user->id);
        }

        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Roles by user
     */
    public function actionByUser()
    {
        $userId = $this->prompt('User ID:', ['required' => true]);
        if (!$user = User::findOne($userId)) {
            throw new Exception('User is not found');
        }

        echo 'Role: ' . key($this->auth->getRolesByUser($userId)) . PHP_EOL;
    }
}