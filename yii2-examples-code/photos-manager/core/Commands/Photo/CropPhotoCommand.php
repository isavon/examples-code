<?php

namespace Photos\Core\Commands\Photo;

use Core\Base\Command;
use Photos\Core\Forms\CropForm;

/**
 * Class CropPhotoCommand
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Commands\Photo
 */
class CropPhotoCommand extends Command
{
    /**
     * @param CropForm $form
     */
    public function __construct(CropForm $form)
    {
        $this->id     = $form->id;
        $this->x      = $form->x;
        $this->y      = $form->y;
        $this->width  = $form->width;
        $this->height = $form->height;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * @return float
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * @return float
     */
    public function getHeight(): float
    {
        return $this->height;
    }
}
