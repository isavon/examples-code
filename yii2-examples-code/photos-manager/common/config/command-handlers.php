<?php

use Photos\Core\Commands\Photo\CreatePhotoCommand;
use Photos\Core\Commands\Photo\CropPhotoCommand;
use Photos\Core\Commands\Photo\DeletePhotoCommand;
use Photos\Core\Commands\Photo\UpdateInformationCommand;
use Photos\Core\Handlers\PhotoHandler;

return [
    UpdateInformationCommand::class => PhotoHandler::class,
    DeletePhotoCommand::class       => PhotoHandler::class,
    CropPhotoCommand::class         => PhotoHandler::class,
];