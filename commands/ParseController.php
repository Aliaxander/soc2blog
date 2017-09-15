<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\News;
use app\models\Projects;
use VK\VK;
use yii\console\Controller;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since  2.0
 */
class ParseController extends Controller
{
    /**
     * This command echoes what you have entered as the message.
     *
     * @param string $message the message to be echoed.
     */
    public function actionIndex($message = 'hello world')
    {
        echo $message . "\n";
        $projects = Projects::find()->all();
        foreach ($projects as $project) {
            $vk = new \VK\VK('6180749 ', 'QrNebYK25HTxXwrvWW5g');
            $vk->setAccessToken($project->vkProfile);
            $result = $vk->api('wall.get', ['owner_id' => $project->vkId, 'count' => 1000]);
            
            foreach ($result['response'] as $row) {
                if (is_array($row)) {
                    $row = (object)$row;
                    print_r($row);
                    try {
                        $news = new News();
                        $news->text = $row->text;
                        $news->token = $row->id . "_" . $project->vkId;
                        $news->media = @json_encode(@$row->media);
                        $news->attachment = @json_encode(@$row->attachment);
                        $news->project = $project->id;
                        var_dump($news->save());
                    } catch (\Exception $e) {
                    
                    }
                }
            }
        }
        
        // print_r($vk->api('wall.get',['owner_id'=>'54476849','count'=>1]));
    }
}
