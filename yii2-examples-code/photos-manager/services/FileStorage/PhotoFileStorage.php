<?php

namespace Photos\Services\FileStorage;

use League\Flysystem\FilesystemInterface;
use yii\web\UploadedFile;
use yii\imagine\Image;

/**
 * Class PhotoFileStorage
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Services\FileStorage
 */
class PhotoFileStorage implements PhotoFileStorageInterface
{
    /**
     * @var FilesystemInterface
     */
    private $adapter;

    /**
     * UserFileStorage constructor.
     * @param FilesystemInterface $adapter
     */
    public function __construct(FilesystemInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param UploadedFile $file
     * @param string $filename
     */
    public function upload(UploadedFile $file, string $filename)
    {
        $fp = fopen($file->tempName, 'rb');
        $this->adapter->putStream($filename, $fp);
        fclose($fp);
    }

    /**
     * @param string $filename
     */
    public function delete(string $filename)
    {
        $this->adapter->delete($filename);
    }

    /**
     * @param string $filename
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     */
    public function crop(string $filename, float $x, float $y, float $width, float $height)
    {
        $filePath = $this->adapter->getAdapter()->getPathPrefix() . $filename;

        Image::crop($filePath, $width, $height, [$x, $y])->save($filePath);
    }
}
