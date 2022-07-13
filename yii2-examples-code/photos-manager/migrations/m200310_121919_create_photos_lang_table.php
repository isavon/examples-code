<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%photos_lang}}`.
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class m200310_121919_create_photos_lang_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%photos_lang}}', [
            'id' => $this->primaryKey(10)->unsigned(),
            'photo_id' => $this->integer(10)->unsigned()->notNull(),
            'information' => $this->string(255)->null(),
            'author' => $this->string(255)->null(),
            'source' => $this->string(255)->null(),
            'language' => $this->string(255)->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%photos_lang}}');
    }
}
