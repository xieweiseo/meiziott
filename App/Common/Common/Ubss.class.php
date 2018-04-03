<?php 
class Ubss{
    
    public function Subscription($data='',$Login_name='',$Password='',$Citycode='',$Productlist='') 
	{
	    
	    $post = $HTTP_RAW_POST_DATA;
	    $post = file_get_contents('php://input');	    
	    $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/boss/';
	    if (!file_exists($file_path)) {
	        mkdir($file_path);
	    }
	    @chmod($file_path, 0777);	
	   	    
	    $user_id_pattern = "/<UserID>(.*?)<\/UserID>/";
	    $login_name_pattern = "/<Login_name>(.*?)<\/Login_name>/";
	    $password_pattern = "/<Password>(.*?)<\/Password>/";
	    $city_code_pattern = "/<Citycode>(.*?)<\/Citycode>/";
	    $product_id_pattern = "/<ProductID>(.*?)<\/ProductID>/";
	    $product_desc_pattern = "/<ProductDesc>(.*?)<\/ProductDesc>/";
	    $user_group_id_pattern = "/<UsergroupID>(.*?)<\/UsergroupID>/";
	    $mac_pattern = "/<MAC>(.*?)<\/MAC>/";
	    $broadbandID_pattern = "/<broadbandID>(.*?)<\/broadbandID>/";
	    $countycode_pattern = "/<Countycode>(.*?)<\/Countycode>/";
	    $oltoffice_pattern = "/<OLTOffice>(.*?)<\/OLTOffice>/";
	    
	    
	    preg_match($user_id_pattern, $post,$user_id);
	    preg_match($login_name_pattern, $post,$login_name);
	    preg_match($password_pattern, $post,$password);
	    preg_match($city_code_pattern, $post,$city_code);
	    preg_match($product_id_pattern, $post,$product_id);
	    preg_match($product_desc_pattern, $post,$product_desc);
	    preg_match($user_group_id_pattern, $post,$user_group_id);
	    preg_match($mac_pattern, $post,$mac);
	    preg_match($broadbandID_pattern, $post, $broadbandID);	    
	    preg_match($countycode_pattern, $post,$countyCode);
	    preg_match($oltoffice_pattern, $post,$oltOffice);
	    	 
        $param['UserID'] = $user_id[1];
        $param['Login_name'] = $login_name[1];
        $param['Password'] = $password[1];
        $param['Citycode'] = $city_code[1];
        $param['ProductID'] = $product_id[1];
        $param['ProductDesc'] = $product_desc[1];
        $param['UsergroupID'] = $user_group_id[1];
        //$param['Mac'] = $mac[1];
        $param['broadbandID'] = $broadbandID[1];
        $param['Countycode'] = $countyCode[1];
        $param['OLTOffice'] = $oltOffice[1];
        
	  
	    file_put_contents($file_path.'post-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".$post."\n",FILE_APPEND);	    
	    
	    file_put_contents($file_path.'mac0-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".json_encode($data)."\n",FILE_APPEND);
	    
	    $create_time = date('Y-m-d H:i:s');    
	    
	    file_put_contents($file_path.'param-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".json_encode($param)."\n",FILE_APPEND);	     
	    
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
        $customer_info = $Model->db(1,$db_config)->query("select * from bims_customer where code = '".$param['Citycode'] ."' and user_id='".$param['UserID']."'");
        if(empty($customer_info)){
            //bims_customer 添加记录
           $customer_sql = "INSERT INTO bims_customer (`code`,`user_id`,`login_name`,`password`,`broadband_id`,`county_code`,`olt_office`,`create_date`)values('".
                            $param['Citycode']."','".$param['UserID']."','".$param['Login_name'] ."','".$param['Password']."','".$param['broadbandID']."','".$param['Countycode']."','".$param['OLTOffice']."','".$create_time."')";
           $result_customer = $Model->db(1,$db_config)->execute($customer_sql);
           $customer_id = $Model->db(1,$db_config)->getLastInsID();
           
           //bims_customer_devcie_map 添加记录
           $device_map_sql = "INSERT INTO bims_customer_device_map (`customer_id`,`city_code`,`user_name`,`product_id`,`product_desc`,`user_group_id`,`at_time`)values('".
                              $customer_id."','".$param['Citycode']."','".$param['Login_name']."','".$param['ProductID']."','".$param['ProductDesc']."','".$param['UsergroupID']."','".$create_time."')";
           $device_map_result = $Model->db(1,$db_config)->execute($device_map_sql);          
        }
        
	    $r = file_put_contents($file_path.'sub-'.date('Y-m-d').'.txt', "\n".date('Y-m-d H:i:s')." || ".json_encode($device_map_sql),FILE_APPEND);
	    
	    $data = array('Result'=>0,'Errordesc'=>'');
	    
        return json_encode($data);
        
        
	}
	
	/**
	 * Ubss变更用户信息
	 * @param string $data
	 * @param string $Login_name
	 * @param string $Password
	 * @param string $Citycode
	 * @param string $Productlist
	 * @return string
	 */
    public function ChangeSubscription($data='',$Login_name='',$Password='',$Citycode='',$Productlist='') 
	{
	    
	    $post = $HTTP_RAW_POST_DATA;
	    $post = file_get_contents('php://input');	    
	    $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/boss/';
	    if (!file_exists($file_path)) {
	        mkdir($file_path);
	    }
	    @chmod($file_path, 0777);	
	    
	    $user_id_pattern = "/<UserID>(.*?)<\/UserID>/";
	    $login_name_pattern = "/<Login_name>(.*?)<\/Login_name>/";
	    $password_pattern = "/<Password>(.*?)<\/Password>/";
	    $city_code_pattern = "/<Citycode>(.*?)<\/Citycode>/";
	    $product_id_pattern = "/<ProductID>(.*?)<\/ProductID>/";
	    $product_desc_pattern = "/<ProductDesc>(.*?)<\/ProductDesc>/";
	    $user_group_id_pattern = "/<UsergroupID>(.*?)<\/UsergroupID>/";
	    $mac_pattern = "/<MAC>(.*?)<\/MAC>/";
	    
	    preg_match($user_id_pattern, $post,$user_id);
	    preg_match($login_name_pattern, $post,$login_name);
	    preg_match($password_pattern, $post,$password);
	    preg_match($city_code_pattern, $post,$city_code);
	    preg_match($product_id_pattern, $post,$product_id);
	    preg_match($product_desc_pattern, $post,$product_desc);
	    preg_match($user_group_id_pattern, $post,$user_group_id);
	    preg_match($mac_pattern, $post,$mac);
	    	 
        $param['UserID'] = $user_id[1];
        $param['Login_name'] = $login_name[1];
        $param['Password'] = $password[1];
        $param['Citycode'] = $city_code[1];
        $param['ProductID'] = $product_id[1];
        $param['ProductDesc'] = $product_desc[1];
        $param['UsergroupID'] = $user_group_id[1];
        //$param['Mac'] = $mac[1];
	  
	    file_put_contents($file_path.'post_update-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".$post."\n",FILE_APPEND);	    
	    file_put_contents($file_path.'param_update-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".json_encode($param)."\n",FILE_APPEND);
	    
	    $create_time = date('Y-m-d H:i:s');    
	    	    
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
        $customer_info = $Model->db(1,$db_config)->query("select `id`,`code` from bims_customer where user_id='".$param['UserID']."'");
       
        if(!empty($customer_info[0]['id'])){
           // 更新 bims_customer_device_map
           $product_desc = empty($param['ProductDesc'])?"":",product_desc='".$param['ProductDesc']."'";
           $customer_sql = "UPDATE bims_customer_device_map set city_code='".$param['Citycode']."',product_id='".$param['ProductID']."'".$product_desc.",user_group_id='".$param['UsergroupID']."' where customer_id='".$customer_info[0]['id']."'";
           $result_customer = $Model->db(1,$db_config)->execute($customer_sql);
                                  
           $customer_device_map_info = $Model->db(1,$db_config)->query("select `mac` from bims_customer_device_map where customer_id='".$customer_info[0]['id']."'");
           $device_info = $Model->db(1,$db_config)->query("select `code`,`ysten_id` from bims_device where customer_id='".$customer_info[0]['id']."'");
           $device_sno = substr($device_info[0]['ysten_id'],0,22);
 
           $this->log('/data/appmac/', 'tradec_update-'.date('Y-m-d').'.txt', 'mac:'.$customer_device_map_info[0]['mac'].' || code:'.$device_info[0]['code'].' || act:'.'update');
           
           // 发送行业认证
           $tradec_result = $this->tradec($customer_device_map_info[0]['mac'], $device_info[0]['code'], 'update');            
           
           if($tradec_result['Result']==0 || $tradec_result['Result']==1){
               $data = array('Result'=>0,'Errordesc'=>'');
           }
           else{
               $data = array('Result'=>-1,'Errordesc'=>'Modify User Info fail!');
           }
        }
        else{            
            $data = array('Result'=>-1,'Errordesc'=>'Post Data Is Empty！');
        }
        	    	    	    
        return json_encode($data);       
	}

	/**
	 * Ubss销户
	 */
	public function Unsubscription(){
	    $post = $HTTP_RAW_POST_DATA;
	    $post = file_get_contents('php://input');
	    $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/boss/';
	    if (!file_exists($file_path)) {
	        mkdir($file_path);
	    }
	    @chmod($file_path, 0777);
	    
	    $user_id_pattern = "/<UserID>(.*?)<\/UserID>/";    
	    preg_match($user_id_pattern, $post,$user_id);	     
	    $param['UserID'] = $user_id[1];
    
	    if(!empty($param['UserID'])){
    	    //删除customer
    	    $Model = new \Think\Model();
    	    $db_config = C("DB125_CONFIG");
    	    $customer = $Model->db(1,$db_config)->execute("delete from bims_customer where user_id='".$param['UserID']."'");	 
    	    	    	    
    	    //将bims_device_sno status置-1
    	    $device_sno = $Model->db(1,$db_config)->execute("UPDATE bims_device_sno set status=-1 where user_id='".$param['UserID']."'");
    	    
    	    //读取code, 删除device
    	    $customer_device_map_info = $Model->db(1,$db_config)->query("select `customer_id`,`mac` from bims_customer_device_map where user_name='".$param['UserID']."'");    
    	    $device_info = $Model->db(1,$db_config)->query("select `code` from bims_device where customer_id='".$customer_device_map_info[0]['customer_id']."'");
    	   
    	    $mac = $customer_device_map_info[0]['mac'];
    	    $code = $device_info[0]['code'];
    	    $device = $Model->db(1,$db_config)->execute("delete  from bims_device where customer_id='".$customer_device_map_info[0]['customer_id']."'");
    	    
    	    //删除customer_device_map
    	    $customer_device_map = $Model->db(1,$db_config)->execute("delete from bims_customer_device_map where user_name='".$param['UserID']."'");	    
    	    
    	    //删除device_group_map
    	    $device_group_map = $Model->db(1,$db_config)->execute("delete  from bims_device_group_map where user_id='".$param['UserID']."'");
    	    
    	    file_put_contents($file_path.'user-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s').' '.$param['UserID']." || ".$post."\n",FILE_APPEND);	    
    	    
    	    //发送行业认证销户  	    
    	    $tradec_return = $this->tradec($mac,$code,'delete');
    	    
    	    if($tradec_return['Result']==0 || $tradec_return['Result']==-1){
    	        $data = array('Result'=>0,'Errordesc'=>'');
    	    }
    	    else{
    	        //1失败,-2异常 ， 都归销户失败
    	        $data = array('Result'=>-1,'Errordesc'=>'销户失败！');
    	    }   
	    }
	    else{	        
	        $data = array('Result'=>-1,'Errordesc'=>'post data is empty!');	        
	    }	
	    
	    echo json_encode($data);exit;
	    
	}
	
	
	public function tradec($mac='',$code='',$act=''){
	    $url = 'http://sl.51le.cn:8083/User/Device/addMacsn';
	    $post_data['mac'] = $mac;
	    $post_data['code'] = $code;
	    $post_data['act'] = $act;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	    $output = curl_exec($ch);
	    curl_close($ch);
	    
	    $this->log('/data/boss/','tardec-'.date('Y-m-d').'.txt',$output." || ".$act);
	    
	    return json_decode($output,true);
	}

	
	public function log($path,$filename,$data){
	
	    $file_path = $_SERVER['DOCUMENT_ROOT'].$path;
	    if (!file_exists($file_path)) {
	        mkdir($file_path);
	    }
	    @chmod($file_path, 0777);
	
	    file_put_contents($file_path.$filename, date('Y-m-d H:i:s')." || ".$data."\n",FILE_APPEND);
	}	
	
}