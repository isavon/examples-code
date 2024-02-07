<?php

namespace backend\controllers;

/**
 * User Controller
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class UserController extends Controller
{
    public function beforeAction()
    {
        $this->model = new User();

        parent::beforeAction();
    }

    public function actionSearch()
    {
        if (isset($_POST['check_email'])) {
            header('Content-type: application/json');
            echo json_encode($this->model->checkEmail($_POST['email'], $_POST['id']));
            exit;
        }

        if (isset($_POST['get-user'])) {
            header('Content-type: application/json');
            echo json_encode($this->model->getUser($_POST['id']));
            exit;
        }

        if (isset($_POST['send_invite'])) {
            header('Content-type: application/json');

            $result = Lang::to('Invitation sent error');
            if ($this->model->sendInvitation($_POST['id_user'])) {
                $result = Lang::to('Invitation sent successfully');
            }

            echo json_encode($result);
            exit;
        }

        $data['users'] = $this->model->getUsersByCategory(User::BUYER_CATEGORY);
        $data['statuses'] = $this->model->getStatuses();

        if (isset($_POST['update-list'])) {
            return $this->render('list', $data, false);
        }

        $this->render('search', $data);
    }

    public function actionCreate()
    {
        $this->model->create($_POST);
    }

    public function actionUpdate()
    {
        $this->model->update($_POST, $_POST['id']);
    }

    public function actionDelete()
    {
        header('Content-type: application/json');
        $response = ['success' => false];

        if ($this->model->delete($_POST['id'])) {
            $response['success'] = true;
        }

        echo json_encode($response);
    }
}