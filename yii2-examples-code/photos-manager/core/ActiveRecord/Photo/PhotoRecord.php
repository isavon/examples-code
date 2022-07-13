<?php

namespace Photos\Core\ActiveRecord\Photo;

use Yii;
use yii\db\ActiveQuery;
use Core\Queries\NotPersistentActiveRecord;

/**
 * Class PhotoRecord
 *
 * @property int $id
 * @property string $filename
 * @property string $original_filename
 * @property string $gradient_rgb
 * @property string $created_at
 * @property string $updated_at
 *
 * @property TranslationRecord[] $translations
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 * @package Photos\Core\ActiveRecord\Photo
 */
class PhotoRecord extends NotPersistentActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%photos}}';
    }

    /**
     * @param null $code
     * @return ActiveQuery
     */
    public function getTranslation($code = null)
    {
        return $this->hasOne(TranslationRecord::class, ['photo_id' => 'id'])
            ->andOnCondition(['language' => $code ?: Yii::$app->language]);
    }

    /**
     * @return ActiveQuery
     */
    public function getTranslations()
    {
        return $this->hasMany(TranslationRecord::class, ['photo_id' => 'id']);
    }

    /**
     * @param string $query
     * @return array
     */
    public static function search(string $query): array
    {
        $data   = [];

        /** @var PhotoRecord[] $photos */
        $photos = self::find()
            ->leftJoin(TranslationRecord::tableName() . ' pl', 'pl.photo_id = ' . self::tableName() . '.id')
            ->where(['or',
                ['like', self::tableName() . '.original_filename', $query],
                ['like', 'pl.information', $query],
                ['like', 'pl.author', $query],
                ['like', 'pl.source', $query]
            ])
            ->groupBy(self::tableName() . '.id')
            ->all();

        foreach ($photos as $photo) {
            $data[$photo->id] = [
                'id'       => $photo->id,
                'filename' => $photo->filename,
            ];

            foreach ($photo->translations as $information) {
                $data[$photo->id]['translation'][] = [
                    'information' => $information->information,
                    'author'      => $information->author,
                    'source'      => $information->source,
                    'language'    => $information->language,
                ];
            }
        }

        return array_values($data);
    }

    /**
     * @return array
     */
    public function normalize(): array
    {
        $data = [
            'id'       => $this->id,
            'filename' => $this->filename,
        ];

        foreach ($this->translations as $information) {
            $data['translation'][] = [
                'information' => $information->information,
                'author'      => $information->author,
                'source'      => $information->source,
                'language'    => $information->language,
            ];
        }

        return $data;
    }
}
