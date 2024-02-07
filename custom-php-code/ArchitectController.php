<?php

namespace frontend\controllers;

/**
 * Architect Controller
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class ArchitectController extends Controller
{
    public function beforeAction()
    {
        $this->model = new Architect();

        parent::beforeAction();
    }

    public function actionList()
    {
        return $this->render('list', ['architects' => $this->model->getAllList()]);
    }

    public function actionCreate()
    {
        header('Content-type: application/json');
        $response = ['success' => false];

        if (isset($_POST['fullname']) && $this->model->createByFullname($_POST['fullname'])) {
            $response['success'] = true;
        }

        echo json_encode($response);
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