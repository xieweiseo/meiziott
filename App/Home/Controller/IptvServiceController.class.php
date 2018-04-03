<?php
namespace Home\Controller;

/**
 * Iptv-Ubss Service
 * @author Administrator
 *
 */
class IptvServiceController extends ComController
{
    public function index()
    {
//         $wsdl_url = $_SERVER['DOCUMENT_ROOT'].'/wsdl/Iptv.wsdl';
//         define('WSDL_URL', $wsdl_url);        //定义WSDL文件路径
//         ini_set('soap.wsdl_cache_enabled','0');    //关闭WSDL缓存
//         import('Vendor.SoapDiscovery');
//         import('Common.Common.Ubss');
//         $disco = new \SoapDiscovery('Ubss','soap',WSDL_URL);
//         $str = $disco->getWSDL();
//         //$r = $disco->getDiscovery();

//         //SOAP开启并接收Client传入的参数响应
//         $server = new \SoapServer(WSDL_URL);
//         $server->setClass('Ubss');
//         $server->handle();        

        //No-WSDL Soap
        $server=new \SoapServer(null,array('uri' => "iptv_ubss"));
        import('Common.Common.Ubss');
        $server->setClass("Ubss");
        $server->handle();        
        
    }
    
    public function ReportMac()
    {   
        //非专网Ip限制
        $ip = get_client_ip();
        $ips = explode('.', $ip);
        if($ips[0]!=='10'){
            exit(json_encode(array('result'=>'Illegal IP Access !')));
        }
        
        $data = $HTTP_RAW_POST_DATA;
        $data = file_get_contents('php://input');
        //$data = json_encode(array('userid'=>'csdj003','username'=>'csdj003','mac'=>'7C:EF:C6:00:91:79','wifi_mac'=>'00:00:00:00:00:00'));
        //$data = json_encode(array('userid'=>'37100000297230','username'=>'37100000297230','mac'=>'6C:EF:C6:00:C1:86','wifi_mac'=>'00:00:00:00:00:00'));
        $param = json_decode($data,true);
        
        //记录日志
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/appmac/';
        if (!file_exists($file_path)) {
            mkdir($file_path);
        }
        @chmod($file_path, 0777);        
        file_put_contents($file_path.'app-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s')." || ".$data."\n",FILE_APPEND);
        
        //上报参数
        $info['username'] = trim($param['username']);
        $info['mac'] = trim(strtoupper($param['mac']));
        $info['wifi_mac'] = trim(strtoupper($param['wifi_mac']));
        if(empty($info['wifi_mac']) || $info['wifi_mac']=='00:00:00:00:00:00'){
           $info['wifi_mac'] = $info['mac'];
        }
        
        $create_date = date('Y-m-d H:i:s');        
        
        //var_dump($data);
        if(!empty($param) && $this->mac_validate($info['mac'])){            
            //device_map 中查询用户是否已绑定mac地址
            $Model = new \Think\Model();
            $db_config = C("DB125_CONFIG");    
            $device_sql = "select * from bims_customer_device_map where user_name = '".$info['username']."'";
            $device_map_info = $Model->db(1,$db_config)->query($device_sql);  
            //dump($device_map_info);
            
            //user_name存在并且mac为空,执行开户
            if($device_map_info[0]['user_name'] && empty($device_map_info[0]['mac'])){
                //绑定mac地址
                $device_map_sql = "UPDATE bims_customer_device_map set mac='".$info['mac']."',up_time='".$create_date."' where user_name='".$info['username']."'";
                $device_map_result = $Model->db(1,$db_config)->execute($device_map_sql);  
                
                $this->log('/data/appmac/', 'result-'.date('Y-m-d').'.txt', $device_map_sql);
                //入mac地址到device 
                if($device_map_result){                    
                    $device_sno_sql = "select * from bims_device_sno where user_id='".$info['username']."'";              
                    $device_sno_info = $Model->db(1,$db_config)->query($device_sno_sql);
                    
                    //code city_code去掉前面0+mac共 (15位)
                    $mac_no_do = str_replace(':','',$info['mac']);
                    $code = substr($device_map_info[0]['city_code'],1,3).$mac_no_do;
                    
                    //city_code(4位) + mac(12位) 
                    $sno = $device_map_info[0]['city_code'].$mac_no_do;
                    
                    //ysten_id 32位
                    $ysten_id = $sno."000000".substr(number_format(microtime(true),10,'',''),-10);
                    if($device_sno_info[0]['user_id']){
                        $result_insert = $Model->db(1,$db_config)->execute("UPDATE bims_device_sno set sn_no='".$sno."',ysten_id='".$ysten_id."',update_date='".$create_date."',status=0,city_code='".$device_map_info[0]['city_code']."' where user_id='".$info['username']."' and status=-1");
                        $this->log('/data/appmac/', 'bims_device_sno-'.date('Y-m-d').'.txt', $result_insert.'||'.$ysten_id."|| update");                                              
                    }
                    else{
                        $result_insert = $Model->db(1,$db_config)->execute("INSERT INTO bims_device_sno (`sn_no`,`user_id`,`create_date`,`ysten_id`,`status`,`city_code`)values('".$sno."','".$info['username']."','".$create_date."','".$ysten_id."',0,'".$device_map_info[0]['city_code']."')"); //记录生成的串号
                        $this->log('/data/appmac/', 'bims_device_sno-'.date('Y-m-d').'.txt', $result_insert.'||'.$ysten_id."|| insert");                        
                    }
                    
                    //判断bims_device 中是否存，存在则更新，否则新增
                    $device_sql = "select `mac` from bims_device where mac='".$info['mac']."'";
                    $device_info = $Model->db(1,$db_config)->query($device_sql);                                        
                    if($device_info[0]['mac']){
                        $device_sql = "UPDATE bims_device set code='".$code."',ysten_id='".$ysten_id."',sno='".$sno."',mac='".$info['mac']."',wifi_mac='".$info['wifi_mac']."',customer_id='".$device_map_info[0]['customer_id']."',type='YSTEN',rj_mac='".$info['mac']."',expire_date='2099-12-31 23:59:59',state='NONACTIVATED',bind_type='UNBIND',
                                       distribute_state='DISTRIBUTE',is_return_ystenId='1',ip_address='1',is_lock='UNLOCKED',product_no='90GW2017YN0711',is_sync='WAITSYNC',description='".$device_map_info[0]['product_desc']."' where mac='".$info['mac']."'";
                       
                        $device_return = $Model->db(1,$db_config)->execute($device_sql);                        
                    }
                    else{
                        $device_sql = "INSERT INTO bims_device (`code`,`ysten_id`,`sno`,`mac`,`wifi_mac`,`create_date`,`customer_id`,`type`,`rj_mac`,`expire_date`,`state`,`bind_type`,`distribute_state`,`is_return_ystenId`,`ip_address`,`is_lock`,`product_no`,`is_sync`,`description`)values(
                            '".$code."','".$ysten_id."','".$sno."','".$info['mac']."','".$info['wifi_mac']."','".$create_date."','".$device_map_info[0]['customer_id']."','YSTEN','".$info['mac']."','2099-12-31 23:59:59','NONACTIVATED','UNBIND','DISTRIBUTE','1','1','UNLOCKED','90GW2017YN0711','WAITSYNC','".$device_map_info[0]['product_desc']."'
                            )";  
                        
                        $device_return = $Model->db(1,$db_config)->execute($device_sql);
                    }                    
                    $this->log('/data/appmac/', 'device-'.date('Y-m-d').'.txt', $device_sql);                   
                    
                    //入device_group-map                        
                    if(strtoupper($device_map_info[0]['user_group_id'])=='DJIPTV'){
                        if(stripos($device_map_info[0]['product_desc'],'华为')!==false){
                            $group_name = '华为';
                        }
                        if(stripos($device_map_info[0]['product_desc'],'中兴')!==false){
                            $group_name = '中兴';
                            
                        }
                        
                        $area_group_map = "select * from bims_area_device_group_map where code_name = 'DJIPTV' and group_name='".$group_name."'";
                    }
                    if(strtoupper($device_map_info[0]['user_group_id'])=='YLIPTV'){
                        if(stripos($device_map_info[0]['product_desc'],'华为')!==false){
                            $group_name = '华为';
                        }
                        if(stripos($device_map_info[0]['product_desc'],'中兴')!==false){
                            $group_name = '中兴';
                        
                        }                        
                        
                        $area_group_map = "select * from bims_area_device_group_map where code_name = 'YLIPTV' and group_name='".$group_name."'";
                    }
                
                    $area_group_map_info = $Model->db(1,$db_config)->query($area_group_map);                
                    $group_id = $area_group_map_info[0]['device_group_id'];           
                    
                    //判断ysten_id 是否存在，存在则修改device_group_id ,否则新增
                    $group_map_sql = "select `ysten_id` from bims_device_group_map where ysten_id = '".$ysten_id."'";
                    $group_map_info = $Model->db(1,$db_config)->query($group_map_sql);                 
                    if($group_map_info[0]['ysten_id']){
                        $group_map_sql = "UPDATE bims_device_group_map set device_group_id='".$group_id."',user_id='".$info['username']."',update_date='".$create_date."' where ysten_id='".$ysten_id."'";
                        $group_map_return = $Model->db(1,$db_config)->execute($group_map_sql);                        
                    }
                    else{                   
                        $group_map_sql = "INSERT INTO bims_device_group_map (`ysten_id`,`device_group_id`,`create_date`,`user_id`)values(
                            '".$ysten_id."','".$group_id."','".$create_date."','".$info['username']."')";                       
                        $group_map_return = $Model->db(1,$db_config)->execute($group_map_sql);                    
                    }                   
                    $this->log('/data/appmac/', 'device_group_map'.date('Y-m-d').'.txt', $group_map_sql);
                    
                    //发送行业认证
                    $act = 'create';
                    $this->log('/data/appmac/', 'tradec_update-'.date('Y-m-d').'.txt', 'mac:'.$info['mac'].' || code:'.$code.' || act:'.$act);
                    
                    $tradec_result = $this->tradec($info['mac'], $code, $act);

                }
            }
            
            //file_put_contents($file_path.'123456.txt', date('Y-m-d H:i:s')." || ".$device_map_info[0]['user_name'].'===='.$device_map_info[0]['mac']."\n",FILE_APPEND);
            
            //user_name存在，mac地址存在并且和上报的mac地址不同时执行变更
            if($device_map_info[0]['user_name'] && $device_map_info[0]['mac'] && $device_map_info[0]['mac']!==$info['mac']){  
                //$this->log('/data/appmac/','666666.txt', $device_map_info[0]['mac']);
                //重新组合新的ystenid
                $device_sno_sql = "select * from bims_device_sno where user_id='".$info['username']."'";
                $device_sno_info = $Model->db(1,$db_config)->query($device_sno_sql);  
                $new_ysten_id = substr($device_sno_info[0]['ysten_id'],0,4) . str_replace(':','', strtoupper($info['mac'])) . substr($device_sno_info[0]['ysten_id'],-16);                
                $sno = substr($device_sno_info[0]['ysten_id'],0,4) . str_replace(':','', strtoupper($info['mac']));
                $code = substr($sno,1); //code 就是sno号去掉前面的0
                
                //$this->log('/data/appmac/','666666.txt', $new_ysten_id.'||'.$sno.'||'.$code);
                
                //更新bims_device               
                $device_sql = "UPDATE bims_device set code='".$code."',ysten_id='".$new_ysten_id."',sno='".$sno."',mac='".$info['mac']."',wifi_mac='".$info['wifi_mac']."',upgrade_date='".$create_date."',type='YSTEN',rj_mac='".$info['mac']."',expire_date='2099-12-31 23:59:59',state='NONACTIVATED',bind_type='UNBIND',
                                       distribute_state='DISTRIBUTE',is_return_ystenId='1',ip_address='1',is_lock='UNLOCKED',product_no='90GW2017YN0711',is_sync='WAITSYNC',description='".$device_map_info[0]['product_desc']."' where mac='".$info['mac']."'";                
                $result_device = $Model->db(1,$db_config)->execute($device_sql);  
                $this->log('/data/appmac/', 'device-'.date('Y-m-d').'.txt', $device_sql);
                                
                //更新bims_device_group_map , 如果存在则更新，否则添加
                $bims_device_group_map_sql = "select `ysten_id`,`user_id` from bims_device_group_map where user_id = '".$device_map_info[0]['user_name']."'";
                $bims_device_group_map_info = $Model->db(1,$db_config)->query($bims_device_group_map_sql); 

                if(empty($bims_device_group_map_info[0]['user_id'])){
                    //获取group_id
                    $customer_device_map_sql = "select * from bims_customer_device_map where user_name='".$info['username']."'";
                    $customer_device_map_info = $Model->db(1,$db_config)->query($customer_device_map_sql);
                                      
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
                    
                    
                    $area_group_map_info = $Model->db(1,$db_config)->query($area_group_map);
                    $group_id = $area_group_map_info[0]['device_group_id'];                    
                    
                    //新增bims_device_group_map记录
                    $bims_device_group_map_sql = "INSERT INTO bims_device_group_map (`ysten_id`,`device_group_id`,`create_date`,`user_id`)values(
                            '".$new_ysten_id."','".$group_id."','".$create_date."','".$info['username']."')";
                    $device_group_map_return = $Model->db(1,$db_config)->execute($bims_device_group_map_sql);                    
                }
                else{               
                    $bims_device_group_map_sql = "UPDATE bims_device_group_map set ysten_id='".$new_ysten_id."',update_date='".$create_date."' where user_id='".$info['username']."'";
                    $device_group_map_return = $Model->db(1,$db_config)->execute($bims_device_group_map_sql);
                } 
                
                $this->log('/data/appmac/', 'device_group_map-'.date('Y-m-d').'.txt', $bims_device_group_map_sql);
                
                //更新 bims_customer_device_map
                $customer_device_map_sql = "UPDATE bims_customer_device_map set mac='".$info['mac']."',up_time='".$create_date."' where user_name ='".$info['username']."'";              
                $result_customer_device_map = $Model->db(1,$db_config)->execute($customer_device_map_sql);                
             
                //更新bims_device_sno
                $customer_device_sno_sql = "UPDATE bims_device_sno  set sn_no='".$sno."',status=1,ysten_id='".$new_ysten_id."',update_date='".$create_date."' where user_id='".$info['username']."'";
                $result_device_sno = $Model->db(1,$db_config)->execute($customer_device_sno_sql); 
                
                //file_put_contents($file_path.'88888888.txt', date('Y-m-d H:i:s')." || ".$info['mac'].'=='.$code.'=='.$act."\n",FILE_APPEND);
                
                $device_sql = "select `code` from bims_device where mac='".$info['mac']."'";
                $device_info = $Model->db(1,$db_config)->query($device_sql);
                
                //发送行业认证
                $act = 'update';
                $tradec_result = $this->tradec($info['mac'], $code, $act);               
                
                $data = array('Result'=>0,'Errordesc'=>'Change Mac to bind !');
                echo json_encode($data); 
                
            }
            //user_name存在，mac地址存在并且和上报的mac地址相同时执行变更,保持和联通变更同步
            if($device_map_info[0]['user_name'] && $device_map_info[0]['mac'] && $device_map_info[0]['mac']===$info['mac']){ 
                
                //更新 bims_customer_device_map
                $customer_device_map_sql = "UPDATE bims_customer_device_map set up_time='".$create_date."' where user_name='".$info['username']."' and mac='".$info['mac']."'";
                $result_customer_device_map = $Model->db(1,$db_config)->execute($customer_device_map_sql);           
                
                $device_sno_sql = "select * from bims_device_sno where user_id='".$info['username']."'";                
                $device_sno_info = $Model->db(1,$db_config)->query($device_sno_sql);   
                if($device_sno_info[0]['ysten_id']){
                    $ysten_id = substr($device_sno_info[0]['ysten_id'],0,4) . str_replace(':','', strtoupper($info['mac'])) . substr($device_sno_info[0]['ysten_id'],-16);
                    $sno = substr($device_sno_info[0]['ysten_id'],0,4) . str_replace(':','', strtoupper($info['mac']));                              
                    $device_sno_sql = "UPDATE bims_device_sno set sn_no ='".$sno."',update_date='".$create_date."',status=1 where user_id='".$info['username']."'";
                    $device_sno_info = $Model->db(1,$db_config)->execute($device_sno_sql);
                    //dump($device_sno_sql);exit;
                }
                else{
                    //code city_code去掉前面0+mac共 (15位)
                    $mac_no_do = str_replace(':','',$info['mac']);
                    $code = substr($device_map_info[0]['city_code'],1,3).$mac_no_do;                    
                    //city_code(4位) + mac(12位)
                    $sno = $device_map_info[0]['city_code'].$mac_no_do;                    
                    //ysten_id 32位
                    $ysten_id = $sno."000000".substr(number_format(microtime(true),10,'',''),-10);   
                    $result_insert = $Model->db(1,$db_config)->execute("INSERT INTO bims_device_sno (`sn_no`,`user_id`,`create_date`,`ysten_id`,`status`,`city_code`)values('".$sno."','".$info['username']."','".$create_date."','".$ysten_id."',0,'".$device_map_info[0]['city_code']."')"); //记录生成的串号
                    $this->log('/data/appmac/', 'bims_device_sno(update)-'.date('Y-m-d').'.txt', $result_insert.'||'.$ysten_id."|| insert");                    
                    
                }
                               
                //从device_sno 中读取最新信息
                $device_sno_sql = "select * from bims_device_sno where user_id='".$info['username']."'";                
                $device_sno_info = $Model->db(1,$db_config)->query($device_sno_sql); 
                $ysten_id = $device_sno_info[0]['ysten_id'];
                $sno = $device_sno_info[0]['sn_no'];
                $code = substr($device_sno_info[0]['sn_no'], 1);
                
                //判断bims_device是否生成ysten_id
                $device_sql = "select `mac`,`code` from bims_device where mac='".$info['mac']."'";
                $device_info = $Model->db(1,$db_config)->query($device_sql);
                if($device_info[0]['mac']){
                    $device_sql = "UPDATE bims_device set code='".$code."',ysten_id='".$ysten_id."',sno='".$sno."',wifi_mac='".$info['wifi_mac']."',customer_id='".$device_map_info[0]['customer_id']."' where mac='".$info['mac']."'";
                    $device_info = $Model->db(1,$db_config)->execute($device_sql);
                }
                else{
                    $device_sql = "INSERT INTO bims_device (`code`,`ysten_id`,`sno`,`mac`,`wifi_mac`,`create_date`,`customer_id`,`type`,`rj_mac`,`expire_date`,`state`,`bind_type`,`distribute_state`,`is_return_ystenId`,`ip_address`,`is_lock`,`product_no`,`is_sync`,`description`)values(
                            '".$code."','".$ysten_id."','".$sno."','".$info['mac']."','".$info['wifi_mac']."','".$create_date."','".$device_map_info[0]['customer_id']."','YSTEN','".$info['mac']."','2099-12-31 23:59:59','NONACTIVATED','UNBIND','DISTRIBUTE','1','1','UNLOCKED','90GW2017YN0711','WAITSYNC','".$device_map_info[0]['product_desc']."'
                            )";
                
                    $device_return = $Model->db(1,$db_config)->execute($device_sql);
                }
                $this->log('/data/appmac/', 'device-(update)'.date('Y-m-d').'.txt', $device_sql);                
                                
                
                //查找变更后的分组                                   
                if(strtoupper($device_map_info[0]['user_group_id'])=='DJIPTV'){
                    if(stripos($device_map_info[0]['product_desc'],'华为')!==false){
                        $group_name = '华为';
                    }
                    if(stripos($device_map_info[0]['product_desc'],'中兴')!==false){
                        $group_name = '中兴';
                
                    }
                
                    $area_group_map = "select * from bims_area_device_group_map where code_name = 'DJIPTV' and group_name='".$group_name."'";
                }
                if(strtoupper($device_map_info[0]['user_group_id'])=='YLIPTV'){
                    if(stripos($device_map_info[0]['product_desc'],'华为')!==false){
                        $group_name = '华为';
                    }
                    if(stripos($device_map_info[0]['product_desc'],'中兴')!==false){
                        $group_name = '中兴';
                
                    }
                
                    $area_group_map = "select * from bims_area_device_group_map where code_name = 'YLIPTV' and group_name='".$group_name."'";
                } 
                $area_group_map_info = $Model->db(1,$db_config)->query($area_group_map);
                $group_id = $area_group_map_info[0]['device_group_id'];                
                
                
                $group_map_sql = "select * from bims_device_group_map where ysten_id = '".$ysten_id."'";
                $group_map_info = $Model->db(1,$db_config)->query($group_map_sql);
                if($group_map_info[0]['ysten_id'] && $group_map_info[0]['device_group_id']!==$group_id && ($group_map_info[0]['device_group_id']!='2087' || $group_map_info[0]['device_group_id']!='1832')){
                    $bims_device_group_map_sql = "UPDATE bims_device_group_map set ysten_id='".$ysten_id."',device_group_id=".$group_id.",update_date='".$create_date."' where user_id='".$info['username']."'";
                    $device_group_map_return = $Model->db(1,$db_config)->execute($bims_device_group_map_sql); 
                }
                if(empty($group_map_info[0]['ysten_id'])){
                    $group_map_sql = "INSERT INTO bims_device_group_map (`ysten_id`,`device_group_id`,`create_date`,`user_id`)values(
                            '".$ysten_id."','".$group_id."','".$create_date."','".$info['username']."')";
                    $group_map_return = $Model->db(1,$db_config)->execute($group_map_sql);
                }
                $this->log('/data/appmac/', 'device_group_map-(update)'.date('Y-m-d').'.txt', $group_map_sql);                
                                               
                
                //发送行业认证
                $act = 'update';
                if(empty($device_info[0]['code'])){
                  $this->tradec($info['mac'],$code,$act);
                }
                
            }                         
        }
        else{
            $data = array('Result'=>-1,'Errordesc'=>'Request parameter error !');
             
            echo  json_encode($data);
        }
       exit;
    }    
    
    public function tradec($mac='',$code='',$act=''){
        //$url = 'http://sl.51le.cn:8083/User/Device/addMacsn';
        $url = 'http://cert.hn.cu.sllhtv.com:8080/User/Device/addMacsn';
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
        
        //分发成功更新串号表
        $return = json_decode($output,true);  
        
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/appmac/';
        if (!file_exists($file_path)) {
            mkdir($file_path);
        }
        @chmod($file_path, 0777);
        
        //记录日志
        file_put_contents($file_path.'update_sno-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s'). " || ".$act." || " .$output."\n",FILE_APPEND); 
        
        file_put_contents($file_path.'app_ubss_trade-'.date('Y-m-d').'.txt', date('Y-m-d H:i:s'). " || " .$mac." || ".$code." || ".$act." || " .$output."\n",FILE_APPEND);
        
        if($return['Result']==0){
            $Model = new \Think\Model();
            $db_config = C("DB125_CONFIG");    
            $device_sno_sql = "UPDATE bims_device_sno set status=1 where sn_no = '".$sno."'";         
            $device_sno_result = $Model->db(1,$db_config)->execute($device_sno_sql); 
            $data = array('Result'=>0,'Errordesc'=>'Mac bind and Report Successful !');
            echo json_encode($data);            
        }
        else{
            $data = array('Result'=>-1,'Errordesc'=>'Information already exists!!');
            echo json_encode($data);
        }
        exit;
    }
    
public function ubss_post()
{
$list= array(

//array('UserID'=>'', 'Login_name'=>'','Password'=>'','Citycode'=>'','Countycode'=>'','broadbandID'=>'','OLTOffice'=>'','ProductID'=>'','ProductDesc'=>'','UsergroupID'=>''),
array('UserID'=>'37300000148251','Login_name'=>'37300000148251','Password'=>'123456','Citycode'=>'0373', 'Countycode'=>'12124',
'broadbandID'=>'37308384765','OLTOffice'=>'13513','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148314','Login_name'=>'37300000148314','Password'=>'123456','Citycode'=>'0373', 'Countycode'=>'11561',
'broadbandID'=>'37303362195','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),                     
                                                    
array('UserID'=>'39500000115243', 'Login_name'=>'39500000115243','Password'=>'123456','Citycode'=>'0395','Countycode'=>'13142',
'broadbandID'=>'39508664557','OLTOffice'=>'13388','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148230', 'Login_name'=>'37300000148230','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12129',
'broadbandID'=>'37306204587','OLTOffice'=>'13558','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148238', 'Login_name'=>'37300000148238','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12129',
'broadbandID'=>'37306622612','OLTOffice'=>'13558','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148240', 'Login_name'=>'37300000148240','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12124',
'broadbandID'=>'37308384737','OLTOffice'=>'13513','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37500000139537', 'Login_name'=>'37500000139537','Password'=>'123456','Citycode'=>'0375','Countycode'=>'14260',
'broadbandID'=>'37505809713','OLTOffice'=>'42509','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37500000139536', 'Login_name'=>'37500000139536','Password'=>'123456','Citycode'=>'0375','Countycode'=>'14260',
'broadbandID'=>'37505756426','OLTOffice'=>'42509','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),

array('UserID'=>'37600000172768', 'Login_name'=>'37600000172768','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340924','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148314', 'Login_name'=>'37300000148314','Password'=>'123456','Citycode'=>'0373','Countycode'=>'11561',
'broadbandID'=>'37303362195','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148292', 'Login_name'=>'37300000148292','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308909191','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172757', 'Login_name'=>'37600000172757','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14426',
'broadbandID'=>'37603923607','OLTOffice'=>'30062','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148301', 'Login_name'=>'37300000148301','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938641','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172752', 'Login_name'=>'37600000172752','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14425',
'broadbandID'=>'37602678534','OLTOffice'=>'32435','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172731', 'Login_name'=>'37600000172731','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340263','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148292', 'Login_name'=>'37300000148292','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308909191','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172734', 'Login_name'=>'37600000172734','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604243423','OLTOffice'=>'29402','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172736', 'Login_name'=>'37600000172736','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340010','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172741', 'Login_name'=>'37600000172741','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604050341','OLTOffice'=>'29402','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172743', 'Login_name'=>'37600000172743','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604066606','OLTOffice'=>'29402','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),

array('UserID'=>'37600000172754', 'Login_name'=>'37600000172754','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604395092','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172756', 'Login_name'=>'37600000172756','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340684','OLTOffice'=>'29437','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172759', 'Login_name'=>'37600000172759','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604955255','OLTOffice'=>'29437','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172762', 'Login_name'=>'37600000172762','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340946','OLTOffice'=>'29437','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172763', 'Login_name'=>'37600000172763','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604955373','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172768', 'Login_name'=>'37600000172768','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340924','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37800000122940', 'Login_name'=>'37800000122940','Password'=>'123456','Citycode'=>'0371','Countycode'=>'13053',
'broadbandID'=>'37807488111','OLTOffice'=>'13307','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148292', 'Login_name'=>'37300000148292','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308909191','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172768', 'Login_name'=>'37600000172768','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604340924','OLTOffice'=>'29463','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148301', 'Login_name'=>'37300000148301','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938641','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148314', 'Login_name'=>'37300000148314','Password'=>'123456','Citycode'=>'0373','Countycode'=>'11561',
'broadbandID'=>'37303362195','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),

array('UserID'=>'37600000172757', 'Login_name'=>'37600000172757','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14426',
'broadbandID'=>'37603923607','OLTOffice'=>'30062','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148292', 'Login_name'=>'37300000148292','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308909191','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148251', 'Login_name'=>'37300000148251','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12124',
'broadbandID'=>'37308384765','OLTOffice'=>'13513','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172760', 'Login_name'=>'37600000172760','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14425',
'broadbandID'=>'37602987784','OLTOffice'=>'32431','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148262', 'Login_name'=>'37300000148262','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308783551','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148265', 'Login_name'=>'37300000148265','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308927097','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148271', 'Login_name'=>'37300000148271','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308873789','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148292', 'Login_name'=>'37300000148292','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308909191','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148296', 'Login_name'=>'37300000148296','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308781967','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148329', 'Login_name'=>'37300000148329','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308793205','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148301', 'Login_name'=>'37300000148301','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938641','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148338', 'Login_name'=>'37300000148338','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938219','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37700000161184', 'Login_name'=>'37700000161184','Password'=>'123456','Citycode'=>'0377','Countycode'=>'11553',
'broadbandID'=>'37703571778','OLTOffice'=>'23294','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37000000127085', 'Login_name'=>'37000000127085','Password'=>'123456','Citycode'=>'0370','Countycode'=>'14413',
'broadbandID'=>'37005173678','OLTOffice'=>'17087','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),

array('UserID'=>'37300000148317', 'Login_name'=>'37300000148317','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12126',
'broadbandID'=>'37304212356','OLTOffice'=>'13523','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148309', 'Login_name'=>'37300000148309','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12126',
'broadbandID'=>'37304105872','OLTOffice'=>'13512','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148286', 'Login_name'=>'37300000148286','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12126',
'broadbandID'=>'37304239102','OLTOffice'=>'13512','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148235', 'Login_name'=>'37300000148235','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12126',
'broadbandID'=>'37304102951','OLTOffice'=>'13631','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148228', 'Login_name'=>'37300000148228','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12126',
'broadbandID'=>'37304476861','OLTOffice'=>'13631','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148346', 'Login_name'=>'37300000148346','Password'=>'123456','Citycode'=>'0373','Countycode'=>'11561',
'broadbandID'=>'37303380694','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148335', 'Login_name'=>'37300000148335','Password'=>'123456','Citycode'=>'0373','Countycode'=>'11561',
'broadbandID'=>'37303351055','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148325', 'Login_name'=>'37300000148325','Password'=>'123456','Citycode'=>'0373','Countycode'=>'11561',
'broadbandID'=>'37303981363','OLTOffice'=>'13020','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148262', 'Login_name'=>'37300000148262','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308783551','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148265', 'Login_name'=>'37300000148265','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308927097','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148271', 'Login_name'=>'37300000148271','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308873789','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148296', 'Login_name'=>'37300000148296','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308781967','OLTOffice'=>'13493','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148301', 'Login_name'=>'37300000148301','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938641','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148329', 'Login_name'=>'37300000148329','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308793205','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148338', 'Login_name'=>'37300000148338','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12122',
'broadbandID'=>'37308938219','OLTOffice'=>'13489','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148291', 'Login_name'=>'37300000148291','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12127',
'broadbandID'=>'37304562946','OLTOffice'=>'13135','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),

array('UserID'=>'37300000148276', 'Login_name'=>'37300000148276','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12124',
'broadbandID'=>'37308287054','OLTOffice'=>'13522','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148269', 'Login_name'=>'37300000148269','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12124',
'broadbandID'=>'37308384447','OLTOffice'=>'13522','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37600000172725', 'Login_name'=>'37600000172725','Password'=>'123456','Citycode'=>'0376','Countycode'=>'14429',
'broadbandID'=>'37604226101','OLTOffice'=>'29402','ProductID'=>'40011372','ProductDesc'=>'IPTV党员远程教育（华为）','UsergroupID'=>'DJIPTV'),
array('UserID'=>'37300000148307', 'Login_name'=>'37300000148307','Password'=>'123456','Citycode'=>'0373','Countycode'=>'12124',
'broadbandID'=>'37308316246','OLTOffice'=>'13522','ProductID'=>'40011373','ProductDesc'=>'IPTV党员远程教育（中兴）','UsergroupID'=>'DJIPTV'),
    
    
    



);         
      
  //dump($list);exit;
        
      foreach ($list as $val){  
          
        $post_subscription =
        '<?xml version="1.0" encoding="utf-8"?>
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <SOAP-ENV:Body>
                    <m:Subscription xmlns:m="http://tempuri.org/BOSS.xsd">
                    <reqSubscription>
                    <UserID>'.trim($val['UserID']).'</UserID>
                    <Login_name>'.trim($val['Login_name']).'</Login_name>
                    <Password>'.trim($val['Password']).'</Password>
                    <Citycode>'.trim($val['Citycode']).'</Citycode>
                    <Countycode>'.trim($val['Countycode']).'</Countycode>
                    <broadbandID>'.trim($val['broadbandID']).'</broadbandID>
                    <OLTOffice>'.trim($val['OLTOffice']).'</OLTOffice>
                    <MAC></MAC>
                    <OLTType></OLTType>
                    <OLTManageIP></OLTManageIP>
                    <OLTPort></OLTPort>
                    <ONTDataPort></ONTDataPort>
                    <ONTType></ONTType>
                    <ONTDataSVLAN></ONTDataSVLAN>
                    <ONTDataCVLAN></ONTDataCVLAN>
                    <Productlist>
                    <ProductListItem>
                    <ProductID>'.trim($val['ProductID']).'</ProductID>
                    <ProductDesc>'.trim($val['ProductDesc']).'</ProductDesc>
                    </ProductListItem>
                    </Productlist>
                    <UsergroupID>'.trim($val['UsergroupID']).'</UsergroupID>
                    </reqSubscription>
                    </m:Subscription>
                  </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>';
           
        $url = C('REPORT_MAC_URL'); //'http://192.168.11.30:5188/home/IptvService';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_subscription);
        $output = curl_exec($ch);
        curl_close($ch);
        
      }
        var_dump($output);exit;
    
    }
    
    
    public function client()
    {       
        $post_subscription =
            '<?xml version="1.0" encoding="utf-8"?>
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                  <SOAP-ENV:Body>
                    <m:Subscription xmlns:m="http://tempuri.org/BOSS.xsd">  
                    <reqSubscription>
                    <UserID>37500000139532</UserID> 
                    <Login_name>37500000139532</Login_name> 
                    <Password>123456</Password> 
                    <Citycode>0375</Citycode>
                    <Countycode>14254</Countycode>
                    <broadbandID>037505150820</broadbandID>
                    <OLTOffice>14598</OLTOffice>
                    <MAC></MAC>
                    <OLTType></OLTType>
                    <OLTManageIP></OLTManageIP>
                    <OLTPort></OLTPort>
                    <ONTDataPort></ONTDataPort>
                    <ONTType></ONTType>
                    <ONTDataSVLAN></ONTDataSVLAN>
                    <ONTDataCVLAN></ONTDataCVLAN>
                    <Productlist>
                    <ProductListItem>
                    <ProductID>40011372</ProductID>
                    <ProductDesc>IPTV党员远程教育（华为）</ProductDesc> 
                    </ProductListItem>
                    </Productlist>
                    <UsergroupID>DJIPTV</UsergroupID> 
                    </reqSubscription>
                    </m:Subscription>
                  </SOAP-ENV:Body>
                </SOAP-ENV:Envelope>';

          $post_change_subscription =
              '<?xml version="1.0" encoding="UTF-8"?>
                <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                <SOAP-ENV:Body><m:ChangeSubscription xmlns:m="http://tempuri.org/BOSS.xsd">
                <reqChangeSubscription>
                <UserID>test-01</UserID> 
                <Login_name>test-01</Login_name> 
                <Password>123456</Password> 
                <Citycode>0999</Citycode>
                <Countycode>20262</Countycode>
                <broadbandID>039405769345</broadbandID>
                <OLTOffice>20338</OLTOffice>
                <MAC></MAC>
                <OLTType></OLTType>
                <OLTManageIP></OLTManageIP>
                <OLTPort></OLTPort>
                <ONTDataPort></ONTDataPort>
                <ONTType></ONTType>
                <ONTDataSVLAN></ONTDataSVLAN>
                <ONTDataCVLAN></ONTDataCVLAN>
                <Productlist>
                <ProductListItem>
                <ProductID>40011399</ProductID>
                <ProductDesc>hw_test华为</ProductDesc> 
                </ProductListItem>
                </Productlist>
                <UsergroupID>DJIPTV</UsergroupID> 
                </reqChangeSubscription>
                </m:ChangeSubscription></SOAP-ENV:Body></SOAP-ENV:Envelope>';  
          
          $post_un_subscription =
              '<?xml version="1.0" encoding="UTF-8"?>
               <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
               <SOAP-ENV:Body><m:Unsubscription xmlns:m="http://tempuri.org/BOSS.xsd">
               <reqUnsubscription>
               <UserID>37100000352993</UserID>  
               </reqUnsubscription>
               </m:Unsubscription></SOAP-ENV:Body></SOAP-ENV:Envelope>';
          
          
             $url = C('REPORT_MAC_URL'); //'http://192.168.11.30:5188/home/IptvService';
             $ch = curl_init();
             curl_setopt($ch, CURLOPT_URL, $url);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
             curl_setopt($ch, CURLOPT_POST, 1);
             curl_setopt($ch, CURLOPT_POSTFIELDS, $post_subscription);
             $output = curl_exec($ch);
             curl_close($ch);
             
             var_dump($output);exit;
        
    } 
    
   
    public function mac_validate($mac='', $wifi_mac=''){
        //校验mac格式
        $pattern_mac="/[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f]/i";
        $result_mac = preg_match($pattern_mac, $mac);
                
        //校验mac 不全为0
        $mac_str = str_replace(':', '', $mac);
        $mac_len = strlen($mac_str);
        if($mac_str =='000000000000' || $mac_len!=12 ){
            $mac_status = FALSE;
        }
        else{
            return TRUE;
        }       
    }
    
    public function test(){
              
        $Model = new \Think\Model();
        $db_config = "mysql://root:ysten123@192.168.11.24:3306/bims";
        //$customer_info = $Model->db(1,$db_config)->query("select * from bims_customer where code = '201410151437040011752'");
        $Citycode = '20160651255';
        $UserID = '201706151437040011759';
        $Login_name = '201706151437040011759';
        $Password = '259868';
        $Productlist = '322295';
        
        $customer_info = $Model->db(1,$db_config)->query("select * from bims_customer where code = '".$Citycode."'");
        if(empty($customer_info)){
            //bims_customer 添加记录
            $customer_sql = "INSERT INTO bims_customer (`code`,`user_id`,`login_name`,`password`,`create_date`)values(
                           '".$Citycode."','".$UserID."','".$Login_name."','".md5($Password)."','".$create_time."')";
           $result_customer = $Model->db(1,$db_config)->execute($customer_sql);
           $customer_id = $Model->db(1,$db_config)->getLastInsID();
           
           //bims_customer_devcie_map 添加记录
           $device_map_sql = "INSERT INTO bims_customer_device_map (`customer_id`,`city_code`,`user_name`,`product_id`)values(              
                            '".$customer_id."','".$Citycode."','".$UserID."','".$Productlist."')";
           $device_map_result = $Model->db(1,$db_config)->execute($device_map_sql);
        }        
        var_dump($Model->db(1,$db_config)->getLastInsID());exit;
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
?>