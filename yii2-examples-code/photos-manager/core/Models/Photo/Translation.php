<?php

namespace Photos\Core\Models\Photo;

use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Class Translation
 *
 * @ORM\Table(name="photos_lang")
 * @ORM\Entity
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Models\Photo
 */
class Translation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string")
     */
    private $information;
    /**
     * @ORM\Column(type="string")
     */
    private $author;
    /**
     * @ORM\Column(type="string")
     */
    private $source;
    /**
     * @ORM\Column(type="string")
     */
    public $language;
    /**
     * @ORM\ManyToOne(targetEntity="\Photos\Core\Models\Photo\Photo", inversedBy="translations")
     * @JoinColumn(name="photo_id", referencedColumnName="id")
     */
    private $photo;

    /**
     * @param string $information
     * @return $this
     */
    public function setInformation(string $information)
    {
        $this->information = $information;

        return $this;
    }

    /**
     * @param string $author
     * @return $this
     */
    public function setAuthor(string $author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @param string $source
     * @return $this
     */
    public function setSource(string $source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage(string $language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param Photo $photo
     * @return $this
     */
    public function setPhoto(Photo $photo): self
    {
        $this->photo = $photo;

        return $this;
    }
}
