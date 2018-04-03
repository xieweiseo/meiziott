<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：节目注入接口。
 *
 **/

namespace Home\Controller;

use Vendor\Page;

class ProgramController extends ComController
{
     
    public function inject()
    {
        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        $action = 1 ; //I('action','','trim');
        //$firms = I('firms','','trim'); //格式：55555-dangjian/LTDJIPTV-DJTV   
        $firms = array("55555-dangjian","LTDJIPTV-DJTV");
                
        if ($aids) {
           $program = D('Program');
           $program_id = $program->where('program_id='.$aids)->getField('PROGRAM_ID');
           
           if($program_id){
               foreach($firms as $val){                                                       
                   switch ($action){
                       case '1':
                           $status = 'create';
                           break;
                       case '2':
                           $status = 'update';
                           break;
                       case '3':
                           $status = 'delete';
                           break;
                   }
                  
                   //节目注入
                   $cmd_result = $this->program_sync($aids, $status, $val);
    
               }
               if($cmd_result){
                   if($cmd_result['result']){
                       //$this->success('节目注入['.$cmd_result['status'].']成功');
                       echo 'program inject success! '.date('Y-m-d H:i:s');
                   }
                   else{
                       //$this->success('节目注入['.$cmd_result['status'].']失败');
                       echo 'program inject fail! '.date('Y-m-d H:i:s');
                   }              
               }
               else{
                   //$this->error('xml url address returned incorrectly!');
                   echo 'xml url address returned incorrectly!';
               }
               
            }
            else{
                //$this->error('program does not exist!');
                echo 'program does not exist!';
            }

        }
        else{
            //$this->error('parameter error!');
            echo 'parameter error!';
        }
    } 
    
    /**
     * 节目注入
     * @param string $aids
     * @param string $act
     * @param string $firms //格式：lspid-cspid
     */
    private function program_sync($aids ='', $act = NULL, $firms ='')
    {
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
                        $url = "http://10.254.202.107:8282/updateProgram?firms=".$firms_name.'-'.$lspid."&ids=".$aids."&itype=".$act;
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_HEADER, 0);
                        $data = curl_exec($ch);
                        curl_close($ch);
                        $result = json_decode($data);
                        if($result->code==0 && $result->fileurl){
                            //发送华为soap请求
                            return array('result'=>$this->program_wsdl($result->fileurl,$aids,$firms,$status),'status'=>$status);
                        }
                        else{
                            return false;  //xml没有返回
                        }
        }
        else{
            //$this->error('参数错误！');
            echo '参数错误！';
        }

    }    
    
    /**
     * 对接iptv-soap接口
     * @param string $cmdUrl
     */
    private function program_wsdl($cmd_file_url ='',$aids ='',$firms ='',$status=''){
        $lscs_pid = explode('-', $firms);
        $lspid = $lscs_pid[0];
        $cspid = $lscs_pid[1];
        //生成注入订单号
        $corre_late_id = 'pk_'.time().mt_rand(10000, 99999);
        $hw_url = 'http://10.254.176.39:35100/cms/services/CCTVctms';
        if($lspid=='55555'){
            //华为 soap
            try {
                $client_1 = new \SoapClient(null,array(
                "location" => $hw_url,
                "uri"      =>'ExecCmdRes',
                "style"    => SOAP_RPC,
                "use"      => SOAP_ENCODED
                ));
    
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
 
    /**
     * 保存注入结果到数据库
     * @param string $corre_late_id
     * @param string $cmd_file_url
     * @param string $cspid
     * @param string $lspid
     */
    private function program_cmd_order_save($corre_late_id='',$cmd_file_url='',$cspid='',$lspid='',$aids='',$firms='',$status=''){
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
    
    private function do_post_soap($cspid='',$lspid='',$corre_late_id='',$cmd_file_url='',$url=''){  
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
    
}