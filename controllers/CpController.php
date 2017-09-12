<?php

namespace app\controllers;

use app\models\News;
use app\models\Vk;
use app\models\WpClient;
use Bhaktaraz\RSSGenerator\Channel;
use Bhaktaraz\RSSGenerator\Feed;
use Bhaktaraz\RSSGenerator\Item;
use GuzzleHttp\Client;
use Vnn\WpApiClient\Auth\WpBasicAuth;
use Vnn\WpApiClient\Http\GuzzleAdapter;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class CpController extends Controller
{
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $this->layout = '';
        
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }
    
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        WpClient::addPost();
        die;
        $vk = new \VK\VK('6180749 ', 'QrNebYK25HTxXwrvWW5g');
        //        print_r($vk->getAuthorizeUrl());
        //        die();
        //        $result = $vk->getAccessToken('73e190efc179e4122c');
        //        $data = $result;
        //        var_dump($data);
        //        //code = e600192589fc39db6d
        //        7a8de399e5cf8f5c171d623f43ecf18bdd908935fee15a024c5eb8f0d26942675c53233c1fe687578d298
        $vk->setAccessToken('7a8de399e5cf8f5c171d623f43ecf18bdd908935fee15a024c5eb8f0d26942675c53233c1fe687578d298');
        $result = $vk->api('wall.get', ['owner_id' => '-18742951', 'count' => 100]);
        
        foreach ($result['response'] as $row) {
            if (is_array($row)) {
                $row = (object)$row;
                print_r($row);
                
                $news = new News();
                $news->text = $row->text;
                $news->token = $row->id;
                $news->media = @json_encode(@$row->media);
                $news->attachment = @json_encode(@$row->attachment);
                var_dump($news->save());
            }
        }
        //    return $this->render('index.twig', ['data' => json_encode($data)]);
    }
    
    
    public function actionBlog()
    {
        $feed = new Feed();
        
        $channel = new Channel();
        $channel
            ->title("Programming")
            ->description("Programming with php")
            ->appendTo($feed);
        
        
        // RSS item
        $news = News::find()->limit(50)->orderBy('id desc')->all();
        foreach ($news as $row) {
            $item = new Item();
            $media = @json_decode(@$row->attachment);
            
            if (!empty($media)) {
                if ($media->type === "video") {
                    $media = $media->video->image;
                } elseif ($media->type === "photo") {
                    $media = @$row->media->thumb_src;
                }
            }
            if (is_object($media)) {
                $media = @$media->album->thumb->src_xxbig;
            }
            //print_r($media);
            $item
                ->title($this->text2title($row->text))
                ->description($this->shortText($row->text))
                ->content($row->text);
            
            if (!empty($media)) {
                $item->enclosure($media, @get_headers($media, true)['Content-Length'], 'image/jpeg');
            }
            
            $item->appendTo($channel);
        }
        
        header('Content-type: text/xml');
        
        return $feed;
        die;
    }
    
    protected function text2title($text)
    {
        $max_lengh = 20;
        
        if (mb_strlen($text, "UTF-8") > $max_lengh) {
            $text_cut = mb_substr($text, 0, $max_lengh, "UTF-8");
            $text_explode = explode(" ", $text_cut);
            
            unset($text_explode[count($text_explode) - 1]);
            
            $text_implode = implode(" ", $text_explode);
            
            return $text_implode . "...";
        } else {
            return $text;
        }
    }
    
    protected function shortText($text)
    {
        $max_lengh = 1000;
        
        if (mb_strlen($text, "UTF-8") > $max_lengh) {
            $text_cut = mb_substr($text, 0, $max_lengh, "UTF-8");
            $text_explode = explode(" ", $text_cut);
            
            unset($text_explode[count($text_explode) - 1]);
            
            $text_implode = implode(" ", $text_explode);
            
            return $text_implode . "...";
        } else {
            return $text;
        }
    }
}
