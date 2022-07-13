<?php

namespace Photos\Backend\Controllers;

use Photos\Core\Commands\Photo\CreatePhotoCommand;
use Photos\Core\Commands\Photo\UpdateInformationCommand;
use Photos\Core\Commands\Photo\DeletePhotoCommand;
use Photos\Core\Commands\Photo\CropPhotoCommand;
use Photos\Core\ActiveRecord\Photo\PhotoRecord;
use Photos\Core\Forms\PhotoForm;
use Photos\Core\Forms\CropForm;
use Yii;
use yii\web\UploadedFile;
use yii\web\MethodNotAllowedHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Class PhotosController
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Backend\Controllers
 */
class PhotosController extends BackendController
{
    /**
     * @var CommandBus
     */
    private $bus;

    /**
     * @param string $id
     * @param $module
     * @param CommandBus $bus
     * @param array $config
     */
    public function __construct(string $id, $module, CommandBus $bus, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->bus = $bus;
    }

    /**
     * @return array
     */
    public function behaviors(): array
    {
        return [
            [
                'class' => 'yii\filters\AjaxFilter'
            ],
        ];
    }

    /**
     * @return string|array
     * @throws NotFoundHttpException
     */
    public function actionUpload()
    {
        $form       = new PhotoForm();
        $form->file = UploadedFile::getInstance($form, 'file');

        if (!$form->validate()) {
            return ['error' => $form->getFirstError('file')];
        }

        $command = new CreatePhotoCommand($form);
        $this->bus->handle($command);

        return $this->asJson($this->findModel($command->getId())->normalize());
    }

    /**
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionUpdateInformation($id)
    {
        $photo = $this->findModel($id);
        $form  = PhotoForm::createFromModel($photo);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            $this->bus->handle(new UpdateInformationCommand($form));

            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => 'Something is wrong']);
    }

    /**
     * @param int $id
     * @return Response
     */
    public function actionDelete($id)
    {
        $this->bus->handle(new DeletePhotoCommand((int)$id));

        return $this->asJson(['success' => true]);
    }

    /**
     * @param string $query
     * @return Response
     */
    public function actionSearch($query)
    {
        return $this->asJson(PhotoRecord::search($query));
    }

    /**
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionGet($id)
    {
        return $this->asJson($this->findModel($id)->normalize());
    }

    /**
     * @param int $id
     * @return Response
     * @throws NotFoundHttpException
     */
    public function actionCrop($id)
    {
        $photo = $this->findModel($id);
        $form  = CropForm::createFromModel($photo);

        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            $this->bus->handle(new CropPhotoCommand($form));

            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => 'Something is wrong']);
    }

    /**
     * @param integer $id
     * @return PhotoRecord
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (!$model = PhotoRecord::findOne($id)) {
            throw new NotFoundHttpException('Photo not found');
        }

        return $model;
    }
}
