<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-3
 * Time: 下午12:51
 */
namespace Lib\Wechat;

class WechatAuth {

    /* 消息类型常量 */
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

    /* 二维码类型常量 */
    const QR_SCENE       = 'QR_SCENE';
    const QR_LIMIT_SCENE = 'QR_LIMIT_SCENE';

    /**
     * 微信开发者申请的appID
     * @var string
     */
    private $appId = '';

    /**
     * 微信开发者申请的appSecret
     * @var string
     */
    private $appSecret = '';

    /**
     * 微信api根路径
     * @var string
     */
    private $apiURL = 'https://api.weixin.qq.com/cgi-bin';

    /**
     * 微信二维码根路径
     * @var string
     */
    private $qrcodeURL = 'https://mp.weixin.qq.com/cgi-bin';

    private $requestCodeURL = 'https://open.weixin.qq.com/connect/oauth2/authorize';

    private $oauthApiURL = 'https://api.weixin.qq.com/sns';

    /**
     * 构造方法，调用微信高级接口时实例化SDK
     * @param string $appid  微信appid
     * @param string $secret 微信appsecret
     */
    public function __construct($appid, $secret){
        if($appid && $secret){
            $this->appId     = $appid;
            $this->appSecret = $secret;
        } else {
            throw new \Exception('缺少参数 APP_ID 和 APP_SECRET!');
        }
    }

    /**
     * 获取微信Access_Token
     * @$type  默认为client 表示基础接口中的access_token
     * @$type = 'code'  Auth2.0接口中网页授权的access_token
     * @$code 为网页授权第一步所获取到的code
     */
    public function getAccessToken(){

        //检测本地是否已经拥有access_token，并且检测access_token是否过期
        $accessToken = S("wechat_access_token");
        if($accessToken === false){
            $accessToken = $this->_getAccessToken();
        }
        return $accessToken;
    }
    /**
     * @descrpition 从微信服务器获取微信ACCESS_TOKEN
     * @return Ambigous|bool
     */
    private function _getAccessToken(){
        $param = array(
            'appid'  => $this->appId,
            'secret' => $this->appSecret
        );
        $param['grant_type'] = 'client_credential';
        $url = "{$this->apiURL}/token";
        $accessToken = self::http($url,$param);
        $accessToken = json_decode($accessToken, true);
        if(!isset($accessToken['access_token'])){
            throw new \Exception('accessToken不存在！');
        }
        //缓存，预留20s延迟时间
        S('wechat_access_token',$accessToken['access_token'],7180);

        return $accessToken['access_token'];
    }

    /**
     * @descrpition 通过OpenID来获取用户基本信息
     * @param $openId 用户唯一OpenId
     * @return JSON {
    "subscribe": 1,    //用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息
    "openid": "o6_bmjrPTlm6_2sgVt7hMZOPfL2M",
    "nickname": "Band",
    "sex": 1,          //用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
    "language": "zh_CN",
    "city": "广州",
    "province": "广东",
    "country": "中国",
    "headimgurl":    "http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/0",
    "subscribe_time": 1382694957
    }
     */
    public function getUserBaseInfo($openId){
        //获取ACCESS_TOKEN
        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/user/info';
        $param = array(
            'access_token' => $accessToken,
            'openid' => $openId,
        );
        return self::http($queryUrl,$param);
    }

    /**
     * @descrpition 获取关注者列表
     * @param $next_openid 第一个拉取的OPENID，不填默认从头开始拉取
     * @return JSON {"total":2,"count":2,"data":{"openid":["OPENID1","OPENID2"]},"next_openid":"NEXT_OPENID"}
     */
    public function getFansList($next_openid=''){
        //获取ACCESS_TOKEN
        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/user/get';
        if(empty($next_openid)){
            $param = array(
                'access_token' => $accessToken,
            );
        }else{
            $param = array(
                'access_token' => $accessToken,
                'next_openid' => $next_openid,
            );
        }
        return self::http($queryUrl,$param);
    }

    /**
     * 设置备注名 开发者可以通过该接口对指定用户设置备注名，该接口暂时开放给微信认证的服务号。
     * @param $openId 用户的openId
     * @param $remark 新的昵称
     * @return array('errorcode'=>0, 'errmsg'=>'ok') 正常时是0
    }

     */
    public function setRemark($openId, $remark){
        //获取ACCESS_TOKEN
        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/user/info/updateremark';
        $param = array(
            'access_token' => $accessToken,
        );
        $data = json_encode(array('openid'=>$openId, 'remark'=>$remark));
        return self::http($queryUrl,$param,$data,'POST');
    }

    /**
     * 向用户推送模板消息----由于需要AccessToken，故写在这个类
     * @param $data = array(
     *                  'first'=>array('value'=>'您好，您已成功消费。', 'color'=>'#0A0A0A')
     *                  'keynote1'=>array('value'=>'巧克力', 'color'=>'#CCCCCC')
     *                  'keynote2'=>array('value'=>'39.8元', 'color'=>'#CCCCCC')
     *                  'keynote3'=>array('value'=>'2014年9月16日', 'color'=>'#CCCCCC')
     *                  'keynote3'=>array('value'=>'欢迎再次购买。', 'color'=>'#173177')
     * );
     * @param $touser 接收方的OpenId。
     * @param $templateId 模板Id。在公众平台线上模板库中选用模板获得ID
     * @param $url URL
     * @param string $topcolor 顶部颜色， 可以为空。默认是红色
     * @return array("errcode"=>0, "errmsg"=>"ok", "msgid"=>200228332} "errcode"是0则表示没有出错
     *
     * 注意：推送后用户到底是否成功接受，微信会向公众号推送一个消息。
     */
    public function sendTemplateMessage($data, $touser, $templateId, $url, $topcolor='#FF0000'){
        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/message/template/send';
        $param = array(
            'access_token' => $accessToken,
        );
        $template = array();
        $template['touser'] = $touser;
        $template['template_id'] = $templateId;
        $template['url'] = $url;
        $template['topcolor'] = $topcolor;
        $template['data'] = $data;
        $template = json_encode($template);
        return self::http($queryUrl,$param,$template,'POST');
    }

    /**
     * Description: 网页授权获取第一步----获取CODE
     * @param $scope snsapi_base不弹出授权页面，只能获得OpenId;snsapi_userinfo弹出授权页面，可以获得所有信息
     * 将会跳转到redirect_uri/?code=CODE&state=STATE 通过GET方式获取code和state
     */
    public function getRequestCodeURL($redirect_uri, $state = null,$scope = 'snsapi_userinfo'){
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
     * Description: 通过code换取网页授权access_token---网页授权第二步
     * 首先请注意，这里通过code换取的网页授权access_token,与基础支持中的access_token不同。
     * 公众号可通过下述接口来获取网页授权access_token。
     * 如果网页授权的作用域为snsapi_base，则本步骤中获取到网页授权access_token的同时，也获取到了openid，snsapi_base式的网页授权流程即到此为止。
     * @param $code getRequestCodeURL()获取的code参数
     *
     * @return Array(access_token, expires_in, refresh_token, openid, scope)
     */
    public function getAccessTokenAndOpenId($code){
        //填写为authorization_code
        $grant_type = 'authorization_code';
        $appid = $this->appId;
        $secret = $this->appSecret;
        $queryUrl = $this->oauthApiURL.'/oauth2/access_token';
        $param = array(
            'appid' => $appid,
            'secret' => $secret,
            'code' =>$code,
            'grant_type' => $grant_type,
        );
        $result =  self::http($queryUrl,$param);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 刷新access_token（如果需要）---网页授权第三步
     * 由于access_token拥有较短的有效期，当access_token超时后，可以使用refresh_token进行刷新，refresh_token拥有较长的有效期（7天、30天、60天、90天），当refresh_token失效的后，需要用户重新授权。
     * @param $refreshToken 通过本类的第二个方法getAccessTokenAndOpenId可以获得一个数组，数组中有一个字段是refresh_token，就是这里的参数
     *
     * @return array(
        "access_token"=>"网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同",
        "expires_in"=>access_token接口调用凭证超时时间，单位（秒）,
        "refresh_token"=>"用户刷新access_token",
        "openid"=>"用户唯一标识",
        "scope"=>"用户授权的作用域，使用逗号（,）分隔")
     */
    public function refreshToken($refreshToken){
        $queryUrl = $this->oauthApiURL.'/oauth2/refresh_token';
        $appid = $this->appId;
        $param = array(
            'appid' => $appid,
            'grant_type' => 'refresh_token',
            'refresh_token' =>$refreshToken,
        );
        $result =  self::http($queryUrl,$param);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 拉取用户信息(需scope为 snsapi_userinfo) ---网页授权第四步
     * 如果网页授权作用域为snsapi_userinfo，则此时开发者可以通过access_token和openid拉取用户信息了。
     * @param $accessToken 网页授权接口调用凭证。通过本类的第二个方法getAccessTokenAndOpenId可以获得一个数组，数组中有一个字段是access_token，就是这里的参数。注意：此access_token与基础支持的access_token不同
     * @param $openId 用户的唯一标识
     * @param $lang 返回国家地区语言版本，zh_CN 简体，zh_TW 繁体，en 英语
     *
     * @return array("openid"=>"用户的唯一标识",
        "nickname"=>'用户昵称',
        "sex"=>"1是男，2是女，0是未知",
        "province"=>"用户个人资料填写的省份"
        "city"=>"普通用户个人资料填写的城市",
        "country"=>"国家，如中国为CN",
        //户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空
        "headimgurl"=>"http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
        //用户特权信息，json 数组，如微信沃卡用户为chinaunicom
        "privilege"=>array("PRIVILEGE1", "PRIVILEGE2"),
        );
     */
    public function getUserInfo($accessToken, $openId, $lang='zh_CN'){
        $queryUrl = $this->oauthApiURL.'/userinfo';
        $param = array(
            'access_token' => $accessToken,
            'openid' => $openId,
            'lang' =>$lang,
        );
        $result =  self::http($queryUrl,$param);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 检验授权凭证（access_token）是否有效 ----网页授权附
     * @param $accessToken 网页授权接口调用凭证。通过本类的方法getAccessTokenAndOpenId可以获得一个数组，数组中有一个字段是access_token，就是这里的参数。注意：此access_token与基础支持的access_token不同
     * @param $openId
     * @return array("errcode"=>0,"errmsg"=>"ok")
     */
    public function checkAccessToken($accessToken, $openId){
        $queryUrl = $this->oauthApiURL.'/auth';
        $param = array(
            'access_token' => $accessToken,
            'openid' => $openId,
        );
        $result =  self::http($queryUrl,$param);
        $result = json_decode($result,true);
        return $result;
    }
    /***************************************************************/


    /**
     * 上传临时媒体资源
     * @param  string $filename 媒体资源本地路径
     * @param  string $type     媒体资源类型，具体请参考微信开发手册
     */
    public function mediaUpload($filename, $type){
        $filename = realpath($filename);
        if(!$filename) throw new \Exception('资源路径错误！');

        $data  = array(
            'type'  => $type,
            'media' => "@{$filename}"
        );

        return $this->api('media/upload', $data, 'POST', '', false);
    }

    /**
     * 上传永久媒体资源
     * @param string $filename    媒体资源本地路径
     * @param string $type        媒体资源类型，具体请参考微信开发手册
     * @param string $description 资源描述，仅资源类型为 video 时有效
     */
    public function materialAddMaterial($filename, $type, $description = ''){
        $filename = realpath($filename);
        if(!$filename) throw new \Exception('资源路径错误！');

        $data = array(
            'type'  => $type,
            'media' => "@{$filename}",
        );

        if($type == 'video'){
            if(is_array($description)){
                //保护中文，微信api不支持中文转义的json结构
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
     * 获取媒体资源下载地址
     * 注意：视频资源不允许下载
     * @param  string $media_id 媒体资源id
     * @return string           媒体资源下载地址
     */
    public function mediaGet($media_id){
        $accessToken = $this->getAccessToken();
        $param = array(
            'access_token' => $accessToken,
            'media_id'     => $media_id
        );

        $url = "{$this->apiURL}/media/get?";
        return $url . http_build_query($param);
    }

    /**
     * 给指定用户推送信息
     * 注意：微信规则只允许给在48小时内给公众平台发送过消息的用户推送信息
     * @param  string $openid  用户的openid
     * @param  array  $content 发送的数据，不同类型的数据结构可能不同
     * @param  string $type    推送消息类型
     */
    public function messageCustomSend($openid, $content, $type = self::MSG_TYPE_TEXT){

        //基础数据
        $data = array(
            'touser'=>$openid,
            'msgtype'=>$type,
        );

        //根据类型附加额外数据
        $data[$type] = call_user_func(array(self, $type), $content);

        return $this->api('message/custom/send', $data);
    }

    /**
     * 发送文本消息
     * @param  string $openid 用户的openid
     * @param  string $text   发送的文字
     */
    public function sendText($openid, $text){
        return $this->messageCustomSend($openid, $text, self::MSG_TYPE_TEXT);
    }

    /**
     * 发送图片消息
     * @param  string $openid 用户的openid
     * @param  string $media  图片ID
     */
    public function sendImage($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_IMAGE);
    }

    /**
     * 发送语音消息
     * @param  string $openid 用户的openid
     * @param  string $media  音频ID
     */
    public function sendVoice($openid, $media){
        return $this->messageCustomSend($openid, $media, self::MSG_TYPE_VOICE);
    }

    /**
     * 发送视频消息
     * @param  string $openid      用户的openid
     * @param  string $media_id    视频ID
     * @param  string $title       视频标题
     * @param  string $discription 视频描述
     */
    public function sendVideo(){
        $video  = func_get_args();
        $openid = array_shift($video);
        return $this->messageCustomSend($openid, $video, self::MSG_TYPE_VIDEO);
    }

    /**
     * 发送音乐消息
     * @param  string $openid         用户的openid
     * @param  string $title          音乐标题
     * @param  string $discription    音乐描述
     * @param  string $musicurl       音乐链接
     * @param  string $hqmusicurl     高品质音乐链接
     * @param  string $thumb_media_id 缩略图ID
     */
    public function sendMusic(){
        $music  = func_get_args();
        $openid = array_shift($music);
        return $this->messageCustomSend($openid, $music, self::MSG_TYPE_MUSIC);
    }

    /**
     * 发送图文消息
     * @param  string $openid 用户的openid
     * @param  array  $news   图文内容 [标题，描述，URL，缩略图]
     * @param  array  $news1  图文内容 [标题，描述，URL，缩略图]
     * @param  array  $news2  图文内容 [标题，描述，URL，缩略图]
     *                ...     ...
     * @param  array  $news9  图文内容 [标题，描述，URL，缩略图]
     */
    public function sendNews(){
        $news   = func_get_args();
        $openid = array_shift($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * 发送一条图文消息
     * @param  string $openid      用户的openid
     * @param  string $title       文章标题
     * @param  string $discription 文章简介
     * @param  string $url         文章连接
     * @param  string $picurl      文章缩略图
     */
    public function sendNewsOnce(){
        $news   = func_get_args();
        $openid = array_shift($news);
        $news   = array($news);
        return $this->messageCustomSend($openid, $news, self::MSG_TYPE_NEWS);
    }

    /**
     * 创建用户组
     * @param  string $name 组名称
     */
    public function groupsCreate($name){
        $data = array('group' => array('name' => $name));
        return $this->api('groups/create', $data);
    }

    /**
     * 查询所有分组
     * @return array 分组列表
     */
    public function groupsGet(){
        return $this->api('groups/get', '', 'GET');
    }

    /**
     * 查询用户所在的分组
     * @param  string $openid 用户的OpenID
     * @return number         分组ID
     */
    public function groupsGetid($openid){
        $data = array('openid' => $openid);
        return $this->api('groups/getid', $data);
    }

    /**
     * 修改分组
     * @param  number $id   分组ID
     * @param  string $name 分组名称
     * @return array        修改成功或失败信息
     */
    public function groupsUpdate($id, $name){
        $data = array('id' => $id, 'name' => $name);
        return $this->api('groups/update', $data);
    }

    /**
     * 移动用户分组
     * @param  string $openid     用户的OpenID
     * @param  number $to_groupid 要移动到的分组ID
     * @return array              移动成功或失败信息
     */
    public function groupsMemberUpdate($openid, $to_groupid){
        $data = array('openid' => $openid, 'to_groupid' => $to_groupid);
        return $this->api('groups/member/update', $data);
    }

    /**
     * 用户设备注名
     * @param  string $openid 用户的OpenID
     * @param  string $remark 设备注名
     * @return array          执行成功失败信息
     */
    public function userInfoUpdateremark($openid, $remark){
        $data = array('openid' => $openid, 'remark' => $remark);
        return $this->api('user/info/updateremark', $data);
    }

    /**
     * 获取指定用户的详细信息
     * @param  string $openid 用户的openid
     * @param  string $lang   需要获取数据的语言
     */
    public function userInfo($openid, $lang = 'zh_CN'){
        $param = array('access_token'  => $this->getAccessToken(),'openid' => $openid, 'lang' => $lang);
        return $this->api('user/info', '', 'GET', $param);
    }

    /**
     * 获取关注者列表
     * @param  string $next_openid 下一个openid，在用户数大于10000时有效
     * @return array               用户列表
     */
    public function userGet($next_openid = ''){
        $param = array('next_openid' => $next_openid);
        return $this->api('user/get', '', 'GET', $param);
    }

    /**
     * 创建自定义菜单
     * @param  array $button 符合规则的菜单数组，规则参见微信手册
     */
    public function menuCreate($button){
        $data = array('button' => $button);
        return $this->api('menu/create', $data);
    }

    /**
     * @param $button
     * @return array
     * 创建自定义菜单，Json方式
     */
    public function createMenu($button){
        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/menu/create';
        $param = array(
            'access_token' => $accessToken,
        );
        return self::http($queryUrl,$param,$button,'POST');
    }

    /**
     * 获取所有的自定义菜单
     * @return array  自定义菜单数组
     */
    public function menuGet(){
        return $this->api('menu/get', '', 'GET');
    }
    /**
     * 获取微信菜单-------Json
     * @return bool|mixed
     *
     * 返回：{"menu":{"button":[{"type":"click","name":"今日歌曲","key":"V1001_TODAY_MUSIC","sub_button":[]},{"type":"click","name":"歌手简介","key":"V1001_TODAY_SINGER","sub_button":[]},{"name":"菜单","sub_button":[{"type":"view","name":"搜索","url":"http://www.soso.com/","sub_button":[]},{"type":"view","name":"视频","url":"http://v.qq.com/","sub_button":[]},{"type":"click","name":"赞一下我们","key":"V1001_GOOD","sub_button":[]}]}]}}
     */
    public function getMenu(){

        $accessToken = $this->getAccessToken();
        $queryUrl = $this->apiURL.'/menu/get';
        $param = array(
            'access_token' => $accessToken,
        );
        return self::http($queryUrl,$param);
    }

    /**
     * 删除自定义菜单
     */
    public function menuDelete(){
        return $this->api('menu/delete', '', 'GET');
    }

    /**
     * 创建二维码，可创建指定有效期的二维码和永久二维码
     * @param  integer $scene_id       二维码参数
     * @param  integer $expire_seconds 二维码有效期，0-永久有效
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
     * 根据ticket获取二维码URL
     * @param  string $ticket 通过 qrcodeCreate接口获取到的ticket
     * @return string         二维码URL
     */
    public function showqrcode($ticket){
        return "{$this->qrcodeURL}/showqrcode?ticket={$ticket}";
    }

    /**
     * 长链接转短链接
     * @param  string $long_url 长链接
     * @return string           短链接
     */
    public function shorturl($long_url){
        $data = array(
            'action'   => 'long2short',
            'long_url' => $long_url
        );

        return $this->api('shorturl', $data);
    }

    /**
     * 调用微信api获取响应数据
     * @param  string $name   API名称
     * @param  string $data   POST请求数据
     * @param  string $method 请求方式
     * @param  string $param  GET请求参数
     * @return array          api返回结果
     */
    protected function api($name, $data = '', $method = 'POST', $param = '', $json = true){
        $params = array('access_token' => $this->getAccessToken());
        if(!empty($param) && is_array($param)){
            $params = array_merge($params, $param);
        }

        $url  = "{$this->apiURL}/{$name}";
        if($json && !empty($data)){
            //保护中文，微信api不支持中文转义的json结构
            array_walk_recursive($data, function(&$value){
                $value = urlencode($value);
            });
            $data = urldecode(json_encode($data));
        }

        $data = self::http($url, $params, $data, $method);

        return json_decode($data, true);
    }

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url    请求URL
     * @param  array  $param  GET参数数组
     * @param  array  $data   POST的数据，GET请求时该参数无效
     * @param  string $method 请求方法GET/POST
     * @return array          响应数据
     */
    public static function http($url, $param, $data = '', $method = 'GET'){
        $opts = array(
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        /* 根据请求类型设置特定参数 */
        $opts[CURLOPT_URL] = $url . '?' . http_build_query($param);

        if(strtoupper($method) == 'POST'){
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $data;

            if(is_string($data)){ //发送JSON数据
                $opts[CURLOPT_HTTPHEADER] = array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data),
                );
            }
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data  = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        //发生错误，抛出异常
        if($error) throw new \Exception('请求发生错误：' . $error);

        return  $data;
    }

    /**
     * 构造文本信息
     * @param  string $content 要回复的文本
     */
    private static function text($content){
        $data['content'] = $content;
        return $data;
    }

    /**
     * 构造图片信息
     * @param  integer $media 图片ID
     */
    private static function image($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * 构造音频信息
     * @param  integer $media 语音ID
     */
    private static function voice($media){
        $data['media_id'] = $media;
        return $data;
    }

    /**
     * 构造视频信息
     * @param  array $video 要回复的视频 [视频ID，标题，说明]
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
     * 构造音乐信息
     * @param  array $music 要回复的音乐[标题，说明，链接，高品质链接，缩略图ID]
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
     * 构造图文信息
     * @param  array $news 要回复的图文内容
     * [
     *      0 => 第一条图文信息[标题，说明，图片链接，全文连接]，
     *      1 => 第二条图文信息[标题，说明，图片链接，全文连接]，
     *      2 => 第三条图文信息[标题，说明，图片链接，全文连接]，
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

            if($key >= 9) break; //最多只允许10条图文信息
        }

        $data['articles']     = $articles;
        return $data;
    }

}
