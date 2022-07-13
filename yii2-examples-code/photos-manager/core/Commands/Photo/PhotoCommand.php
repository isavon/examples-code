<?php

namespace Photos\Core\Commands\Photo;

use Core\Base\Command;
use Photos\Core\Forms\PhotoForm;
use Faker\Provider\Uuid;
use yii\web\UploadedFile;
use ColorThief\ColorThief;

/**
 * Class PhotoCommand
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Commands\Photo
 */
class PhotoCommand extends Command
{
    /**
     * @param PhotoForm $form
     */
    public function __construct(PhotoForm $form)
    {
        $this->id = $form->id;

        if ($form->file) {
            $this->file             = $form->file;
            $this->filename         = Uuid::uuid() . '.' . $this->file->getExtension();
            $this->originalFilename = $this->file->name;
            $this->gradientRgb      = implode(',', ColorThief::getColor($this->file->tempName));
        }

        foreach ($form->translations as $translation) {
            $this->translations[] = [
                'language'    => $translation->language,
                'information' => (string) $translation->information,
                'author'      => (string) $translation->author,
                'source'      => (string) $translation->source,
            ];
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    /**
     * @return string
     */
    public function getGradientRgb(): string
    {
        return $this->gradientRgb;
    }

    /**
     * @return UploadedFile
     */
    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @return array
     */
    public function getTranslations(): array
    {
        return $this->translations;
    }
}
