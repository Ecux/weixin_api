<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/22
 * Time: 13:42
 */

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Ixudra\Curl\Facades\Curl;

class WechatController extends Controller
{

    const ExpireTime = 7000;
    const UserRedisKey = 'access_token';

    private $appId;
    private $appSecret;

    public function __construct()
    {
        $this->appId = config('weixin.wechat.app_id');
        $this->appSecret = config('weixin.wechat.app_secret');
    }

    /**
     *
     * no.1
     * 获取 token
     * @return mixed
     */
    public function getToken() {
        //1.获取redis 的access_token
        //$data = json_decode(Redis::get(self::UserRedisKey),true);
        $data = [];
        //dd($data);
        //if ( empty($data) || $data['expire_time'] < time() ) {
        if ( empty($data) ) {
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appId."&secret=".$this->appSecret;
            $jsonRet = Curl::to($url)->get();
            $objRet = json_decode($jsonRet);
            $access_token = $objRet->access_token;
            if ($access_token) {
                //$data['expire_time'] = time() + self::ExpireTime;
                //$data['access_token'] = $access_token;
                //Redis::set(self::UserRedisKey,json_encode($data));
                //Redis::expire(self::UserRedisKey, self::ExpireTime);
            }
        } else {
            $access_token = $data;
        }
        return $access_token;
    }

    /**
     * 获取code
     * @param $redirect_uri
     * @param int $state
     * @param string $scope
     * @param string $response_type
     */
    public function getCode($redirect_uri, $state=1, $scope='snsapi_base', $response_type='code'){
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$this->appId.'&redirect_uri='.$redirect_uri.'&response_type='.$response_type.'&scope='.$scope.'&state='.$state.'#wechat_redirect';
        header('Location: '.$url, true, 301);
    }

    public function getAccessToken($code,$grant_type='authorization_code'){
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecret.'&code='.$code.'&grant_type='.$grant_type.'';
        return Curl::to($url)->get();
    }


    public function getUserInfo($openId){
        $access_token = $this->getToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openId.'&lang=zh_CN';
        return Curl::to($url)->get();
    }


    public function callback(Request $request){
        $code = $request->get('code');
        //$param = $request->get('param');
        $accessTokenInfo = json_decode($this->getAccessToken($code));
        $openId = $accessTokenInfo->openid;
        $userInfo = json_decode($this->getUserInfo($openId));
        return $user = $this->hasMember($openId,$userInfo);
    }

    //用户授权事件
    public function auth(Request $request) {
        $param = $request->all();// 如果有参数
        $openid = array_get($param,'openid');
        if($openid){
            $userInfo = json_decode($this->getUserInfo($openid));
            return $this->hasMember($openid,$userInfo);
        }
        $redirect_uri=urlencode(route('callback'));
        $this->getCode($redirect_uri, 1 ,'snsapi_base');
    }

    public function hasMember($openid,$userInfo){
        $member = Member::query()->where('openid',$openid)->first();
        if(!$member){
            $data = [
                'card'=>'',
                'wxname'=>$userInfo->nickname,
                'wxsex'=>$userInfo->sex,
                'openid'=>$openid,
                'headimg'=>$userInfo->headimgurl,
                'phone'=>'',
            ];
            $member = Member::create($data);
        }
        $data = [
            'state'=>true,
            'errcode'=>'',
            'errmsg'=>'',
            'result'=>['openid'=>$member->openid,'user_id'=>$member->id]
        ];
        return response()->json($data);
    }
}