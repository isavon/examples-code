<?php

namespace Photos\Services\FileStorage;

use yii\web\UploadedFile;

/**
 * Interface PhotoFileStorageInterface
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Services\FileStorage
 */
interface PhotoFileStorageInterface
{
    /**
     * @param UploadedFile $file
     * @param string $filename
     */
    public function upload(UploadedFile $file, string $filename);

    /**
     * @param string $filename
     */
    public function delete(string $filename);

    /**
     * @param string $filename
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     */
    public function crop(string $filename, float $x, float $y, float $width, float $height);
}
