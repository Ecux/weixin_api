<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () {
    return ['message'=>"404 Not Found", 'status_code'=>404];
});

//todo 事件监听
$router->get('valid', 'WeixinController@valid');

//授权接口
$router->get('auth',['uses'=>'WechatController@auth','as' => 'auth']);

//微信回调
$router->get('callback', ['uses'=>'WechatController@callback','as' => 'callback']);


