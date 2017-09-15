<?php
/**
 * Created by PhpStorm.
 * User: aliaxander
 * Date: 11.09.17
 * Time: 12:24
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Vk
 *
 * @package app\models
 */
class Projects extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'projects';
    }
}