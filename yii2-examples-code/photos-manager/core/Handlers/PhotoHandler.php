<?php

namespace Photos\Core\Handlers;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Core\Base\CommandHandlerException;
use Photos\Core\Commands\Photo\CreatePhotoCommand;
use Photos\Core\Commands\Photo\UpdateInformationCommand;
use Photos\Core\Commands\Photo\DeletePhotoCommand;
use Photos\Core\Commands\Photo\CropPhotoCommand;
use Photos\Core\Transformers\CommandPhotoTransformer;
use Photos\Core\Models\Photo\Photo;
use Photos\Services\FileStorage\PhotoFileStorage;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

/**
 * Class PhotoHandler
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Handlers
 */
class PhotoHandler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var PhotoFileStorageInterface
     */
    private $photoFileStorage;

    /**
     * PhotoHandler constructor.
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->photoFileStorage = new PhotoFileStorage(new Filesystem(new Local(\Yii::getAlias('@backend') . '/../uploads/photo')));
    }

    /**
     * @param CreatePhotoCommand $command
     */
    public function create(CreatePhotoCommand $command): void
    {
        $photo = CommandPhotoTransformer::transform($command);
        $this->photoFileStorage->upload($command->getFile(), $command->getFilename());

        try {
            $this->entityManager->persist($photo);
            $this->entityManager->flush();

            $command->setId($photo->getId());
        } catch (DBALException $e) {
            throw new CommandHandlerException($photo, $e->getMessage());
        }
    }

    /**
     * @param UpdateInformationCommand $command
     */
    public function updateInformation(UpdateInformationCommand $command): void
    {
        $photo = $this->entityManager->getRepository(Photo::class)->find($command->getId());
        $photo = CommandPhotoTransformer::transform($command, $photo);

        try {
            $this->entityManager->persist($photo);
            $this->entityManager->flush();
        } catch (DBALException $e) {
            throw new CommandHandlerException($photo, $e->getMessage());
        }
    }

    /**
     * @param DeletePhotoCommand $command
     */
    public function delete(DeletePhotoCommand $command): void
    {
        $photo = $this->entityManager->getRepository(Photo::class)->find($command->getId());
        $this->photoFileStorage->delete($photo->getFilename());

        try {
            $this->entityManager->remove($photo);
            $this->entityManager->flush();
        } catch (DBALException $e) {
            throw new CommandHandlerException($photo, $e->getMessage());
        }
    }

    /**
     * @param CropPhotoCommand $command
     */
    public function crop(CropPhotoCommand $command): void
    {
        $photo = $this->entityManager->getRepository(Photo::class)->find($command->getId());
        $this->photoFileStorage->crop($photo->getFilename(), $command->getX(), $command->getY(), $command->getWidth(), $command->getHeight());
    }
}
