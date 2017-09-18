<?php

namespace app\controllers;

use app\models\News;
use app\models\Projects;
use Bhaktaraz\RSSGenerator\Channel;
use Bhaktaraz\RSSGenerator\Feed;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Cookie;

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
        if (is_object($session)) {
            $session = @$session->value['access_token'];
        }
        if (Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $data = Projects::find()->all();
        if (Yii::$app->request->isPost) {
            $model = new Projects();
            $model->name = Yii::$app->request->post('name');
            $model->vkId = Yii::$app->request->post('vkId');
            //            $model->vkProfile = Yii::$app->request->post('vkProfile');
            $model->save();
            
            return $this->refresh();
        }
        $isPost = 1;
        if (empty($session)) {
            $session = "<a href='/cp/vk'>Войти через VK</a>";
            $isPost = false;
        }
        //        print_r($session);
        //        die;
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
        $news = News::find()->where([
            'project' => Yii::$app->request->get('id'),
            'isPost' => 0
        ])->limit(10)->orderBy('id desc')->all();
        
        foreach ($news as $row) {
            $addText = '';
            $item = new \app\models\Helpers\Item();
            $attachments = @json_decode(@$row->attachment);
            $media = @json_decode(@$row->media);
            //            print_r($attachments);
            //            die;
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    try {
                        if ($attachment->type == "video") {
                            $attachment = $attachment->video->image;
                        } elseif ($attachment->type == "photo") {
                            if (!empty(@$attachment->photo->photo_1280)) {
                                $attachment = @$attachment->photo->photo_1280;
                            } elseif (!empty(@$attachment->photo->photo_807)) {
                                $attachment = @$attachment->photo->photo_807;
                            } elseif (!empty(@$attachment->photo->photo_604)) {
                                $attachment = @$attachment->photo->photo_604;
                            } elseif (!empty(@$attachment->photo->photo_130)) {
                                $attachment = @$attachment->photo->photo_130;
                            } else {
                                $attachment = @$media->thumb_src;
                            }
                            if (is_object($attachment)) {
                                $attachment = @$attachment->album->thumb->src_xxbig;
                            }
                        } else {
                            $attachment = '';
                        }
                        
                        if (!empty($attachment)) {
                            try {
                                $addText .= "\n<img src='" . $attachment . "'>\n";
                            } catch (\Exception $e) {
                                //                            print_r($attachment);
                                //                            die;
                            }
                            $item->enclosure($attachment, @get_headers($attachment, true)['Content-Length'],
                                'image/jpeg');
                        }
                    } catch (\Exception $e) {
                    
                    }
                }
            }
            //            print_r($addText);
            //            die;
            $row->text = $this->replaceText($row->text);
            //print_r($media);
            $pos = strrpos($addText, "\n");
            if ($pos != false) {
                $addText = substr($addText, 0, $pos);
            }
//            print_r($attachment);
//            die;
            if(is_object($attachment) && $attachment->type==='video'){
                $row->text .= " " . @$attachment->video->description;
                if (isset($attachment->video->photo_800)) {
                    $attachment = $attachment->video->photo_800;
                } elseif (isset($attachment->video->photo_640)) {
                    $attachment = $attachment->video->photo_640;
                } elseif (isset($attachment->video->photo_320) {
                    $attachment = $attachment->video->photo_320;
                } else {
                    $attachment = "";
                }
            }
            if (!empty($attachment)) {
                $addText = str_replace("<img src='" . $attachment . "'>", "", $addText);
            }
            $item
                ->title($this->text2title($row->text))
                ->description($this->shortText($row->text))
                ->content($row->text . $addText)->comments('https://soc2blog.ebot.biz/cp/comments?id=' . $row->id);
            
            
            $item->appendTo($channel);
            $row->isPost = 1;
            $row->update();
        }
        
        header('Content-type: text/xml');
        
        return $feed;
        die;
    }
    
    /**
     * @return mixed
     */
    public function actionComments()
    {
        $news = News::find()->where(['id' => Yii::$app->request->get('id')])->one();
        
        
        return $news->comments;
    }
    
    protected function replaceText($text)
    {
        $text = preg_replace("/(#)+[\w\d.]*+(@)+[\w\d]*/iu", '', $text);
        $text = preg_replace("/http(s)?:\/\/[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(\/.*)?/i", '', $text);
        $text = preg_replace("/\bисточник:?\b/i", "", $text);
        $text = preg_replace("/Читать полностью:?/i", "", $text);
        
        return $text;
    }
    
    protected function text2title($text)
    {
        $max_lengh = 30;
        
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
