<?php 
use Org\Util\String;
class Service {	
	/**
	 * 
	 * @param string $CSPID
	 * @param string $LSPID
	 * @param string $CorrelateID
	 * @param int $CmdResult        0/成功,-1/失败
	 * @param url $ResultFileURL    返回xml url地址
	 * @return int Result:-1失败,0成功
	 * @return string ErrorDescription
	 */
	public function ResultNotifyReq($cspid='',$lspid='',$corre_late_id='',$cmd_result='',$result_file_url=''){	     
	    //请求回应提示
	    $response = array('Result'=>-1,'ErrorDescription'=>'');
	    if(empty($cspid)){
	        //$response['Result'] = -1;
	        $response['ErrorDescription'] = '-4000: [CL]CSPID is invalid [1, 32]!';
	    }
	    else if(empty($lspid)){
	        //$response['Result'] = -1;
	        $response['ErrorDescription'] = '-4001: [CL]LSPID  is invalid [1, 32]!';	        
	    }
	    else if(empty($corre_late_id)){
	        //$response['Result'] = -1;
	        $response['ErrorDescription'] = '-4002: [CL]CorrelateID  is invalid [1, 32]!';	        
	    }
	    else if(!isset($cmd_result)){
	        //$response['Result'] = -1;
	        $response['ErrorDescription'] = '-4003: [CL]CmdResult  is invalid [1, 32]!';	        
	    }
	    else if(empty($result_file_url)){
	        //$response['Result'] = -1;
	        $response['ErrorDescription'] = '-4004: [CL]ResultFileURL  is invalid [1, 32]!';	        
	    }
	    else{
	        //处理xml文件判断注入是否成功，并更新数据库;
	        //$result_file_url
	        if($cmd_result==0){
	            $response['Result'] = 0;
	            $response['ErrorDescription'] = '[CL]Soap Massage(CCTV to CL) was received Successfully!!';	 
	        }
	        else{
	            $response['Result'] = -1;
	            $response['ErrorDescription'] = '[CMS]Soap Massage(CCTV to CL) was received drop through!!';
	        }
	        //返回结果更新cmd_order
            $cmd_order['up_time'] = time();
            $cmd_order['cmd_result'] = $cmd_result; //注入结果: 0成功、-1失败
            $cmd_order['result_file_url'] = $result_file_url;
            if($cmd_result==-1){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $result_file_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $data = curl_exec($ch);
                curl_close($ch);
                $cmd_order['error_desc'] = '内容对接注入失败!';
                $cmd_order['result_content'] = $data;
            }
            if($cmd_result==0){
                $cmd_order['error_desc'] = '内容对接注入成功!';
                $cmd_order['result_content'] = '';
            }
            
           M('cmd_order')->data($cmd_order)->where(array('corre_late_id'=>$corre_late_id))->save(); 
          	             	        
	    }
	   
	    return json_encode($response);
	    
	}
}