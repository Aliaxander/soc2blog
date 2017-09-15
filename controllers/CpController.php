<?php

namespace app\controllers;

use app\models\News;
use app\models\Projects;
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
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Cookie;
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
        //$this->layout = '';
        
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
    
    public function actionVk()
    {
        $collBackUrl = 'https://soc2blog.ebot.biz/cp/vk';
       // $collBackUrl = 'http://localhost:8080/cp/vk';
        $vk = new \VK\VK('6180749 ', 'QrNebYK25HTxXwrvWW5g');
        if (Yii::$app->request->get('code')) {
            $accessToken = $vk->getAccessToken(Yii::$app->request->get('code'), $collBackUrl);
            $cookie = new Cookie([
                'name' => 'session',
                'value' => $accessToken['access_token'],
                'expire' => time() + 86400 * 365,
            ]);
            \Yii::$app->getResponse()->getCookies()->add($cookie);
            
            return $this->redirect('/cp');
        }
        
        return $this->redirect($vk->getAuthorizeUrl('', $collBackUrl));
    }
    
    public function actionIndex()
    {
        $cookies = Yii::$app->request->cookies;
        $session = $cookies->get('session');
        if (Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $data = Projects::find()->all();
        if (Yii::$app->request->isPost) {
            $model = new Projects();
            $model->name = Yii::$app->request->post('name');
            $model->vkId = Yii::$app->request->post('vkId');
            $model->vkProfile = Yii::$app->request->post('vkProfile');
            $model->save();
            
            return $this->refresh();
        }
        $isPost = 1;
        if (empty($session)) {
            $session = "<a href='/cp/vk'>Войти через VK</a>";
            $isPost = false;
        }
        
        return $this->render('index.twig', ['data' => $data, 'session' => $session, 'isPost' => $isPost]);
        
        
        //        WpClient::addPost();
        //        die;
        //        $vk = new \VK\VK('6180749 ', 'QrNebYK25HTxXwrvWW5g');
        //        //        print_r($vk->getAuthorizeUrl());
        //        //        die();
        //        //        $result = $vk->getAccessToken('73e190efc179e4122c');
        //        //        $data = $result;
        //        //        var_dump($data);
        //        //        //code = e600192589fc39db6d
        //        //        7a8de399e5cf8f5c171d623f43ecf18bdd908935fee15a024c5eb8f0d26942675c53233c1fe687578d298
        //        $vk->setAccessToken('7a8de399e5cf8f5c171d623f43ecf18bdd908935fee15a024c5eb8f0d26942675c53233c1fe687578d298');
        //        $result = $vk->api('wall.get', ['owner_id' => '-18742951', 'count' => 100]);
        //
        //        foreach ($result['response'] as $row) {
        //            if (is_array($row)) {
        //                $row = (object)$row;
        //                print_r($row);
        //
        //                $news = new News();
        //                $news->text = $row->text;
        //                $news->token = $row->id;
        //                $news->media = @json_encode(@$row->media);
        //                $news->attachment = @json_encode(@$row->attachment);
        //                var_dump($news->save());
        //            }
        //        }
        //    return $this->render('index.twig', ['data' => json_encode($data)]);
    }
    
    public function actionBlog()
    {
        $this->layout = '';
        $feed = new Feed();
        $data = Projects::findOne(['id' => Yii::$app->request->get('id')]);
        
        
        $channel = new Channel();
        $channel
            ->title($data->name)
            ->description($data->name)
            ->appendTo($feed);
        
        
        // RSS item
        $news = News::find()->where(['project' => Yii::$app->request->get('id')])->limit(50)->orderBy('id desc')->all();
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
                ->description($this->shortText($row->text) . " " . $media)
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
