<?php
/**
 * @link      http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license   http://www.yiiframework.com/license/
 */

namespace app\commands;

use app\models\News;
use app\models\Projects;
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
        $vk = new \VK\VK('6180749 ', 'QrNebYK25HTxXwrvWW5g');
        $vk->setApiVersion(5);
        $vk->setAccessToken('512ac84d512ac84d512ac84d26517487c05512a512ac84d089c90d1471a0245796ec90e');
        //        $result = $vk->api('wall.get', ['owner_id' => '-14897324', 'count' => 1]);
        
        $projects = Projects::find()->all();
        foreach ($projects as $project) {
            $result = $vk->api('wall.get', ['owner_id' => $project->vkId, 'count' => 1]);
            //14897324
            foreach ($result['response'] as $row) {
                if (is_array($row)) {
                    $row = (object)$row[0];
                    print_r($row);
                    
                    $comments = $vk->api('wall.getComments',
                        ['owner_id' => $project->vkId, 'post_id' => $row->id, 'extended' => 1, 'count' => 100]);
                    echo "/n/n>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>COMMENTS:/n";
                    print_r($comments);
                    try {
                        $news = new News();
                        $news->text = $row->text;
                        $news->token = $row->id . "_" . $project->vkId;
                        $news->media = @json_encode(@$row->media);
                        $news->attachment = @json_encode(@$row->attachments);
                        $news->project = $project->id;
                        $news->comments = @json_encode($comments['response']);
                        var_dump($news->save());
                    } catch (\Exception $e) {
                    
                    }
                }
            }
        }
        
        // print_r($vk->api('wall.get',['owner_id'=>'54476849','count'=>1]));
    }
}
