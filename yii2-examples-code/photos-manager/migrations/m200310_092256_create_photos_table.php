<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%photos}}`.
 *
 * @author Ivan Savon <isavon.we@gmail.com>
 */
class m200310_092256_create_photos_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%photos}}', [
            'id' => $this->primaryKey(10)->unsigned(),
            'filename' => $this->string(255)->unique()->notNull(),
            'original_filename' => $this->string(255)->notNull(),
            'gradient_rgb' => $this->string(30),
            'created_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%photos}}');
    }
}
