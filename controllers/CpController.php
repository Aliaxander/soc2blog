<?php

namespace app\controllers;

use app\models\News;
use app\models\Vk;
use app\models\WpClient;
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
        $client = new \Vnn\WpApiClient\WpClient(new \SoapClient(), 'https://blog.ebot.biz/');
        $client->setCredentials(new WpBasicAuth('user', 'securepassword'));
        //https://blog.ebot.biz/oauth1/authorize?oauth_token=BoE50zRj63ua&oauth_token_secret=oGa9LgmQHZg6xKftCXNFWDkJUWP1Jb38f5DDdAx5kyOAIkgI
    }
}
