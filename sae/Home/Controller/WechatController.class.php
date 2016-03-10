<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/3/10
 * Time: 9:16
 */

namespace Home\Controller;

use Think\Controller;
use Wechat\Wechat;
use Wechat\CommonUtil;
class WechatController extends Controller
{
    public function index(){

        $w = new Wechat('dgyl',true);
        if(isset($_GET["echostr"])){

            $w->valid();
        }else{

            $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

            file_put_contents("post.txt", $postStr);
            $xml = simplexml_load_string($postStr);

            file_put_contents("post.txt", $xml->MsgType=="text");
            if($xml->Event=="subscribe"){
                $commonUtil = new CommonUtil();
                $access_token = $commonUtil->accessToken();
                $textTpl = <<<eot
<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[%s]]></MediaId>
</Image>
</xml>
eot;

                $text=vsprintf($textTpl,array( $xml->FromUserName, $xml->ToUserName,
                    time(), 'thanks'));

                echo $text;
                exit();
            }




        }


    }
}