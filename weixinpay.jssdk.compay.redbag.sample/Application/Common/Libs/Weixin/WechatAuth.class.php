<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: ����� <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Common\Libs\Weixin;

class WechatAuth {

    /* ��Ϣ���ͳ��� */
    const MSG_TYPE_TEXT       = 'text';
    const MSG_TYPE_IMAGE      = 'image';
    const MSG_TYPE_VOICE      = 'voice';
    const MSG_TYPE_VIDEO      = 'video';
    const MSG_TYPE_SHORTVIDEO = 'shortvideo';
    const MSG_TYPE_LOCATION   = 'location';
    const MSG_TYPE_LINK       = 'link';
    const MSG_TYPE_MUSIC      = 'music';
    const MSG_TYPE_NEWS       = 'news';
    const MSG_TYPE_EVENT      = 'event';
    
    /* ��ά�����ͳ��� */
    const QR_SCENE       = 'QR_SCENE';
    const QR_LIMIT_SCENE = 'QR_LIMIT_SCENE';

    /**
     * ΢�ſ����������appID
     * @var string
     */
    private $appId = '';

    /**
     * ΢�ſ����������appSecret
     * @var string
     */
    private $appSecret = '';

    /**
     * ��ȡ����access_token
     * @var string
     */
    private $accessToken = '';

    /**
     * ΢��api��·��
     * @var string
     */
    private $apiURL = 'https://api.weixin.qq.com/cgi-bin';

    /**
     * ΢�Ŷ�ά���·��
     * @var string
     */
    private $qrcodeURL = 'https://mp.weixin.qq.com/cgi-bin';

    private $requestCodeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    private $oauthApiURL = 'https://api.weixin.qq.com/sns';

    /**
     * ���췽��������΢�Ÿ߼��ӿ�ʱʵ����SDK
     * @param string $appid  ΢��appid
     * @param string $secret ΢��appsecret
     * @param string $token  ��ȡ����access_token
     */
    public function __construct($appid, $secret, $token = null){
        if($appid && $secret){
            $this->appId     = $appid;
            $this->appSecret = $secret;

            if(!empty($token)){
                $this->accessToken = $token;
            }
        } else {
            throw new \Exception('ȱ�ٲ��� APP_ID �� APP_SECRET!');
        }
    }

    public function getRequestCodeURL($redirect_uri, $state = null,
        $scope = 'snsapi_userinfo'){
        
        $query = array(
            'appid'         => $this->appId,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => $scope,
            );

        if(!is_null($state) && preg_match('/[a-zA-Z0-9]+/', $state)){
            $query['state'] = $state;
        }

        $query = http_build_query($query);
        return "{$this->requestCodeURL}?{$query}#wechat_redirect";
    }

    /**
     * ��ȡaccess_token�����ں����ӿڷ���
     * @return array access_token��Ϣ������ token ����Ч��
     */
    public function getAccessToken($type = 'client', $code = null){
        $param = array(
            'appid'  => $this->appId,
            'secret' => $this->appSecret
            );

        switch ($type) {
            case 'client':
            $param['grant_type'] = 'client_credential';
            $url = "{$this->apiURL}/token";
            break;

            case 'code':
            $param['code'] = $code;
            $param['grant_type'] = 'authorization_code';
            $url = "{$this->oauthApiURL}/oauth2/access_token";
            break;
            
            default:
            throw new \Exception('��֧�ֵ�grant_type���ͣ�');
            break;
        }

        $token = self::http($url, $param);
        $token = json_decode($token, true);

        if(is_array($token)){
            if(isset($token['errcode'])){
                throw new \Exception($token['errmsg']);
            } else {
                $this->accessToken = $token['access_token'];
                return $token;
            }
        } else {
            throw new \Exception('��ȡ΢��access_tokenʧ�ܣ�');
        }
    }

    // +----------------------------------------------------------------------
    // | Author: �Ȳ�����һ�� ��Ⱥ��366504956  ����thinkphp��΢�ſ�����
    // | ���� GetOpenid(),get_url()
    // +----------------------------------------------------------------------

    /**
     * @return �û���openid
     */
    public function GetOpenid($scope = 'snsapi_base'){
        //ͨ��code���openid
        if (!isset($_GET['code'])){
            //����΢�ŷ���code��
            $baseUrl = $this->get_url();
            $state = null;
            $scope ? $url = $this->getRequestCodeURL($baseUrl,$state,$scope) :$url = $this->getRequestCodeURL($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //��ȡcode�룬�Ի�ȡopenid
            $code = $_GET['code'];
            $type = 'code';
            $access_token = $this->getAccessToken($type, $code);
            return $access_token['openid'];
        }
    }

    /**
     * @return ��ǰurl
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }


    /**
     * ��ȡ��Ȩ�û���Ϣ
     * @param  string $openid �û���OpenID
     * @param  string $lang   ָ��������
     * @return array          �û���Ϣ���ݣ�����μ�΢���ĵ�
     */
    public function getUserInfo($openid, $lang = 'zh_CN'){
        $query = array(
            'access_token' => $this->accessToken,
            'openid'       => $openid,
            'lang'         => $lang,
            );

        $info = self::http("{$this->oauthApiURL}/userinfo", $query);
        return json_decode($info, true);
    }

    /**
     * �ϴ���ʱý����Դ
     * @param  string $filename ý����Դ����·��
     * @param  string $type     ý����Դ���ͣ�������ο�΢�ſ����ֲ�
     */
    public function mediaUpload($filename, $type){
        $filename = realpath($filename);
        if(!$filename) throw new \Exception('��Դ·������');
        
        $data  = array(
            'type'  => $type,
            'media' => "@{$filename}"
            );

        return $this->api('media/upload', $data, 'POST', '', false);
    }

    /**
     * �ϴ�����ý����Դ
     * @param string $filename    ý����Դ����·��
     * @param string $type        ý����Դ���ͣ�������ο�΢�ſ����ֲ�
     * @param string $description ��Դ����������Դ����Ϊ video ʱ��Ч
     */
    public function materialAddMaterial($filename, $type, $description = ''){
        $filename = realpath($filename);
        if(!$filename) throw new \Exception('��Դ·������');
        
        $data = array(
            'type'  => $type,
            'media' => "@{$filename}",
            );

        if($type == 'video'){
            if(is_array($description)){
                //�������ģ�΢��api��֧������ת���json�ṹ
                array_walk_recursive($description, function(&$value){
                    $value = urlencode($value);
                });
                $description = urldecode(json_encode($description));
            }
            $data['description'] = $description;
        }
        return $this->api('material/add_material', $data, 'POST', '', false);
    }

    /**
     * ��ȡý����Դ���ص�ַ
     * ע�⣺��Ƶ��Դ����������
     * @param  string $media_id ý����Դid
     * @return string           ý����Դ���ص�ַ
     */
    public function mediaGet($media_id){
        $param = array(
            'access_token' => $this->accessToken,
            'media_id'     => $media_id
            );

        $url = "{$this->apiURL}/media/get?";
        return $url . http_build_query($param);
    }

    /**
     * ��ָ���û�������Ϣ
     * ע�⣺΢�Ź���ֻ�������48Сʱ�ڸ�����ƽ̨���͹���Ϣ���û�������Ϣ
     * @param  string $openid  �û���openid
     * @param  array  $content ���͵����ݣ���ͬ���͵����ݽṹ���ܲ�ͬ
     * @param  string $type    ������Ϣ����
     */
    public function messageCustomSend($openid, $content, $type = self::MSG_TYPE_TEXT){

        //��������
        $data = array(
            'touser'=>$openid,
            'msgtype'=>$type,
            );

        //�������͸��Ӷ�������
        $data[$type] = call_user_func(array(self, $type), $content);

        return $this->api('message/custom/send', $data);
    }

    /**
     * �����ı���Ϣ
     * @param  string $openid �û���openid
     * @param  string $text   ���͵�����
     */
    public function sendText($openid, $text){
        return $this->messageCustomSend($openid, $text, self::MSG_TYPE_TEXT);
    }

    /**
     * ����ͼƬ��Ϣ
     * @param  string $openid �û���openid
     * @param  string $media  ͼƬID
     */
    public function sendImage($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_IMAGE);
    }

    /**
     * ����������Ϣ
     * @param  string $openid �û���openid
     * @param  string $media  ��ƵID
     */
    public function sendVoice($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_VOICE);
    }

    /**
     * ������Ƶ��Ϣ
     * @param  string $openid      �û���openid
     * @param  string $media_id    ��ƵID
     * @param  string $title       ��Ƶ����
     * @param  string $discription ��Ƶ����
     */
    public function sendVideo(){
        $video  = func_get_args();
        $openid = array_shift($video);
        return $this->messageCustomSend($openid, $video, self::MSG_TYPE_VIDEO);
    }

    /**
     * ����������Ϣ
     * @param  string $openid         �û���openid
     * @param  string $title          ���ֱ���
     * @param  string $discription    ��������
     * @param  string $musicurl       ��������
     * @param  string $hqmusicurl     ��Ʒ����������
     * @param  string $thumb_media_id ����ͼID
     */
    public function sendMusic(){
        $music  = func_get_args();
        $openid = array_shift($music);
        return $this->messageCustomSend($openid, $music, self::MSG_TYPE_MUSIC);
    }

    /**
     * ����ͼ����Ϣ
     * @param  string $openid �û���openid
     * @param  array  $news   ͼ������ [���⣬������URL������ͼ]
     * @param  array  $news1  ͼ������ [���⣬������URL������ͼ]
     * @param  array  $news2  ͼ������ [���⣬������URL������ͼ]
     *                ...     ...
     * @param  array  $news9  ͼ������ [���⣬������URL������ͼ]
     */
    public function sendNews(){
        $news   = func_get_args();
        $openid = array_shift($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * ����һ��ͼ����Ϣ
     * @param  string $openid      �û���openid
     * @param  string $title       ���±���
     * @param  string $discription ���¼��
     * @param  string $url         ��������
     * @param  string $picurl      ��������ͼ
     */
    public function sendNewsOnce(){
        $news   = func_get_args();
        $openid = array_shift($news);
        $news   = array($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * �����û���
     * @param  string $name ������
     */
    public function groupsCreate($name){
        $data = array('group' => array('name' => $name));
        return $this->api('groups/create', $data);
    }

    /**
     * ��ѯ���з���
     * @return array �����б�
     */
    public function groupsGet(){
        return $this->api('groups/get', '', 'GET');
    }

    /**
     * ��ѯ�û����ڵķ���
     * @param  string $openid �û���OpenID
     * @return number         ����ID
     */
    public function groupsGetid($openid){
        $data = array('openid' => $openid);
        return $this->api('groups/getid', $data);
    }

    /**
     * �޸ķ���
     * @param  number $id   ����ID
     * @param  string $name ��������
     * @return array        �޸ĳɹ���ʧ����Ϣ
     */
    public function groupsUpdate($id, $name){
        $data = array('id' => $id, 'name' => $name);
        return $this->api('groups/update', $data);
    }

    /**
     * �ƶ��û�����
     * @param  string $openid     �û���OpenID
     * @param  number $to_groupid Ҫ�ƶ����ķ���ID
     * @return array              �ƶ��ɹ���ʧ����Ϣ
     */
    public function groupsMemberUpdate($openid, $to_groupid){
        $data = array('openid' => $openid, 'to_groupid' => $to_groupid);
        return $this->api('groups/member/update', $data);
    }

    /**
     * �û��豸ע��
     * @param  string $openid �û���OpenID
     * @param  string $remark �豸ע��
     * @return array          ִ�гɹ�ʧ����Ϣ
     */
    public function userInfoUpdateremark($openid, $remark){
        $data = array('openid' => $openid, 'remark' => $remark);
        return $this->api('user/info/updateremark', $data);
    }

    /**
     * ��ȡָ���û�����ϸ��Ϣ
     * @param  string $openid �û���openid
     * @param  string $lang   ��Ҫ��ȡ���ݵ�����
     */
    public function userInfo($openid, $lang = 'zh_CN'){
        $param = array('openid' => $openid, 'lang' => $lang);
        return $this->api('user/info', '', 'GET', $param);
    }

    /**
     * ��ȡ��ע���б�
     * @param  string $next_openid ��һ��openid�����û�������10000ʱ��Ч
     * @return array               �û��б�
     */
    public function userGet($next_openid = ''){
        $param = array('next_openid' => $next_openid);
        return $this->api('user/get', '', 'GET', $param);
    }

    /**
     * �����Զ���˵�
     * @param  array $button ���Ϲ���Ĳ˵����飬����μ�΢���ֲ�
     */
    public function menuCreate($button){
        $data = array('button' => $button);
        return $this->api('menu/create', $data);
    }

    /**
     * ��ȡ���е��Զ���˵�
     * @return array  �Զ���˵�����
     */
    public function menuGet(){
        return $this->api('menu/get', '', 'GET');
    }

    /**
     * ɾ���Զ���˵�
     */
    public function menuDelete(){
        return $this->api('menu/delete', '', 'GET');
    }

    /**
     * ������ά�룬�ɴ���ָ����Ч�ڵĶ�ά������ö�ά��
     * @param  integer $scene_id       ��ά�����
     * @param  integer $expire_seconds ��ά����Ч�ڣ�0-������Ч
     */
    public function qrcodeCreate($scene_id, $expire_seconds = 0){
        $data = array();

        if(is_numeric($expire_seconds) && $expire_seconds > 0){
            $data['expire_seconds'] = $expire_seconds;
            $data['action_name']    = self::QR_SCENE;
        } else {
            $data['action_name']    = self::QR_LIMIT_SCENE;
        }

        $data['action_info']['scene']['scene_id'] = $scene_id;
        return $this->api('qrcode/create', $data);
    }

    /**
     * ����ticket��ȡ��ά��URL
     * @param  string $ticket ͨ�� qrcodeCreate�ӿڻ�ȡ����ticket
     * @return string         ��ά��URL
     */
    public function showqrcode($ticket){
        return "{$this->qrcodeURL}/showqrcode?ticket={$ticket}";
    }

    /**
     * ������ת������
     * @param  string $long_url ������
     * @return string           ������
     */
    public function shorturl($long_url){
        $data = array(
            'action'   => 'long2short',
            'long_url' => $long_url
            );

        return $this->api('shorturl', $data);
    }

    /**
     * ����΢��api��ȡ��Ӧ����
     * @param  string $name   API����
     * @param  string $data   POST��������
     * @param  string $method ����ʽ
     * @param  string $param  GET�������
     * @return array          api���ؽ��
     */
    protected function api($name, $data = '', $method = 'POST', $param = '', $json = true){
        $params = array('access_token' => $this->accessToken);

        if(!empty($param) && is_array($param)){
            $params = array_merge($params, $param);
        }

        $url  = "{$this->apiURL}/{$name}";
        if($json && !empty($data)){
            //�������ģ�΢��api��֧������ת���json�ṹ
            array_walk_recursive($data, function(&$value){
                $value = urlencode($value);
            });
            $data = urldecode(json_encode($data));
        }
        
        $data = self::http($url, $params, $data, $method);
        return json_decode($data, true);
    }


    /**
    *ģ����Ϣ
    * @param  array  $data ģ������
    **/
    public function templateSend($data){        
        return $this->api('message/template/send', $data);
    }

    /**
     * ����HTTP���󷽷���Ŀǰֻ֧��CURL��������
     * @param  string $url    ����URL
     * @param  array  $param  GET��������
     * @param  array  $data   POST�����ݣ�GET����ʱ�ò�����Ч
     * @param  string $method ���󷽷�GET/POST
     * @return array          ��Ӧ����
     */
    protected static function http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            );

        /* �����������������ض����� */
        $opts[CURLOPT_URL] = $url . '?' . http_build_query($param);

        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;
            
            if(is_string($data)){ //����JSON����
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',  
                    'Content-Length: ' . strlen($data),
                    );
            }
        }

        /* ��ʼ����ִ��curl���� */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        //���������׳��쳣
        if($error) throw new \Exception('����������' . $error);

        return  $data;
    }

    /**
     * �����ı���Ϣ
     * @param  string $content Ҫ�ظ����ı�
     */
    private static function text($content){
        $data['content'] = $content;
        return $data;
    }

    /**
     * ����ͼƬ��Ϣ
     * @param  integer $media ͼƬID
     */
    private static function image($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * ������Ƶ��Ϣ
     * @param  integer $media ����ID
     */
    private static function voice($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * ������Ƶ��Ϣ
     * @param  array $video Ҫ�ظ�����Ƶ [��ƵID�����⣬˵��]
     */
    private static function video($video){
        $data = array();
        list(
            $data['media_id'],
            $data['title'], 
            $data['description'], 
            ) = $video;

        return $data;
    }

    /**
     * ����������Ϣ
     * @param  array $music Ҫ�ظ�������[���⣬˵�������ӣ���Ʒ�����ӣ�����ͼID]
     */
    private static function music($music){
        $data = array();
        list(
            $data['title'], 
            $data['description'], 
            $data['musicurl'], 
            $data['hqmusicurl'],
            $data['thumb_media_id'],
            ) = $music;

        return $data;
    }

    /**
     * ����ͼ����Ϣ
     * @param  array $news Ҫ�ظ���ͼ������
     * [    
     *      0 => ��һ��ͼ����Ϣ[���⣬˵����ͼƬ���ӣ�ȫ������]��
     *      1 => �ڶ���ͼ����Ϣ[���⣬˵����ͼƬ���ӣ�ȫ������]��
     *      2 => ������ͼ����Ϣ[���⣬˵����ͼƬ���ӣ�ȫ������]�� 
     * ]
     */
    private static function news($news){
        $articles = array();
        foreach ($news as $key => $value) {
            list(
                $articles[$key]['title'],
                $articles[$key]['description'],
                $articles[$key]['url'],
                $articles[$key]['picurl']
                ) = $value;

            if($key >= 9) break; //���ֻ����10��ͼ����Ϣ
        }

        $data['articles']     = $articles;
        return $data;
    }

}
