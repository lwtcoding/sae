<?php

namespace Wechat;
header("content-Type: text/html; charset=utf-8");
class Wechat{
 static $req_keys = array( "Content", "CreateTime", "FromUserName", "Label", 
            "Location_X", "Location_Y", "MsgType", "PicUrl", "Scale", "ToUserName", "MusicUrl","HQMusicUrl","Event","EventKey");
    public $token;
    public $request = array();

    protected $funcflag = false;
    protected $debug = false;

    public function __construct($token, $debug = false)
    {
        $this->token = $token;
        $this->debug = $debug;
    }

    public function get_msg_type()
    {
        return strtolower($this->request['MsgType']);
    }
	
    public function get_event_type()
    {
        return strtolower($this->request['Event']);
    }
	
	public function get_event_key()
    {
        return strtolower($this->request['EventKey']);
    }
	
		public function get_creattime()
    {
        return strtolower($this->request['CreateTime']);
    }
	
	
	public function valid()
    {
    
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
    }

    public function set_funcflag()
    {
        $this->funcflag = true;
    }

    public function replyText($message)
    {
        $textTpl = <<<eot
<xml>
    <ToUserName><![CDATA[%s]]></ToUserName>
    <FromUserName><![CDATA[%s]]></FromUserName>
    <CreateTime>%s</CreateTime>
    <MsgType><![CDATA[%s]]></MsgType>
    <Content><![CDATA[%s]]></Content>
    <FuncFlag>%d</FuncFlag>
</xml>
eot;
        $req = $this->request;
        return sprintf($textTpl, $req['FromUserName'], $req['ToUserName'],
                time(), 'text', $message, $this->funcflag ? 1 : 0);

    }
    
 public function replyNews($arr_item)
    {
        $itemTpl = <<<eot
        <item>
            <Title><![CDATA[%s]]></Title>
            <Discription><![CDATA[%s]]></Discription>
            <PicUrl><![CDATA[%s]]></PicUrl> 
            <Url><![CDATA[%s]]></Url>
        </item>

eot;
        $real_arr_item = $arr_item;
        if (isset($arr_item['title']))
            $real_arr_item = array($arr_item); 

        $nr = count($real_arr_item);
        $item_str = "";
        foreach ($real_arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['title'], $item['description'],
                    $item['pic'], $item['url']);

        $time = time();
        $fun = $this->funcflag ? 1 : 0;

        return <<<eot
<xml>
    <ToUserName><![CDATA[{$this->request['FromUserName']}]]></ToUserName>
    <FromUserName><![CDATA[{$this->request['ToUserName']}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[news]]></MsgType>
    <Content><![CDATA[]]></Content>
    <ArticleCount>{$nr}</ArticleCount>
    <Articles>
$item_str
    </Articles>
    <FuncFlag>{$fun}</FuncFlag>
</xml> 
eot;
    }
    public function replyMusic($arr_item)
    {
        $itemTpl = <<<eot
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <MusicUrl><![CDATA[%s]]></MusicUrl> 
            <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>      

eot;
        $real_arr_item = $arr_item;
        if (isset($arr_item['title']))
            $real_arr_item = array($arr_item); 
      $item_str = "";
        foreach ($real_arr_item as $item)
            $item_str .= sprintf($itemTpl, $item['title'], $item['description'],
            $item['murl'], $item['hqurl']);

        $time = time();
        $fun = $this->funcflag ? 1 : 0;

        return <<<eot
<xml>
    <ToUserName><![CDATA[{$this->request['FromUserName']}]]></ToUserName>
    <FromUserName><![CDATA[{$this->request['ToUserName']}]]></FromUserName>
    <CreateTime>{$time}</CreateTime>
    <MsgType><![CDATA[music]]></MsgType>
    <Music>
{$item_str}
    </Music>
    <FuncFlag>{$fun}</FuncFlag>
</xml> 
eot;
    }

    public function reply($callback)
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
                
           
        if ($this->debug)
          //     file_put_contents("request.txt", $postStr);

        if(empty($postStr) || !$this->checkSignature())
            die("该程序是微信接口程序,请在公众平台配置本页地址为url后测试!");

        $this->request = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

        $arg = call_user_func($callback, $this->request, $this);

        if (!is_array($arg))
        {
         $ret = $this->replyText($arg);
        }      
        elseif(array_key_exists("murl",$arg))
        {
            $ret = $this->replyMusic($arg);
        }else{
        
         $ret = $this->replyNews($arg);
        }

        if ($this->debug)
            file_put_contents("response.txt", $ret);
        echo $ret;
    }

private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = $this->token;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
	
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
	
	
public function biaoqing($content)
    {
	    if(strstr($content,"/:")){
$content = str_replace("'", "xb", $content);
		}
        
      
	return $content;
    }
}
 function media($content) //多媒体转换
    {
		if(strstr($content,'murl')){//音乐
			$a=array();
			foreach (explode('#',$content) as $content)
			{
				list($k,$v)=explode('|',$content);
				$a[$k]=$v;
			}
			$content = $a;
		}              
		elseif(strstr($content,'pic'))//多图文回复
		{
			$a=array();
			$b=array();
			$c=array();
			$n=0;
			$contents = $content;
			foreach (explode('@t',$content) as $b[$n])
			{
			    if(strstr($contents,'@t'))
				{
				$b[$n] = str_replace("itle","title",$b[$n]);
				$b[$n] = str_replace("ttitle","title",$b[$n]);
				}
				
				foreach (explode('#',$b[$n]) as $content)
				{
					list($k,$v)=explode('|',$content);
					$a[$k]=$v;
					$d.= $k;
				}
			$c[$n] = $a;
			$n++;
			
			}
			$content = $c ;
		}
		return $content;
	}

function curlpost($curlPost,$url) //curl post 函数
{
	$ch = curl_init();//初始化curl  
	curl_setopt($ch,CURLOPT_URL,$url);//抓取指定网页  
	curl_setopt($ch, CURLOPT_HEADER, 0);//设置header  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上  
	curl_setopt($ch, CURLOPT_POST, 1);//post提交方式  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);  
	$data = curl_exec($ch);//运行curl  
	curl_close($ch);  
	return $data;
}


function get_utf8_string($content) 
{    
	//  将一些字符转化成utf8格式   
	$encoding = mb_detect_encoding($content, array('ASCII','UTF-8','GB2312','GBK','BIG5'));  
	return  mb_convert_encoding($content, 'utf-8', $encoding);
}

?>