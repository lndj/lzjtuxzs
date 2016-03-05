<?php
/**
 * Created by PhpStorm.
 * User: luoning
 * Date: 15-9-12
 * Time: 下午9:55
 */
namespace Lib\Jiaowu;

class Curl{
    //学号
    private $jwid = '';
    //密码
    private $jwpwd = '';

    public function __construct($jwid,$jwpwd){
        $this->jwid = $jwid;
        $this->jwpwd = $jwpwd;
    }

    public function get($url,$param = array()){

        $url = $url.'?'.http_build_query($param);

        $cookie_file = $this->getCookie();

        $ch=curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch,CURLOPT_REFERER,$url);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $str=curl_exec($ch);
        curl_close($ch);

        return $str;
    }
    /**
     * @param $url
     * @param string $data
     * @param array $param
     * @return mixed
     * post数据
     */
    public function post($url,$data = '',$param = array()){

        $url = $url.'?'.http_build_query($param);

        $cookie_file = $this->getCookie();
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        $str=curl_exec($ch);
        $info=curl_getinfo($ch);
        curl_close($ch);
        return $str;
    }

    /**
     * @param $jwid
     * @param $jwpwd
     * @return mixed
     * 获取有效的cookie
     */
    public function getCookie(){
        $jwid = $this->jwid;
        $cookie = S('cookie_'.$jwid);
        //配置文件修改后重新获取cookie
        if(C('JIAOWU_INDEX_URL') != S('JIAOWU_INDEX_URL') || $cookie === false){
            $ret = $this->getLogin();
            if( $ret !== true ){
                //清除一下COOKIE,再试一次~ ------2016/1/7
                S('cookie_' . $jwid,null);
                $this->getLogin();
            }
            $cookie = S('cookie_'.$jwid);
        }
        $cookie_file = tempnam(SAE_TEM_PATH,'cookie');
        file_put_contents($cookie_file,$cookie);

        return $cookie_file;
    }

    /**
     * @param $jwid
     * @param $jwpwd
     * @throws \Exception
     * 登录，缓存cookie
     */
    public function getLogin(){

        $url = C('JIAOWU_LOGIN_URL');

        //每次登录时候，将配置文件中的教务网连接写入缓存，方便更换时检测，清除cookie缓存
        S('JIAOWU_INDEX_URL',C('JIAOWU_INDEX_URL'));

        $jwid = $this->jwid;
        $jwpwd = $this->jwpwd;

        $cookie_file = tempnam(SAE_TEM_PATH,'cookie');

        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $str = curl_exec($ch);
        $info = curl_getinfo($ch);
        if($info['http_code'] != 200)
            throw new \Exception('教务网错误');
        curl_close($ch);

        //获取隐藏域表单值
        try{
            $viewstate = self::getHidden($str);
        }catch( \Exception $e ){
            //--------此处我想这记录日志啥的
            systemErrLog( $jwid,$e->getMessage() );
            exit();
        }
        //更改为这种方式 -----2016/01/15
        $post['__VIEWSTATE'] = $viewstate;
        $post['TextBox1'] = $jwid;
        $post['TextBox2'] = $jwpwd;
        $post['RadioButtonList1'] = iconv('utf-8', 'gb2312', '学生');
        $post['Button1'] = iconv('utf-8', 'gb2312', '登录');

        $post = http_build_query($post);

        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $str=curl_exec($ch);
        curl_close($ch);

        if(preg_match("/xs_main/",$str)){

            /*缓存cookie*/
            $cookie = file_get_contents($cookie_file);
            S('cookie_'.$jwid,$cookie,3000);

            return true;
        }else{
            return false;
        }
    }

    /**
     * @param $str
     * 匹配隐藏域表单值
     */
    public static function getHidden($str){

        $pattern = '/<input type="hidden" name="__VIEWSTATE" value="(.*)" \/>/i';
        preg_match($pattern, $str, $matches);
        $view_size = sizeof($matches);
        if ($view_size > 1) {
            return $matches[1];
        }else{
            throw new \Exception('获取隐藏域值失败！');
        }
    }

}
