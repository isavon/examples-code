<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%photos_relations}}`.
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class m200310_124833_create_photos_relations_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%photos_relations}}', [
            'photo_id' => $this->integer(10)->unsigned()->notNull(),
            'type' => $this->string(255)->notNull(),
            'type_id' => $this->integer(10)->unsigned()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%photos_relations}}');
    }
}
