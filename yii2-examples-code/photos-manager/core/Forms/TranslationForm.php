<?php

namespace Photos\Core\Forms;

use Core\Forms\CompositeForm;
use Photos\Core\ActiveRecord\Photo\TranslationRecord;

/**
 * Class TranslationForm
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\Forms
 */
class TranslationForm extends CompositeForm
{
    /**
     * @var string
     */
    public $information;
    /**
     * @var string
     */
    public $author;
    /**
     * @var string
     */
    public $source;
    /**
     * @var string
     */
    public $language;
    /**
     * @var TranslationRecord
     */
    private $translation;

    /**
     * @param string $language
     * @param TranslationRecord|null $translation
     * @param array $config
     */
    public function __construct($language, TranslationRecord $translation = null, array $config = [])
    {
        parent::__construct($config);
        $this->language = $language;
        if ($translation) {
            $this->information = $translation->information;
            $this->author      = $translation->author;
            $this->source      = $translation->source;
            $this->translation = $translation;
        }
    }

    /**
     * @return string
     */
    public function formName(): string
    {
        return parent::formName() . '_' . $this->language;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['information', 'author', 'source'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    protected function internalForms(): array
    {
        return [];
    }
}
