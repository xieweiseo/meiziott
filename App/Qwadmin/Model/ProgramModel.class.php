<?php
namespace Qwadmin\Model;
use Think\Model;

class ProgramModel extends Model {
    protected $tableName = 'program'; 
    
    /**
     * 获取节目数
     * @param number $sid
     * @return 
     */
    public function getProgramCount($sid = 0){
        $count = 0;
        if($sid){
            $ids = M('program_series_rel')->field('PROGRAM_ID')->where("PROGRAM_SERIES_ID=".$sid)->select();
            //dump($ids);exit;
            $aids_list = array();
            foreach ($ids as $aid){
                $aids_list[] = $aid['program_id'];
            }
            $progrma_ids = implode(',', $aids_list);
        
            if($progrma_ids){
                $where = "program_id in ($progrma_ids)";
                $count = $this->where($where)->count();
            }            
        }
        
        return $count;
    } 
    
    public function getSeriesSortID($sid = 0){
        if($sid){
           $taxis = 1;
           $ids = M('program_series_rel')->field('taxis')->where("PROGRAM_SERIES_ID=".$sid)->select();
           if(!empty($ids)){
               $arr = array();
               foreach ($ids as $v){
                   $arr[] = $v['taxis'];
               }
               
               $taxis = getMax($arr);
           }
           
           return $taxis;
        }
    }
    
    public function getProgramCount2($sid = 0,$model=''){
        $a = array();
        if($sid){
            if(empty($model)){
                $model = M('program_series_rel');
            }
            $ids = $model->field('PROGRAM_ID')->where("PROGRAM_SERIES_ID=".$sid)->select();
            foreach ($ids as $k=>$v){
                $a[] = $v['program_id'];
            }
        }
    
        return count(array_unique($a));
    }    
    
    /**
     * 保存注入结果到数据库
     * @param string $corre_late_id
     * @param string $cmd_file_url
     * @param string $cspid
     * @param string $lspid
     */
    public function program_cmd_order_save($corre_late_id='',$cmd_file_url='',$cspid='',$lspid='',$aids='',$firms='',$status=''){
        $result =0;
        if($corre_late_id && $cmd_file_url){
            $file_url = explode('/', $cmd_file_url);
            $lscs_pid = explode('-', $firms);
            $cmd_order['file_name'] = substr($cmd_file_url,strripos($cmd_file_url,'/')+1);
            $cmd_order['corre_late_id'] = $corre_late_id;
            $cmd_order['file_url'] = $cmd_file_url;
            $cmd_order['type'] = str_replace(':','',$file_url[0]);
            $cmd_order['lspid'] = $lscs_pid[0];
            $cmd_order['cspid'] = $lscs_pid[1];            
            $cmd_order['cmd_result'] = 1;  //等待回调
            $cmd_order['status'] = $status; //状态
            $cmd_order['program_ids'] = $aids; //节目id
            $cmd_order['at_time'] = time();
            if($cmd_order['lspid']=='55555'){
              $cmd_order['firms'] = 'HW';
            }
            if($cmd_order['lspid']=='LTDJIPTV'){
              $cmd_order['firms'] = 'ZTE';
            }
            //dump($cmd_order);exit;
            if(M('Cmd_order')->data($cmd_order)->add()){
                $result = 1;
            }
        }
        return $result;
    }
    
    /**
     * 节目注入
     * @param string $aids
     * @param string $act
     * @param string $firms //格式：lspid-cspid
     */
    public function program_sync($aids ='', $act = NULL, $firms ='')
    {
        //$test = 'ftp://digitlink2017:digitlink@125.46.36.84:21//rbd/dgl/bvod/2017/04/xmldir/ZHE_1_1495180891378619.xml';
        //$this->program_wsdl($test,'17859512','LTDJIPTV-DJTV');
        //exit;
        $status = $act;
        if ($aids && $act && $firms) {
            if($act=='create'){
                $act = 1;}
            if($act=='update'){
                $act = 2;}
            if($act=='delete'){
                $act = 3;}
            $lscs_pid = explode('-', $firms);
            $lspid = $lscs_pid[0];
            $cspid = $lscs_pid[1];
            //定义商家标识
            if($lspid=='55555'){
                $firms_name = 'HW';
            }
            if($lspid=='LTDJIPTV'){
                $firms_name = 'ZTE';
            }            
            //获取xml地址
            //$url = "http://192.168.11.30:8282/updateProgram?firms=".$firms_name.'-'.$lspid."&ids=".$aids."&itype=".$act;
            $url = "http://10.254.202.107:8282/updateProgram?firms=".$firms_name.'-'.$lspid."&ids=".$aids."&itype=".$act;
            //dump($status);exit;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);  
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $data = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($data);
            //dump($result);exit;
            if($result->code==0 && $result->fileurl){
                //发送华为soap请求
              return array('result'=>$this->program_wsdl($result->fileurl,$aids,$firms,$status),'status'=>$status);
            }
            else{
                return false;  //xml没有返回
            }
        }
        else{
            $this->error('参数错误！');
        }
        
        return $output;
    }
    
    /**
     * 对接华为iptv-soap接口
     * @param string $cmdUrl
     */
    public function program_wsdl($cmd_file_url ='',$aids ='',$firms ='',$status=''){
    
        //$this->program_sync($aids);  // 获取cmd_ftp_url 地址
        
        //if (!empty($aids) && !empty($cmdUrl) && !empty($firms)) {
        $lscs_pid = explode('-', $firms);
        $lspid = $lscs_pid[0];
        $cspid = $lscs_pid[1];
        //生成注入订单号        
        $corre_late_id = 'pk_'.time().mt_rand(10000, 99999);

        //$cmd_file_url = 'ftp://ysten:ysten2016hlj@211.143.155.147:21/xml/fjmobile_huawei_c2/fj_huawei_c2_pk_408663_20170426153205079.xml';
        //dump($corre_late_id);exit;
        //dump($lspid);
        $hw_url = 'http://10.254.176.39:35100/cms/services/CCTVctms';
        if($lspid=='55555'){
            //华为 soap
            try {
                //$header = new \SoapHeader('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'CallbackHandler', new \SoapVar($xml, XSD_ANYXML), TRUE);
                $client_1 = new \SoapClient(null,array(
                "location" => $hw_url, 
                "uri"      =>'ExecCmdRes',
                "style"    => SOAP_RPC,
                "use"      => SOAP_ENCODED
                ));
                
                //$client->__setSoapHeaders(array($header));
                $resultInfo_1 = $client_1->__call('ExecCmd', array('CSPID' => $cspid, 'LSPID' => $lspid,'CorrelateID'=>$corre_late_id,'CmdFileURL'=>$cmd_file_url));  
                //dump($resultInfo_1);exit;
                //cmd_order入库
                if($resultInfo_1->Result==0){
                    return $this->program_cmd_order_save($corre_late_id,$cmd_file_url,$cspid,$lspid,$aids,$firms,$status);
                }
                 
            }catch (\Exception $e){
                $result_1 = $e->getMessage();
            }                        
        }
        if($lspid=='LTDJIPTV'){
            $zte_url = 'http://61.158.254.141:9378/axis/Services/WebServiceManager';
            //节目注入
            $resultInfo_2 = $this->do_post_soap($cspid,$lspid,$corre_late_id,$cmd_file_url,$zte_url);
            //dump($resultInfo_2);exit;
            //cmd_order入库
            if($resultInfo_2['result']==0){
                return $this->program_cmd_order_save($corre_late_id,$cmd_file_url,$cspid,$lspid,$aids,$firms,$status);
            }                    
        }     
    }     
   
    public function do_post_soap($cspid='',$lspid='',$corre_late_id='',$cmd_file_url='',$url=''){
        
       $post_data ='<?xml version="1.0" encoding="utf-8"?>        
        <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:m0="http://schemas.xmlsoap.org/soap/encoding/">  
          <SOAP-ENV:Body> 
            <m:ExecCmd xmlns:m="iptv" SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">  
              <CSPID xsi:type="m0:string">'.$cspid.'</CSPID>  
              <LSPID xsi:type="m0:string">'.$lspid.'</LSPID>  
              <CorrelateID xsi:type="m0:string">'.$corre_late_id.'</CorrelateID>  
              <CmdFileURL xsi:type="m0:string">'.$cmd_file_url.'</CmdFileURL> 
            </m:ExecCmd> 
          </SOAP-ENV:Body> 
        </SOAP-ENV:Envelope>';  
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch); 
        $result_pattern = "/<Result>(.*?)<\/Result>/";
        preg_match($result_pattern, $output, $result);
        $desc_pattern = "/<ErrorDescription>(.*?)<\/ErrorDescription>/";
        preg_match($desc_pattern, $output,$error_desc);         
                 
        return array('result'=>$result[1],'error_desc'=>$error_desc[1]);         
    }



    /**
     * 节目集下线
     * @param string $aids
     * @return mixed
     */
    public function program_usync($aids ='')
    {
        if ($aids) {
            $url = "http://epg.sllhtv.com:8080/home/public/programSet";
            $post_data = explode(',', $aids);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            $output = curl_exec($ch);
            curl_close($ch);
            //dump($output);exit();
        }
        else{
            $this->error('参数错误！');
        }
    
        return $output;
    }    
}