<?php

namespace Photos\Core\Models\Photo;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

/**
 * Class Photo
 *
 * @ORM\Table(name="photos")
 * @ORM\Entity()
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Models\Photo
 */
class Photo
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
    private $filename;
    /**
     * @ORM\Column(type="string")
     */
    private $original_filename;
    /**
     * @ORM\Column(type="string")
     */
    private $gradient_rgb;
    /**
     * @ORM\OneToMany(targetEntity="\Photos\Core\Models\Photo\Translation", mappedBy="photo", cascade={"persist", "remove"})
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
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
     * @return $this
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return $this
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @param string $originalFilename
     * @return $this
     */
    public function setOriginalFilename(string $originalFilename): self
    {
        $this->original_filename = $originalFilename;

        return $this;
    }

    /**
     * @param string $gradientRgb
     * @return $this
     */
    public function setGradientRgb(string $gradientRgb): self
    {
        $this->gradient_rgb = $gradientRgb;

        return $this;
    }

    /**
     * @param string $language
     * @return Translation|false
     */
    public function getTranslationByLanguage(string $language)
    {
        return $this->getTranslations()->filter(function ($translation) use ($language) {
            return $translation->language == $language;
        })->first();
    }

    /**
     * @return ArrayCollection|PersistentCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param Translation $translation
     * @return $this
     */
    public function addTranslation(Translation $translation): self
    {
        $this->translations->add($translation);
        $translation->setPhoto($this);

        return $this;
    }
}
