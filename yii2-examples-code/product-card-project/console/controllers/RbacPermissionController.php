<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\console\Exception;
use yii\helpers\Console;
use yii\helpers\ArrayHelper;
use common\models\User;

/**
 * RBAC Permissions
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class RbacPermissionController extends Controller
{
    private $auth;

    public function __construct($id, $module, $config = array())
    {
        $this->auth = Yii::$app->authManager;

        return parent::__construct($id, $module, $config);
    }

    /**
     * List all permissions
     */
    public function actionList()
    {
        $permissions = $this->auth->getPermissions();
        foreach ($permissions as $permission) {
            echo $permission->name  . ' - ' . $permission->description . PHP_EOL;
        }
    }

    /**
     * Create permission
     */
    public function actionCreate()
    {
        $name = $this->prompt('Name:', ['required' => true]);

        if ($this->auth->getPermission($name)) {
            throw new Exception('Permission already exists');
        }

        $desctiption = $this->prompt('Description:', ['required' => true]);

        $permission = $this->auth->createPermission($name);
        $permission->description = $desctiption;

        $this->auth->add($permission);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Delete permission
     */
    public function actionDelete()
    {
        $name = $this->prompt('Name:', ['required' => true]);

        if (!$permission = $this->auth->getPermission($name)) {
            throw new Exception('Permission is not found');
        }

        if (!$this->confirm('Are you sure?')) {
            return false;
        }

        $this->auth->remove($permission);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Assign permision to role
     */
    public function actionAssign()
    {
        $roleName = $this->select('Role:', ArrayHelper::map($this->auth->getRoles(), 'name', 'description'));
        $role = $this->auth->getRole($roleName);

        $permissionName = $this->select('Permission:', ArrayHelper::map($this->auth->getPermissions(), 'name', 'description'));
        $permission = $this->auth->getPermission($permissionName);

        $this->auth->addChild($role, $permission);
        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Revoke permission from role
     */
    public function actionRevoke()
    {
        $roleName = $this->select('Role:', ArrayHelper::map($this->auth->getRoles(), 'name', 'description'));
        $role = $this->auth->getRole($roleName);

        $permissionName = $this->select('Permission:', ArrayHelper::merge(
            ['all' => 'All Permissions'],
            ArrayHelper::map($this->auth->getPermissionsByRole($roleName), 'name', 'description'))
        );

        if ($permissionName == 'all') {
            $this->auth->removeChildren($role);
        } else {
            $permission = $this->auth->getPermission($permissionName);
            $this->auth->removeChild($role, $permission);
        }

        echo $this->ansiFormat('Done!' . PHP_EOL, Console::BG_GREEN);
    }

    /**
     * Permissions by role
     */
    public function actionByRole()
    {
        $roleName = $this->select('Role:', ArrayHelper::map($this->auth->getRoles(), 'name', 'description'));

        if (!$permissions = $this->auth->getPermissionsByRole($roleName)) {
            throw new Exception('Permissions is not found');
        }

        foreach ($permissions as $permission) {
            echo $permission->name  . ' - ' . $permission->description . PHP_EOL;
        }
    }

    /**
     * Permissions by user
     */
    public function actionByUser()
    {
        $userId = $this->prompt('User ID:', ['required' => true]);
        if (!$user = User::findOne($userId)) {
            throw new Exception('User is not found');
        }

        if (!$permissions = $this->auth->getPermissionsByUser($userId)) {
            throw new Exception('Permissions is not found');
        }

        foreach ($permissions as $permission) {
            echo $permission->name  . ' - ' . $permission->description . PHP_EOL;
        }
    }
}