<?php
namespace Home\Controller;

class CommandsServiceController extends ComController
{
    public function index()
    {
         $data = $HTTP_RAW_POST_DATA;
         $data = file_get_contents('php://input');
         $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/'.date('Ymd').'/';
         if (!file_exists($file_path)) {
             mkdir($file_path);            
         }   
         @chmod($file_path, 0777);
         
                
         //保存返回soap文件
         $hw_list = $this->hw_pattern($data);
         $zte_list = $this->zte_pattern($data);

        if($hw_list['lspid']=='55555'){ 
           //更新cmd_order
           file_put_contents($file_path.'hw_'.$hw_list['corre_id'].'.xml', $data);           
           $program_cmd_order = $this->update_cmd_order($hw_list);     
           $program_id = $program_cmd_order['program_ids'];         
           //更新节目表 program
           $ps = $this->update_program($program_id,$hw_list,$program_cmd_order['status']);         
           $this->HwResultNotifyRes($hw_list['cmd_result']);    //返回结果 0成功，-1失败         
        }
        if($zte_list['lspid']=='LTDJIPTV'){
             //更新cmd_order
             file_put_contents($file_path.'zte_'.$zte_list['corre_id'].'.xml', $data);            
             $program_cmd_order = $this->update_cmd_order($zte_list);    
             $program_id = $program_cmd_order['program_ids'];          
             //更新节目表 program
             $this->update_program($program_id,$zte_list,$program_cmd_order['status']);          
             $this->ZteResultNotifyRes($zte_list['cmd_result']);    //返回结果 0成功，-1失败
        }
        exit;      
    }
    
    /**
     * 华为Soap_Res
     * @param string $result
     * @param string $erro_des
     */
    public function HwResultNotifyRes($result, $erro_des='Resived Succesfully!!'){
          $res_msg ='<?xml version="1.0" encoding="utf-8"?>       
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
              <soapenv:Body>
                <ns1:ExecCmdResponse xmlns:ns1="iptv" soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/http">
                 <ExecCmdReturn xsi:type="ns1:CSPResult">
                  <Result xsi:type="xsd:int">'.$result.'</Result>
                  <ErrorDescription xsi:type="xsd:string">'.$erro_des.'</ErrorDescription>
                  </ExecCmdReturn>
                 </ns1:ExecCmdResponse>
              </soapenv:Body>
            </soapenv:Envelope>';
      
          echo $res_msg; exit;           
    } 
    
    /**
     * 中兴Soap_Res
     * @param string $result
     * @param string $erro_des
     */
    public function ZteResultNotifyRes($result, $erro_des='Resived Succesfully!!'){
        $res_msg ='<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
              <soapenv:Body>
                <ns1:ExecCmdResponse xmlns:ns1="iptv" soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/http">
                 <ExecCmdReturn xsi:type="ns1:CSPResult">
                  <Result xsi:type="xsd:int">'.$result.'</Result>
                  <ErrorDescription xsi:type="xsd:string">'.$erro_des.'</ErrorDescription>
                  </ExecCmdReturn>
                 </ns1:ExecCmdResponse>
              </soapenv:Body>
            </soapenv:Envelope>';
        
        echo $res_msg; exit;
    }
    
    
    /**
     * 华为回调返回
     * @param string $subject
     */
    public function hw_pattern($subject=''){
        $sub_list = NULL;
        if($subject){
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
            
            $sub_list = array(
                'cspid'=>$cspid[1],
                'lspid'=>$lspid[1],
                'corre_id'=>$corre_id[1],
                'cmd_result'=>$cmd_result[1],
                'result_url'=>$result_url[1]               
            );
        }
        
        
        return $sub_list;
    }
    
    /**
     * 中兴回调返回
     * @param string $subject
     */
    public function zte_pattern($subject=''){
        $zx_list = NULL;

        if($subject){
            $cspid_pattern = "/<CSPID>(.*?)<\/CSPID>/";
            $lspid_pattern = "/<LSPID>(.*?)<\/LSPID>/";
            $corre_id_pattern = "/<CorrelateID>(.*?)<\/CorrelateID>/";
            $cmd_result_pattern = "/<CmdResult>(.*?)<\/CmdResult>/";
            $result_url_pattern = "/<ResultFileURL>(.*?)<\/ResultFileURL>/";
    
            preg_match($cspid_pattern, $subject,$cspid);
            preg_match($lspid_pattern, $subject,$lspid);
            preg_match($corre_id_pattern, $subject,$corre_id);
            preg_match($cmd_result_pattern, $subject,$cmd_result);
            preg_match($result_url_pattern, $subject,$result_url);
    
            $zx_list = array(
                'cspid'=>$cspid[1],
                'lspid'=>$lspid[1],
                'corre_id'=>$corre_id[1],
                'cmd_result'=>$cmd_result[1],
                'result_url'=>$result_url[1]
            );
        }

         return $zx_list;
    }    
    
    /**
     * 更新订单表<cmd_order>
     * @param array $arr_list
     * @param string $data
     */
    public function update_cmd_order($arr_list='', $data=''){       
        $result= 0;
        if($arr_list){
            //返回结果更新cmd_order
            $cmd_order['up_time'] = time();
            $cmd_order['cmd_result'] = $arr_list['cmd_result']; //注入结果: 0成功、-1失败
            $cmd_order['result_file_url'] = $arr_list['result_url'];
            $cmd_order['result_desc'] = json_encode($arr_list);
            
            $cmd_order_model = M('cmd_order');
            $co_result = $cmd_order_model->data($cmd_order)->where(array('corre_late_id'=>$arr_list['corre_id']))->save();
                       
            //$result = $cmd_order_model->where(array('corre_late_id'=>$arr_list['corre_id']))->getField('program_ids');
            $result = $cmd_order_model->field('program_ids,status')->where(array('corre_late_id'=>$arr_list['corre_id']))->find();
        }
        
        return $result;
    }
    
    /**
     * 更新节目表<program>
     * @param int $program_id
     * @param array $arr_list
     * @return Ambigous <number, boolean>
     */
    public function update_program($program_id='',$arr_list='',$program_status=''){       
        $result= 0;
        if($program_id){
           
            $program_model = M('program');
            $cmd_result = $program_model->field('CMS_RESULT_HW_INJECT,CMS_RESULT_HW_STATUS,CMS_RESULT_ZTE_INJECT,CMS_RESULT_ZTE_STATUS')->where(array('PROGRAM_ID'=>$program_id))->find();
            
            //获取lspid_name
            $lspid = $arr_list['lspid'];
            if($lspid=='55555'){
                $lspid_name = '华为';
                if($arr_list['cmd_result']==0){
                  if($cmd_result['cms_result_hw_status']=='create'){
                        $program['CMS_RESULT_HW_INJECT'] = 0;
                        $program['CMS_RESULT_HW_STATUS'] = 'update'; //'update';
                    }
                  if($cmd_result['cms_result_hw_status']=='update'){
                        $program['CMS_RESULT_HW_INJECT'] = 0;
                        $program['CMS_RESULT_HW_STATUS'] = 'update'; //'update';
                    }
                  if($cmd_result['cms_result_hw_status']=='' || empty($cmd_result['cms_result_hw_status'])){
                        $program['CMS_RESULT_HW_INJECT'] = 0;
                        $program['CMS_RESULT_HW_STATUS'] = 'create'; //'create';
                    }
                  if($program_status=='delete'){
                        $program['CMS_RESULT_HW_INJECT'] = -2;
                        $program['CMS_RESULT_HW_STATUS'] = ''; //'delete';
                  }
                }
            }
            if($lspid=='LTDJIPTV'){
                $lspid_name = '中兴';
                if($arr_list['cmd_result']==0){
                  if($cmd_result['cms_result_zte_status']=='create'){
                           $program['CMS_RESULT_ZTE_INJECT'] = 0;
                           $program['CMS_RESULT_ZTE_STATUS'] = 'update'; //'update';
                    }
                  if($cmd_result['cms_result_zte_status']=='update'){
                           $program['CMS_RESULT_ZTE_INJECT'] = 0;
                           $program['CMS_RESULT_ZTE_STATUS'] = 'update'; //'upate'               
                   }
                  if($cmd_result['cms_result_zte_status']=='' || empty($cmd_result['cms_result_zte_status'])){
                      $program['CMS_RESULT_ZTE_INJECT'] = 0;
                      $program['CMS_RESULT_ZTE_STATUS'] = 'create'; //'upate'                      
                  }
                  if($program_status=='delete'){
                      $program['CMS_RESULT_ZTE_INJECT'] = -2;
                      $program['CMS_RESULT_ZTE_STATUS'] = ''; //'delete';
                  }                                   
                }                           
            }                                               
            
             $result = $program_model->data($program)->where(array('PROGRAM_ID'=>$program_id))->save();                        
        }
        
        return $result;        
    }
}
?>