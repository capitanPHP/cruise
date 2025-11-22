<?php
/************************************************************************
 ______            _ __              ____  __  ______ 
/ ____/___ _____  (_) /_____ _____  / __ / / / / __ \
/ /   / __ `/ __ / / __/ __ `/ __ / /_/ / /_/ / /_/ /
/ /___/ /_/ / /_/ / / /_/ /_/ / / / / ____/ __  / ____/ 
\____/\__,_/ .___/_/\__/\__,_/_/ /_/_/   /_/ /_/_/
            /_/
*************************************************************************
* @file Login.php
*************************************************************************
* This file is part of the CapitanPHP framework.
*************************************************************************
* Copyright (c) 2025 CapitanPHP.
*************************************************************************
* Licensed (https://opensource.org/license/MIT)
*************************************************************************
* Author: ⛵️⛵️⛵️capitan <capitanPHP@outlook.com>
**************************************************************************/
declare(strict_types=1);
namespace capitan\cruise\w\echat;
class Login {
   
    private $config = [
        'app_id' => 'APPID',
        'app_secret' => 'App Secret',
        'redirect_uri' => 'https://your-domain.com/wechat/callback.php',
        'scope' => 'snsapi_userinfo',
    ];

   
    const AUTH_URL = 'https://open.weixin.qq.com/connect/qrconnect';
    const TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const USER_INFO_URL = 'https://api.weixin.qq.com/sns/userinfo';
    const TOKEN_CHECK_URL = 'https://api.weixin.qq.com/sns/auth';

   
    public function __construct(?array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
       
        $this->config['redirect_uri'] = urlencode($this->config['redirect_uri']);
    }
   
    public function getAuthUrl(string $state = 'wechat_login') : string
    {
        $params = [
            'appid' => $this->config['app_id'],
            'redirect_uri' => $this->config['redirect_uri'],
            'response_type' => 'code',
            'scope' => $this->config['scope'],
            'state' => $state,
            'connect_redirect' => 1,
        ];
        return self::AUTH_URL . '?' . http_build_query($params) . '#wechat_redirect';
    }

   
    public function getAccessToken(string $code) : array
    {
        $params = [
            'appid' => $this->config['app_id'],
            'secret' => $this->config['app_secret'],
            'code' => $code,
            'grant_type' => 'authorization_code',
        ];
        $response = $this->httpGet(self::TOKEN_URL, $params);
        $result = json_decode($response, true);

       
        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return ['error' => true, 'msg' => $result['errmsg'], 'errcode' => $result['errcode']];
        }
        return ['error' => false, 'data' => $result];
    }

   
    public function getUserInfo(string $access_token, string $openid, string $lang = 'zh_CN') : array
    {
        $params = [
            'access_token' => $access_token,
            'openid' => $openid,
            'lang' => $lang,
        ];
        $response = $this->httpGet(self::USER_INFO_URL, $params);
        $result = json_decode($response, true);

        if (isset($result['errcode']) && $result['errcode'] != 0) {
            return ['error' => true, 'msg' => $result['errmsg'], 'errcode' => $result['errcode']];
        }
        return ['error' => false, 'data' => $result];
    }

   
    public function checkToken(string $access_token, string $openid) : bool
    {
        $params = [
            'access_token' => $access_token,
            'openid' => $openid,
        ];
        $response = $this->httpGet(self::TOKEN_CHECK_URL, $params);
        $result = json_decode($response, true);
        return $result['errcode'] == 0 ? true : false;
    }

   
    private function httpGet(string $url, array $params = []) : string
    {
        $url = $url . (empty($params) ? '' : '?' . http_build_query($params));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 超时10秒
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过SSL验证（生产环境建议开启）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}