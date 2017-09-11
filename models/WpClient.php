<?php
/**
 * Created by PhpStorm.
 * User: aliaxander
 * Date: 11.09.17
 * Time: 15:32
 */

namespace app\models;


class WpClient
{
    public static function addPost(){
        $client_id = 'BoE50zRj63ua';
        $client_secret = 'oGa9LgmQHZg6xKftCXNFWDkJUWP1Jb38f5DDdAx5kyOAIkgI';
    
        $curl_post_data = array(
            'grant_type' => 'authorization_code',
            'code' => $_GET['code'],
            'redirect_uri' => 'http://oauth.dev',
            'client_id' => $client_id, // Only needed if server is running CGI
            'client_secret' => $client_secret // Only need if server is running CGI
        );
    
        $curl = curl_init('https://blog.ebot.biz//oauth/token/');
    
        // Uncomment if you want to use CLIENTID AND SECRET IN THE HEADER
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($curl, CURLOPT_USERPWD, $client_id.':'.$client_secret); // Your credentials goes here
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
        curl_setopt($curl, CURLOPT_REFERER, 'http://www.example.com/1');
    
        $curl_response = curl_exec($curl);
        curl_close($curl);
        echo '<pre>';
        print_r($curl_response);
        echo '</pre>';
    }
}