<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-12
 * Time: 下午11:49
 */

namespace Home\Controller;
use Lib\Jiaowu\Curl;
use Lib\Jiaowu\Schedule;
use Lib\Jiaowu\Grade;
use Lib\Jiaowu\RankExam;
use Lib\Jiaowu\MakeupExam;
use Lib\Jiaowu\UserInfo;
use Home\Controller\WechatHandle;
use Lib\Wechat\WechatAuth;
use Lib\Jiaowu\UserImg;
use Lib\Jiaowu\ArrayAnalysis;
use Lib\Jiaowu\FreeClassroom;
use Lib\Jiaowu\ChooseClass;
use Lib\Jiaowu\GetApiData;
use Lib\Jiaowu\Exam;
use Think\Controller;
use Org\Net\Http;
use Think\Page;
use Lib\Tool\Snoopy;

class OkController extends  WechatHandleController
{
    //微信对象
    private $wechatAuth;

    /**
     *
     */
    public function index(){
        header("Content-type:text/html;charset=utf-8");
        //自动更新的时间间隔
        setS('grade_update_time',86400);

//        S('grade_bottom_copy','语音调戏小助手更欢乐～');
        $jwid = 201201148;
        $jwpwd = 'luowei2008';
        $openid = "o3LbBjgr9xeEfPoRRch4HuQ0PYa0";
        $this->wechatAuth();
        S("updateGradeByUser:".$openid,NULL);
        S("updateExamByUser:".$openid,NULL);
        S("updateScheduleByUser:".$openid,NULL);
        S('cookie_201201148',null);
//        S('wechat_access_token',null);

        $curl = new Curl($jwid,$jwpwd);
        $ret = $curl->getLogin();
        var_dump($ret);

        $user =  M()
            ->table('xzs_user u, xzs_user_info ui')
            ->where('u.id = ui.uid and ui.sex = 0 and u.is_bind = 1')
            ->field('u.jwid as jwid, u.jwpwd as jwpwd')
            ->select();
        p($user);



        $ret = Schedule::schedule($jwid,$jwpwd);
        p($ret);

        die;


        $jwid = '201201148';
        $jwpwd = 'luowei2008';

        $url = 'http://202.201.29.194/default_ysdx.aspx';
//        $url = $url = C('JIAOWU_LOGIN_URL');

        $cookie_file = tempnam(SAE_TEMP_PATH,'cookie');

        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $str = curl_exec($ch);
        $info = curl_getinfo($ch);
        if($info['http_code'] != 200)
            throw new \Exception('教务网错误');
        curl_close($ch);


        $c = file_get_contents($cookie_file);
        p($c);
        //获取隐藏域表单值
        $viewstate = self::getHidden($str);

        p($viewstate);

        $r = iconv('utf-8', 'gb2312', base64_decode($viewstate));
        p($r);

        //更改为这种方式 -----2016/01/09
        $post['__VIEWSTATE'] = $viewstate;
        $post['TextBox1'] = $jwid;
        $post['TextBox2'] = $jwpwd;
        $post['RadioButtonList1'] = iconv('utf-8', 'gb2312', '学生');
        $post['Button1'] = iconv('utf-8', 'gb2312', '登录');

        $post = http_build_query($post);
        p($post);


        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
//        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $str=curl_exec($ch);
        $info=curl_getinfo($ch);
        curl_close($ch);
        p($info);
        print_r($str);



        //  TODO 2,对教务数据的请求使用try catch写法，失败尝试在校内服务器获取json数据
        //  TODO 3,完成校内服务器开发----校内？到底要不要呢...
        //  TODO 4,完成验证码识别开发 ----没做
        //  TODO 5,搞定xuanke。ysdx的405错误原因---貌似消失了


    }


    /**
     * @param $str
     * 匹配隐藏域表单值
     */
    public static function getHidden($str){

        $pattern = '/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i';
        preg_match($pattern, $str, $matches);
        $view_size = sizeof($matches);
//         var_dump($view_size);
        if ($view_size > 1) {
            $viewstate = $matches[1];
//            var_dump($viewstate);
        }else{
            throw new \Exception('获取隐藏域值失败！');
        }
        return $viewstate;
    }



    /**
     * 加在WechatAuth类
     */
    private function wechatAuth(){

        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatAuth = new WechatAuth($appid,$secret);

        $this->wechatAuth = $wechatAuth;
    }

    public function menu(){

        $appid = C('WEIXIN.APPID'); //AppID(应用ID)
        $secret = C('WEIXIN.SECRET');
        $wechatAuth = new WechatAuth($appid,$secret);

        $data = '{
             "button":[
              {
                   "name":"教务助手",
                   "sub_button":[
                   {
                       "type":"click",
                       "name":"当日课表",
                       "key":"课表查询"
                   },
                   {
                       "type":"click",
                       "name":"学期成绩",
                       "key":"成绩查询"
                   },
                   {
                       "type":"click",
                       "name":"全部成绩",
                       "key":"全部成绩"
                   },
                   {
                       "type":"click",
                       "name":"空闲教室",
                       "key":"空闲教室"
                   },
                   {
                       "type":"click",
                       "name":"四六级查询",
                       "key":"四六级"
                   }
                   ]
              },
              {
                   "name":"问答社区",
                   "sub_button":[
                   {
                       "type":"view",
                       "name":"帮助",
                       "url":"http://www.lzjtuhand.com/Help"
                   },
                   {
                       "type":"view",
                       "name":"图书馆藏",
                       "url":"http://202.201.19.11:8080/sms/opac/search/showSearch.action?xc=5"
                   },
                   {
                       "type":"view",
                       "name":"问答社区",
                       "url":"http://xiaoqu.qq.com/mobile/barindex.html?_bid=128&_wv=1027&bid=158679"
                   },
                   {
                       "type":"click",
                       "name":"每日一句",
                       "key":"每日一句"
                   }]
              },
              {
                   "name":"大四党专区",
                   "sub_button":[
                   {
                       "type":"view",
                       "name":"宣讲会",
                       "url":"http://job.lzjtuhand.com/careertalk.php"
                   },
                   {
                       "type":"view",
                       "name":"校园招聘会",
                       "url":"http://job.lzjtuhand.com/"
                   }]
              }]
        }';

        $this->wechatAuth();
        $ret = $wechatAuth->createMenu($data);
        p($ret);
    }
}