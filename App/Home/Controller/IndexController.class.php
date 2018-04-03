<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-01-21
 * 版    本：1.0.0
 * 功能说明：前台控制器演示。
 *
 **/
namespace Home\Controller;

use Vendor\Page;

class IndexController extends ComController
{
    public function index()
    {
        header("Location: http://cms.cuhn.sllhtv.com:5188/qwadmin");exit;
//         var_dump(C('DB_LOCALHOST'));
//         echo substr('0392', 1,3)."<br/>";
//         echo substr(number_format(microtime(true),10,'',''),-10)."<br/>";
//         echo strlen(substr(number_format(microtime(true),10,'',''),-10))."<br/>";
//         echo strlen(time());
         exit;
        //$customer_device_map_info[0]['user_group_id'] = 'DJIPTV';
        $customer_device_map_info[0]['user_group_id'] = 'YLIPTV';
        //$customer_device_map_info[0]['product_desc'] = 'IPTV智慧医疗平安版（华为）';
        $customer_device_map_info[0]['product_desc'] = 'IPTV党员远程教育（中兴）';
        
        
        if(strtoupper($customer_device_map_info[0]['user_group_id'])=='DJIPTV'){
            if(stripos($customer_device_map_info[0]['product_desc'],'华为')!==false){
                $group_name = '华为';
            }
            if(stripos($customer_device_map_info[0]['product_desc'],'中兴')!==false){
                $group_name = '中兴';
        
            }
        
            $area_group_map = "select * from bims_area_device_group_map where code_name = 'DJIPTV' and group_name='".$group_name."'";
        }
        if(strtoupper($customer_device_map_info[0]['user_group_id'])=='YLIPTV'){
            if(stripos($customer_device_map_info[0]['product_desc'],'华为')!==false){
                $group_name = '华为';
            }
            if(stripos($customer_device_map_info[0]['product_desc'],'中兴')!==false){
                $group_name = '中兴';
        
            }
        
            $area_group_map = "select * from bims_area_device_group_map where code_name = 'YLIPTV' and group_name='".$group_name."'";
        }  
        
        dump($area_group_map);exit;
        
        $path = $path ? $path : '/media/new/';
        $php_path = $_SERVER['DOCUMENT_ROOT'];
        $save_path = $php_path . $path;  //设置文件保存目录 注意包含/
        
        $data = json_encode(array('userid'=>'39200000119670','username'=>'39200000119670','mac'=>'9C:59:57:69:80:00','wifi_mac'=>'6C:76:65:89:70:70'));
        $param = json_decode($data,true);
        
        //校验mac格式
        $pattern_mac="/[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f]/i";
        $result_mac = preg_match($pattern_mac, $param['mac']);

        $pattern_wifi_mac="/[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f]/i";
        $result_wifi_mac = preg_match($pattern_wifi_mac, $param['wifi_mac']);
        
        //校验mac 不全为0
        $mac_status = TRUE;
        $mac_str = str_replace(':', '', $param['mac']);
        $mac_len = strlen($mac_str);
        if($mac_str =='000000000000' || $mac_len!=12){
            $mac_status = FALSE;
        }
        $wifi_mac_status = TRUE;
        $wifi_mac_str = str_replace(':', '', $param['wifi_mac']);
        $wifi_mac_len = strlen($wifi_mac_str);
        if($wifi_mac_str=='000000000000' || $wifi_mac_len!=12){
            $wifi_mac_status = FALSE;
        }
        if($result_mac && $result_wifi_mac && $mac_status && $wifi_mac_status){            
            dump(TRUE);
        }
        else{
            dump(FALSE);
        }
        
        //$this->bianli($save_path);

        //$this->display();
    }
    
    public function client(){
        try {
            $soap = new \SoapClient(null, array(
                "location" => "http://10.254.202.27:5188/home/CommandsService",
                "uri" => "index", //资源描述符服务器和客户端必须对应
                "style" => SOAP_RPC,
                "use" => SOAP_ENCODED
            ));
            
            dump($soap->__getTypes());
            dump($soap->__getFunctions());
            $param = 'tongtong';
            $result = $soap->__Call('ResultNotifyReq');
            dump($result);
            
        } catch (Exction $e) {
            echo print_r($e->getMessage(), true);
        }        
    }
    
    
    public function bianli($path = '.'){
        $current_dir = opendir($path);
        while(($file = readdir($current_dir)) !==false){
            $sub_dir = $path .DIRECTORY_SEPARATOR . $file;
            if($file == '.' || $file == '..'){
                continue;
            }elseif(is_dir($sub_dir)){//如果是目录，则递归目录
               //echo "目录".$path.DIRECTORY_SEPARATOR .$file.":<br />";
                $this->bianli($sub_dir);
            }else{//如果是文件直接输出文件
                echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;文件: ".$path.DIRECTORY_SEPARATOR.$file .' 大小：'.round(filesize($path.DIRECTORY_SEPARATOR.$file)/1024)."<br />";
            }
        }
    
    }
    
    public function createxml(){
         
        D('program')->program_wsdl('102212','update');  //节目注入
        exit;
        
        $dom = new \DOMDocument('1.0','utf-8'); //创建XML对象
        //创建根节点
        $data = $dom->createElement('content');
        $data->setAttribute('width','990'); //配置属性
        $data->setAttribute('height','1000');
        $data->setAttribute('bgcolor','cccccc');
        $data->setAttribute('loadercolor','ffffff');
        $data->setAttribute('panelcolor','5d5d61');
        $data->setAttribute('buttoncolor','5d5d61');
        $data->setAttribute('textcolor','ffffff');
        $dom->appendChild($data); //对象中插入根节点
        
        //dump($dom);exit;
        //利用循环插入子节点
        foreach($a as $vo){
            $img = $dom->createElement('page');
            $img->setAttribute('src', "pages/".$vo);
            $data->appendChild($img);
        }
        echo $dom->save($_SERVER['DOCUMENT_ROOT']."itcms/xml/Pages.xml");        
    }
  
    public function message(){
       $subject='<?xml version="1.0" encoding="utf-8"?>       
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
          <soapenv:Body>
            <ns1:ResultNotify xmlns:ns1="iptv" soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/http">
              <CSPID xsi:type="xsd:string">dangjian</CSPID>
              <LSPID xsi:type="xsd:string">55555</LSPID>
              <CorrelateID xsi:type="xsd:string">pk_149520778357536</CorrelateID>
              <CmdResult xsi:type="xsd:int">0</CmdResult>
              <ResultFileURL xsi:type="xsd:string">ftp://cmsftp:Vtpicmsftp_1234@10.254.176.39:21/syncdir/remote/CCTV/response/CCTV_SOAP_RESULT_CCTV_SOAP_MSG_201705192330087340162.xml</ResultFileURL>
            </ns1:ResultNotify>
          </soapenv:Body>
        </soapenv:Envelope>';

       $cspid_pattern = "/<CSPID xsi:type=\"xsd:string\">(.*?)<\/CSPID>/";
       $lspid_pattern = "/<LSPID xsi:type=\"xsd:string\">(.*?)<\/LSPID>/";
       $corre_id_pattern = "/<CorrelateID xsi:type=\"xsd:string\">(.*?)<\/CorrelateID>/";
       $cmd_result_pattern = "/<CmdResult xsi:type=\"xsd:int\">(.*?)<\/CmdResult>/";
       $result_url_pattern = "/<ResultFileURL xsi:type=\"xsd:string\">(.*?)<\/ResultFileURL>/";
      
       preg_match($cspid_pattern, $subject,$cspid);
       preg_match($lspid_pattern, $subject,$lspid);
       preg_match($corre_id_pattern, $subject,$corre_id);
       preg_match($cmd_result_pattern, $subject,$cmd_result);
       preg_match($result_url_pattern, $subject,$result_url);
       
      dump($result_url);exit;
                
    }
    
    public function test(){
        
        $str = -2;
        if(empty($str)){
            echo 11111111;
        }
        else{
            echo 222222;
        }
        exit;
        $str = '';
        dump($str);
        $arr_str = explode(',', $str);
        
        if(is_array($arr_str) && $arr_str[0]){
            $r=0;
            foreach ($arr_str as $list){
                if(strstr($list,'华为')){
                    $r=1;
                    $cmd_result_new[] = '华为0';
                }
                if(strstr($list,'中兴')){
                    $r=2;
                    $cmd_result_new[] = '中兴0';
                }
            }                        
        }
        else{
            $cmd_result_new[] = 'error';
        }
        echo $r;exit;
        //dump(implode(',',$cmd_result_new));exit;
    }
    
    /*
    //一些前台DEMO
    //单页
    public function single($aid){

        $aid = intval($aid);
        $article = M('article')->where('aid='.$aid)->find();
        $this->assign('article',$article);
        $this->assign('nav',$aid);
        $this -> display();
    }
    //文章
    public function article($aid){

        $aid = intval($aid);
        $article = M('article')->where('aid='.$aid)->find();
        $sort = M('asort')->field('name,id')->where("id='{$article['sid']}'")->find();
        $this->assign('article',$article);
        $this->assign('sort',$sort);
        $this -> display();
    }

    //列表
    public function articlelist($sid='',$p=1){
        $sid = intval($sid);
        $p = intval($p)>=1?$p:1;
        $sort = M('asort')->field('name,id')->where("id='$sid'")->find();
        if(!$sort) {
            $this -> error('参数错误！');
        }
        $sorts = M('asort')->field('id')->where("id='$sid' or pid='$sid'")->select();
        $sids = array();
        foreach($sorts as $k=>$v){
            $sids[] = $v['id'];
        }
        $sids = implode(',',$sids);

        $m = M('article');
        $pagesize = 2;#每页数量
        $offset = $pagesize*($p-1);//计算记录偏移量
        $count = $m->where("sid in($sids)")->count();
        $list  = $m->field('aid,title,description,thumbnail,t')->where("sid in($sids)")->order("aid desc")->limit($offset.','.$pagesize)->select();
        //echo $m->getlastsql();
        $params = array(
            'total_rows'=>$count, #(必须)
            'method'    =>'html', #(必须)
            'parameter' =>"/list-{$sid}-?.html",  #(必须)
            'now_page'  =>$p,  #(必须)
            'list_rows' =>$pagesize, #(可选) 默认为15
        );
        $page = new Page($params);
        $this->assign('list',$list);
        $this->assign('page',$page->show(1));
        $this->assign('sort',$sort);
        $this->assign('p',$p);
        $this->assign('n',$count);

        $this -> display();
    }
    */
}