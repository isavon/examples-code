<?php

namespace Photos\Core\Transformers;

use Photos\Core\Commands\Photo\PhotoCommand;
use Photos\Core\Models\Photo\Photo;
use Photos\Core\Models\Photo\Translation;

/**
 * Class CommandPhotoTransformer
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Transformers
 */
class CommandPhotoTransformer
{
    /**
     * @param PhotoCommand $command
     * @param Photo|null $photo
     * @return Photo
     */
    public static function transform(PhotoCommand $command, Photo $photo = null): Photo
    {
        $isNew = is_null($photo);

        if ($isNew) {
            $photo = new Photo;
            $photo->setFilename($command->getFilename());
            $photo->setOriginalFilename($command->getOriginalFilename());
            $photo->setGradientRgb($command->getGradientRgb());
        }

        foreach ($command->getTranslations() as $translationParams) {
            $translation = $isNew
                ? new Translation
                : $photo->getTranslationByLanguage($translationParams['language']);

            $translation
                ->setLanguage($translationParams['language'])
                ->setInformation($translationParams['information'])
                ->setAuthor($translationParams['author'])
                ->setSource($translationParams['source']);

            $photo->addTranslation($translation);
        }

        return $photo;
    }
}
