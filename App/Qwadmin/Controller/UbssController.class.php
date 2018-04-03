<?php
namespace  Qwadmin\Controller;

class UbssController extends  ComController
{
    //上报
    public function index($p = 1)
    {   
        $p = intval($p) > 0 ? $p : 1;        
        $pagesize = 25;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';        
               
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
                
        $at_time_start = isset($_GET['at_time_start']) ? $_GET['at_time_start'] : '';
        $at_time_end = !empty($_GET['at_time_end']) ? $_GET['at_time_end']." 23:59:59" : $at_time_start." 23:59:59";
        $city_code = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        $condition = ' 1=1 ';
        if ($at_time_start && $at_time_end) {
            $condition .= " and at_time >= '".$at_time_start."' and at_time <= '".$at_time_end."' ";
        }
        $type = isset($_GET['type']) ? $_GET['type'] : '';        
        if($type){
            $condition .= " and user_group_id='".$type."' ";
        }
        if($city_code){
            $condition .= "  and city_code='".$city_code."' ";
        }        
        
        $area_list = $this->create_select($this->get_area(), $city_code);        
        
        //数量
        $device_sql_count = "SELECT count(*) FROM {$prefix}customer_device_map WHERE ".$condition." GROUP BY city_code,DATE_FORMAT(at_time,'%Y-%m-%d') ORDER BY at_time DESC";
        $count = count($Model->db(1,$db_config)->query($device_sql_count));        
        $this->assign('count',$count);
        
        //记录
        $device_sql = "SELECT count(*) as number,city_code,DATE_FORMAT(at_time,'%Y-%m-%d')as at_time   FROM bims_customer_device_map WHERE ".$condition." GROUP BY city_code,DATE_FORMAT(at_time,'%Y-%m-%d') ORDER BY at_time DESC limit ".$offset.",".$pagesize;
        $device_map_info = $Model->db(1,$db_config)->query($device_sql); 
        
        $device_info = array();
        foreach ($device_map_info as $k=>$v){           
            $device_info[$k] = $v;
            $device_info[$k]['area'] = $this->get_area($v['city_code']);
        }
        
        //统计上报总数
        $account_info = $Model->db(1,$db_config)->query("SELECT count(*) as count from {$prefix}customer_device_map WHERE ".$condition);
        $accounts = $account_info[0]['count'];
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('page', $page);
        $this->assign('device_map_info',$device_info);
        $this->assign('accounts',$accounts);
        $this->assign('user_type',$this->user_type($type));
        $this->assign('area_list',$area_list);
        $this->display();
    }
    
    //开户
    public function user_list($p = 1){
        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 80;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';

        $user_name = isset($_GET['user_name']) ? trim($_GET['user_name']) : '';
        $at_time_start = isset($_GET['at_time_start']) ? $_GET['at_time_start'] : '';
        $at_time_end = !empty($_GET['at_time_end']) ? $_GET['at_time_end']." 23:59:59" : $at_time_start." 23:59:59";        
        $where = ' and 1 = 1 ';
        if ($user_name) {
            $where .= "and user_name='".$user_name."' ";
        }
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        if ($status==1) {
            $where .= "and mac is not Null ";
        }
        if($status==2){
            $where .= "and mac is Null ";
        }
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if($type){
            $where .= "and user_group_id='".$type."' ";
        }
        $mac = isset($_GET['mac']) ? $_GET['mac'] : '';
        if($mac){
            $where .= "and mac ='".$mac."'";
        }       
        if($at_time_start && $at_time_end){            
            $where .= "and b.create_date >= '".$at_time_start."' and b.create_date <= '".$at_time_end."'";
        }      
        $city_code = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        if($city_code){
            $where .= "and a.city_code ='".$city_code."'";
        }
        $area_list = $this->create_select($this->get_area(), $city_code);
        
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
        //dump($db_config);
        
        //数量
        //$device_sql_count = "SELECT count(*) as count FROM {$prefix}customer_device_map WHERE user_group_id in('DJIPTV','YLIPTV') ".$where." ORDER BY at_time DESC";
        $device_sql_count = "SELECT count(*) as count FROM {$prefix}customer_device_map as a  left join {$prefix}customer as b ON b.user_id=a.user_name  WHERE a.user_group_id in('DJIPTV','YLIPTV') ".$where." ORDER BY b.create_date DESC";
        
        $device_info = $Model->db(1,$db_config)->query($device_sql_count);
        $count = $device_info[0]['count'];
        
        //记录
        //$device_sql = "SELECT a.*,b.broadband_id,b.county_code,b.olt_office,b.create_date FROM {$prefix}customer_device_map as a  left join {$prefix}customer as b ON b.user_id=a.user_name  WHERE a.user_group_id in('DJIPTV','YLIPTV') ".$where." ORDER BY b.create_date DESC limit ".$offset.",".$pagesize;
        //$device_map_info = $Model->db(1,$db_config)->query($device_sql);                  
        
        //dump($device_sql);exit;        
        
        //导出
        $export = isset($_GET['export']) ? $_GET['export'] : '';
        if($export){
            $device_sql = "SELECT a.*,b.broadband_id,b.county_code,b.olt_office,b.create_date FROM {$prefix}customer_device_map as a  left join {$prefix}customer as b ON b.user_id=a.user_name  WHERE a.user_group_id in('DJIPTV','YLIPTV') ".$where." ORDER BY b.create_date DESC";
            $device_map_info = $Model->db(1,$db_config)->query($device_sql);  
            if($device_map_info){
                $device_info = array();
                foreach ($device_map_info as $k=>$val){
                    $device_info[$k]=$val;
                    $device_info[$k]['city_code'] = $this->get_area($val['city_code']);
                    //$device_info[$k]['county_code'] = $this->get_iom_area($val['county_code']);
                }
            }            
            $this->expload($device_info);
        }
        else{
            //搜索
            $device_sql = "SELECT a.*,b.broadband_id,b.county_code,b.olt_office,b.create_date FROM {$prefix}customer_device_map as a  left join {$prefix}customer as b ON b.user_id=a.user_name  WHERE a.user_group_id in('DJIPTV','YLIPTV') ".$where." ORDER BY b.create_date DESC limit ".$offset.",".$pagesize;
            $device_map_info = $Model->db(1,$db_config)->query($device_sql);              
        }
                    
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('page', $page);
        $this->assign('device_map_info',$device_map_info);
        $this->assign('count',$count);
        $this->assign('area_list',$area_list);
        $this->display();        
        
    }
    
    public function index_list($p = 1){
        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 25;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';

        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';  
        $username = isset($_GET['username']) ? trim($_GET['username']) : '';
        $county_code = isset($_GET['county_code']) ? $this->get_iom_area(trim($_GET['county_code'])) : '';
        $where = ' and 1 = 1 ';
        if ($status==1) {
            $where .= "and a.mac is not Null ";
        }
        if($status==2){
            $where .= "and a.mac is Null ";
        }
        if ($mac) {
            $where .= "and a.mac='".$mac."'";
        }
        if($username){
            $where .= " and a.user_name='".$username."'";
        }
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if($type){
            $where .= "and a.user_group_id='".strtoupper($type)."' ";
        }        
        if($county_code){
           $where .= "and d.county_code='".$county_code."' ";
        }        
        
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");        
        
        $city_code = I('city_code');
        $at_time = I('at_time');
        
        $at_time_start = $at_time." 00:00:00";
        $at_time_end = $at_time." 23:59:59";
        
        $device_map_sql_count = "SELECT * FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id WHERE a.city_code='".$city_code."' AND a.at_time>'".$at_time_start."' AND a.at_time<'".$at_time_end."' ".$where." ORDER BY d.county_code,a.at_time DESC";
        $count = count($Model->db(1,$db_config)->query($device_map_sql_count));
        
        $device_map_sql = "SELECT * FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id WHERE a.city_code='".$city_code."' AND a.at_time>'".$at_time_start."' AND a.at_time<'".$at_time_end."' ".$where." ORDER BY d.county_code,a.at_time DESC limit ".$offset.",".$pagesize;
        $device_map_infos = $Model->db(1,$db_config)->query($device_map_sql);        
        
        if(!empty($device_map_infos)){
            foreach ($device_map_infos as $k=>$v){
                $device_map_info[$k] = $v;
                $device_map_info[$k]['county_code'] = $this->get_iom_area($v['county_code']);
            }
        }
        
        //dump($device_map_sql);exit;
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        
        $this->assign('count',$count);
        $this->assign('page', $page);
        $this->assign('device_map_info',$device_map_info);
        $this->display();
    }

    //激活
    public function active($p = 1)
    {
        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 25;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';
        
        $at_time_start = isset($_GET['at_time_start']) ? $_GET['at_time_start'] : '';
        $at_time_end = !empty($_GET['at_time_end']) ? $_GET['at_time_end']." 23:59:59" : $at_time_start." 23:59:59";
        $city_code = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        $county_code = isset($_GET['county_code']) ? $this->get_iom_area(trim($_GET['county_code'])) : '';
        $condition = ' ';
        if ($at_time_start && $at_time_end) {
            $condition .= "a.at_time >= '".$at_time_start."' and a.at_time <= '".$at_time_end."' and ";
        }
        if($city_code){
            $condition .= "a.city_code='".$city_code."' and ";
        }
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if($type){
            $condition .= "a.user_group_id='".strtoupper($type)."' and ";
        }
        if($county_code){
            $condition .= "b.county_code='".$county_code."' and ";
        }
        
        $area_list = $this->create_select($this->get_area(), $city_code);
        
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
    
        //数量
        $device_sql_count = "SELECT count(*) as count FROM {$prefix}customer_device_map a left join {$prefix}customer b on a.user_name=b.user_id WHERE ".$condition." a.mac is not NULL GROUP BY a.city_code,DATE_FORMAT(a.at_time,'%Y-%m-%d') ORDER BY a.at_time DESC";
        $count = count($Model->db(1,$db_config)->query($device_sql_count));
        $this->assign('count',$count);
        
        //记录
        $device_sql = "SELECT count(*) as number,a.city_code,DATE_FORMAT(a.at_time,'%Y-%m-%d') as at_time FROM {$prefix}customer_device_map a left join {$prefix}customer b on a.user_name=b.user_id WHERE ".$condition." a.mac is not NULL GROUP BY a.city_code,DATE_FORMAT(a.at_time,'%Y-%m-%d') ORDER BY a.at_time DESC limit ".$offset.",".$pagesize;
        $device_map_info = $Model->db(1,$db_config)->query($device_sql);
        $device_info = array();
        foreach ($device_map_info as $k=>$v){
            $device_info[$k] = $v;
            $device_info[$k]['area'] = $this->get_area($v['city_code']);
            //$device_info[$k]['county_code'] = $this->get_iom_area($v['county_code']);
            $accounts += $v['number'];
        }   
        
        //统计激活总数
        $account_info = $Model->db(1,$db_config)->query("SELECT count(*) as count from {$prefix}customer_device_map a left join {$prefix}customer b on a.user_name=b.user_id WHERE ".$condition." a.mac is not null");
        $accounts = $account_info[0]['count'];        
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('page', $page);
        $this->assign('device_map_info',$device_info);
        $this->assign('accounts',$accounts);
        $this->assign('area_list',$area_list);
        $this->assign('user_type',$this->user_type($type));
        $this->display();
    }    

    public function active_list($p = 1){
        $ref_url = I('server.HTTP_REFERER');
        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 25;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';

        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $mac = isset($_GET['mac']) ? trim($_GET['mac']) : '';
        $ysten_id = isset($_GET['ysten_id']) ? trim($_GET['ysten_id']) : '';  
        $county_code = isset($_GET['county_code']) ? $this->get_iom_area(trim($_GET['county_code'])) : '';
        $where = ' and 1 = 1 ';
        if ($code) {
            $where .= "and b.code ='".$code. "' ";
        }
        if($mac){
            $where .= "and b.mac = '".$mac."' ";
        }
        if ($ysten_id) {
            $where .= "and b.ysten_id='".$ysten_id."'";
        } 
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $conditon=' ';
        if($type){
            $condition .= "a.user_group_id='".strtoupper($type)."' and ";
        }
        if($county_code){
            $condition .= "d.county_code='".$county_code."' and ";
        }
        
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
    
        $city_code = I('city_code');
        $at_time = I('at_time');
    
        $at_time_start = $at_time." 00:00:00";
        $at_time_end = $at_time." 23:59:59";
    
        if($at_time && $city_code){
            $device_map_sql_count = "SELECT a.*,b.code,b.ysten_id,b.mac,c.device_group_id FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id left join bims_device b on a.mac = b.mac left join bims_device_group_map c on b.ysten_id=c.ysten_id WHERE a.city_code='".$city_code."' AND a.at_time>'".$at_time_start."' AND a.at_time<'".$at_time_end."' AND $condition  a.mac is not NULL ".$where." ORDER BY d.county_code,a.at_time DESC";
            $count = count($Model->db(1,$db_config)->query($device_map_sql_count));        
            
            $device_map_sql = "SELECT a.*,b.id,b.code,b.ysten_id,b.mac,b.create_date,c.device_group_id,d.county_code FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id left join bims_device b on a.mac = b.mac left join bims_device_group_map c on b.ysten_id=c.ysten_id WHERE a.city_code='".$city_code."' AND a.at_time>'".$at_time_start."' AND a.at_time<'".$at_time_end."' AND $condition  a.mac is not NULL ".$where." ORDER BY d.county_code,a.at_time DESC limit ".$offset.",".$pagesize;
            $device_map_info = $Model->db(1,$db_config)->query($device_map_sql);    
        }
        else{
            $device_map_sql_count = "SELECT a.*,b.code,b.ysten_id,b.mac,c.device_group_id FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id left join bims_device b on a.mac = b.mac left join bims_device_group_map c on b.ysten_id=c.ysten_id WHERE $condition  a.mac is not NULL ".$where." ORDER BY d.county_code,a.at_time DESC";                       
            $count = count($Model->db(1,$db_config)->query($device_map_sql_count));
            
            $device_map_sql = "SELECT a.*,b.code,b.ysten_id,b.mac,c.device_group_id,d.county_code FROM bims_customer_device_map a left join bims_customer d on a.user_name=d.user_id left join bims_device b on a.mac = b.mac left join bims_device_group_map c on b.ysten_id=c.ysten_id WHERE  $condition a.mac is not NULL ".$where." ORDER BY d.county_code,a.at_time DESC limit ".$offset.",".$pagesize;            
            $device_map_info = $Model->db(1,$db_config)->query($device_map_sql);            
        }
        
    
        if(empty($device_map_info)){
            $device_map_infos[]['user_name'] = I('get.user_name',0);
        }
        else{
            foreach ($device_map_info as $k=>$v){
                $device_map_infos[$k] = $v;
                $device_map_infos[$k]['county_code'] = $this->get_iom_area($v['county_code']);
            }
        }
    
        //dump($device_map_sql);exit;
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
    
        $this->assign('count',$count);
        $this->assign('page', $page);
        $this->assign('device_map_info',$device_map_infos);
        $this->assign('ref_url',$ref_url);
        $this->display();
    }    
    
    public  function statistics(){
        $p = intval($p) > 0 ? $p : 1;
        $pagesize = 25;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $this->assign('pagesize',$pagesize);
        $prefix = 'bims_';
         
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
        
        //$at_time_start = !empty($_GET['at_time_start']) ? $_GET['at_time_start'] : '2017-05-01 00:00:00';
        //$at_time_end = !empty($_GET['at_time_end']) ? $_GET['at_time_end'].' '.date('H:i:s') : date('Y-m-d H:i:s');
        $at_time_start = !empty($_GET['at_time_start']) ? $_GET['at_time_start'] : '2017-05-01 00:00';
        $at_time_end = !empty($_GET['at_time_end']) ? $_GET['at_time_end']: date('Y-m-d H:i:s');
        $show_date = date('Y年m月d H:i:s', strtotime($at_time_end));
        $city_code = isset($_GET['city_code']) ? $_GET['city_code'] : '';
        $condition = ' 1=1 ';
        if ($at_time_start && $at_time_end) {           
            $condition .= " and a.at_time >= '".$at_time_start."' and a.at_time <= '".$at_time_end."' ";
        }
        //$type = isset($_GET['type']) ? $_GET['type'] : ''; //'DJIPTV';
        if(empty($_GET['type']) || strtolower($_GET['type'])=='djiptv'){
            $type = 'DJIPTV';
        }
        if(strtolower($_GET['type'])=='yliptv'){
            $type= 'YLIPTV';
        }
        if(strtolower($_GET['type'])=='all'){
            $type = '';
        }       
        if($type){
            $condition .= " and a.user_group_id='".$type."' ";
        }
        if($city_code){
            $condition .= "  and a.city_code='".$city_code."' ";
        }
        
        $area_list = $this->create_select($this->get_area(), $city_code);        
        
        //省市记录
        $device_sql = "SELECT count(*) as number,a.* FROM bims_customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE ".$condition." GROUP BY a.city_code ORDER BY a.at_time DESC ";
        $device_map_info = $Model->db(1,$db_config)->query($device_sql);
        $device_sql_active = "SELECT count(*) as active_number,a.city_code FROM bims_customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE ".$condition." and a.mac is not NULL GROUP BY a.city_code ORDER BY a.at_time DESC ";
        $device_info_active = $Model->db(1,$db_config)->query($device_sql_active); 
        
        //dump($device_map_info);exit;
                
        if(!empty($device_map_info)){            
            $device_map_infos =  array_merge($device_map_info,array(array('city_code'=>'0378','product_id'=>$type))); //开封
            $device_map_info = $this->get_area_sort($device_map_infos); //地区排列
        }
        
        foreach ($device_map_info as $k=>$v){
            $device_info[$k] = $v;

            $device_info[$k]['area'] = $this->get_area($v['city_code']);
            foreach ($device_info_active as $ak=>$av){
                if($av['city_code']==$v['city_code']){
                    $device_info[$k]['active_number'] = $av['active_number'];
                }
            }
        }
        
        //dump($device_info);exit;
        
        //地区记录
        $device_sql = "SELECT count(*) as number,a.*,b.county_code FROM bims_customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE ".$condition." GROUP BY a.city_code,b.county_code ORDER BY a.city_code DESC ";
        $device_map_countys = $Model->db(1,$db_config)->query($device_sql);  
        $device_sql_active = "SELECT count(*) as active_number,a.city_code,b.county_code FROM bims_customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE ".$condition." and mac is not NULL GROUP BY a.city_code,b.county_code ORDER BY a.city_code DESC ";
        $device_county_actives = $Model->db(1,$db_config)->query($device_sql_active);
        
        
        //开封开户激活数
        $kf_number = 0;
        $kf_actvie_number = 0;
        $kf_arr = array('11568','13051','13052','13053','13054','13055');
        foreach ($device_map_countys as $k=>$v){ 
            $device_map_county[$k] = $v;
            if(in_array($v['county_code'],$kf_arr)){                
                $device_map_county[$k]['city_code'] = '0378';
                $kf_number+= $v['number'];              
            }
        }
        foreach ($device_county_actives as $k=>$v){
            $device_county_active[$k] = $v;
            if(in_array($v['county_code'],$kf_arr)){
                $device_county_active[$k]['city_code'] = '0378';
                $kf_actvie_number+= $v['active_number'];
            }
        }        
        
               
        $device_info_countys = array();
        foreach ($device_map_county as $k=>$v){
            $device_info_countys[$k] = $v;
            $device_info_countys[$k]['county_code'] = $v['county_code'];
            $device_info_countys[$k]['county_name'] = $this->get_iom_area($v['county_code']);
            $device_info_countys[$k]['area'] = $this->get_area($v['city_code']);            
            foreach($device_county_active as $ak=>$av){
                
                if($av['city_code']==$v['city_code'] && $av['county_code'] == $v['county_code']){
                    $device_info_countys[$k]['active_number'] = $av['active_number'];
                }
            }
        } 
        
        //市区地区记录
        $area_county = array();
        foreach ($device_info as $k=>$v){           
            $area_county[$k] = $v;
            foreach($device_info_countys as $ak=>$av){
                if($av['city_code']==$v['city_code']){
                    if($v['city_code']=='0378'){
                        $area_county[$k]['number'] = $kf_number;
                        $area_county[$k]['active_number'] = $kf_actvie_number;
                    }
                    if($v['city_code']=='0371'){
                        $area_county[$k]['number'] = $v['number']-$kf_number;
                        $area_county[$k]['active_number'] = $v['active_number']-$kf_actvie_number;                        
                    }
                    
                    $area_county[$k]['county_list'][] = $av;
                }
            }
        }
        
        //统计开户总数
        $account_info = $Model->db(1,$db_config)->query("SELECT count(*) as count from {$prefix}customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE ".$condition);
        $accounts = $account_info[0]['count'];
        //统计开户总激活数
        $account_info_active = $Model->db(1,$db_config)->query("SELECT count(*) as count_active from {$prefix}customer_device_map a left join bims_customer b on a.user_name=b.user_id WHERE a.mac is not NULL and ".$condition);
        $accounts_active = $account_info_active[0]['count_active'];        

        $this->assign('show_date', $show_date);
        $this->assign('area_county',$area_county);
        $this->assign('accounts',$accounts);
        $this->assign('accounts_active',$accounts_active);
        $this->assign('user_type',$this->user_type($type));
        $this->assign('area_list',$area_list);
        $this->display();        
    }
          
    private function get_area($city_code = ''){
        $area_code['0371'] = '郑州';
        $area_code['0392'] = '鹤壁';
        $area_code['0394'] = '周口';
        $area_code['0374'] = '许昌';
        $area_code['0379'] = '洛阳';
        $area_code['0376'] = '信阳';
        $area_code['0378'] = '开封';
        $area_code['0398'] = '三门峡';
        $area_code['0372'] = '安阳';
        $area_code['0393'] = '濮阳';
        $area_code['0370'] = '商丘';
        $area_code['0377'] = '南阳';
        $area_code['0375'] = '平顶山';
        $area_code['039A'] = '济源';
        $area_code['0391'] = '焦作';
        $area_code['0395'] = '漯河';
        $area_code['0396'] = '驻马店';
        $area_code['0373'] = '新乡';  
        
        if(!empty($city_code)){
            if(empty($area_code[$city_code])){
                $area_code = '未知';
            }
            else{
                $area_code = $area_code[$city_code];
            }
            
        }

        return $area_code;
    }
    
    private function create_select($arr,$select){
        $area_list="<label class='inline'></label><select name='city_code' class='form-control'>";
        $area_list.="<option value=''>--地区--</option>";        
        if($arr){
            foreach ($arr as $k=>$val){
                $selected=(isset($select) && $select==$k)?"selected='selected'":'';//判断是否为选中状态
                $area_list.="<option value='{$k}' {$selected}>{$val}</option>";                
            }                       
        }
        $area_list.="</select>&nbsp;&nbsp;";
        
        return $area_list;
    }
    
    private function user_type($type=''){
            $show_message = array();
            switch (strtoupper(trim($type))){
                case 'DJIPTV':
                    $show_message[0] = $type;
                    $show_message[1] = '&nbsp;&frasl;&nbsp;党建';                   
                    break;
                case 'YLIPTV':
                    $show_message[0] = $type;
                    $show_message[1] = '&nbsp;&frasl;&nbsp;医疗';
                    break;
                default:
                    $show_message[0] = $type;
                    $show_message[1] = '&nbsp;&frasl;&nbsp;全部';
                    break;
            }
            
         return $show_message;
    }
    
    public function expload($expTableData = ''){
        vendor("PHPExcel.PHPExcel");
        vendor("PHPExcel.Writer.Excel2007");       
        vendor("PHPExcel.Writer.Excel5");      
        $objPhpExcel = new \PHPExcel();
        $objPhpExcel->getActiveSheet()->getDefaultColumnDimension()->setAutoSize(true);//设置单元格宽度
               
        //设置表格的宽度     
        $objPhpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);        
        $objPhpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(10); 
        $objPhpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $objPhpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $objPhpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);        
        $objPhpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);        
        $objPhpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $objPhpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(15);
        $objPhpExcel->getActiveSheet()->getColumnDimension('I')->setWidth(17);
        $objPhpExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        
        //设置标题        
        $rowVal = array(0=>'开户名', 1=>'地区',2=>'产品ID',3=>'关联宽带', 4=>'区县编码',5=>'分局ID',6=>'产品描述',7=>'类型',8=>'Mac地址', 9=>'开户时间');
                           
        foreach ($rowVal as $k=>$r){       
            $objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)        
            ->getFont()->setBold(true);//字体加粗        
            //$objPhpExcel->getActiveSheet()->getStyleByColumnAndRow($k,1)->
            //getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);//文字居中            
            $objPhpExcel->getActiveSheet()->setCellValueByColumnAndRow($k,1,$r);      
        }
        
        //设置当前的sheet索引 用于后续内容操作     
        $objPhpExcel->setActiveSheetIndex(0);      
        $objActSheet=$objPhpExcel->getActiveSheet();
        
        //设置当前活动的sheet的名称        
        $title="IPTV_UBSS";      
        $objActSheet->setTitle($title);
        
        //设置单元格内容
        $Model = new \Think\Model();
        $db_config = C("DB125_CONFIG");
        
        $where = ' and 1 = 1 ';
        
        //记录
        //$device_sql = "SELECT a.*,b.broadband_id,b.county_code,b.olt_office,b.create_date FROM bims_customer_device_map as a left join bims_customer as b ON b.user_id=a.user_name WHERE user_group_id in('DJIPTV','YLIPTV') ".$where." order by b.create_date DESC";
        //$expTableData = $Model->db(1,$db_config)->query($device_sql);

        //dump($expTableData);exit;
        $objPhpExcel->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);     
        $objPhpExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);        
        $objPhpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);        
        
        if($expTableData){
            foreach($expTableData as $k => $v)       
            {  
                $num=$k+2;                        
                $objPhpExcel->setActiveSheetIndex(0)
                ->setCellValue('A'.$num, $v['user_name'])
                ->setCellValue('B'.$num, ' '.$v['city_code'])
                ->setCellValue('C'.$num, $v['product_id']) 
                ->setCellValue('D'.$num, ' '.$v['broadband_id'])
                ->setCellValue('E'.$num, $v['county_code'])
                ->setCellValue('F'.$num, $v['olt_office'])            
                ->setCellValue('G'.$num, $v['product_desc'])        
                ->setCellValue('H'.$num, $v['user_group_id'])        
                ->setCellValue('I'.$num, $v['mac'])        
                ->setCellValue('J'.$num, $v['create_date']);        
            }
        }
        else{
            $this->error('数据源为空，请检查数据源...');
            exit;
        }
        
        $name=date('Y-m-d');//设置文件名                
        header("Content-Type: application/force-download");        
        header("Content-Type: application/octet-stream");        
        header("Content-Type: application/download");        
        header("Content-Transfer-Encoding:utf-8");        
        header("Pragma: no-cache");        
        header('Content-Type: application/vnd.ms-excel');        
        header('Content-Disposition: attachment;filename="'.urlencode($name).'.xls"');       
        header('Cache-Control: max-age=0');     
        $objWriter = \PHPExcel_IOFactory::createWriter($objPhpExcel, 'Excel5');        
        $objWriter->save('php://output');                                    
        exit;        
    }
    
    public function import(){
        if (!empty ( $_FILES)){
            $upload = new \Think\Upload();
            $upload->maxSize   =     1048576000 ;
            $upload->exts      =     array('xls','xlsx');
            $upload->rootPath  = './Public/attached/ubss/';
            $upload->autoSub   = false;
    
            // 上传文件
            $info   =   $upload->upload();
            //dump($info);exit;
            $exts   = $info['upload_excel']['ext'];
            $filename = $upload->rootPath.$info['upload_excel']['savename'];
            //dump($filename);exit;
            if(!$info) {
                $this->error($upload->getError());
            }else{
                vendor("PHPExcel.PHPExcel");
                $PHPExcel = new \PHPExcel();
                if ($exts == 'xls') {
                    vendor("PHPExcel.PHPExcel.Reader.Excel5");
                    $PHPReader = new \PHPExcel_Reader_Excel5();
                } else
                    if ($exts == 'xlsx') {
                        vendor("PHPExcel.PHPExcel.Reader.Excel2007");
                        $PHPReader = new \PHPExcel_Reader_Excel2007();
                    }
                 
                $PHPExcel = $PHPReader->load($filename);                     // 载入文件
                //dump($PHPExcel);exit;
                $currentSheet = $PHPExcel->getSheet(0);                      // 获取表中的第一个工作表，如果要获取第二个，把0改为1，依次类推
                $allColumn = $currentSheet->getHighestColumn();              // 获取总列数
                $allRow = $currentSheet->getHighestRow();                    // 获取总行数
                for ($currentRow = 2; $currentRow <= $allRow; $currentRow ++) {// 循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
                    for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn ++) {// 从哪列开始，A表示第一列
                        $address = $currentColumn . $currentRow;             // 数据坐标
                        $ExlData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();// 读取到的数据，保存到数组$arr中
                    }
                }
    
                //dump($ExlData);exit;
                $data = $this->importExcel_Ubss($ExlData);
                
            }
        }else {
            $this->display();
        }
    }    
    
    public function importExcel_Ubss($list){
        if(!empty($list) && is_array($list)){
            //dump($list);exit;
            for($i=2;$i<=sizeof($list)+1;$i++){
                if(empty($list[$i]['A'])){
                    $this->error("第:".$i++."行  UserID 为空!");
                }
                if(empty($list[$i]['B'])){
                    $this->error("第:".$i++."行  Login_name 为空!");
                }
                if(empty($list[$i]['C'])){
                    $this->error("第:".$i++."行  Password 为空!");
                }
                if(empty($list[$i]['D'])){
                    $this->error("第:".$i++."行  Citycode 为空!");
                }
                if(empty($list[$i]['E'])){
                    $this->error("第:".$i++."行  Countycode 为空!");
                }
                if(empty($list[$i]['F'])){
                    $this->error("第:".$i++."行  broadbandID 为空!");
                }
                if(empty($list[$i]['G'])){
                    $this->error("第:".$i++."行  OLTOffice 为空!");
                }
                if(empty($list[$i]['H'])){
                    $this->error("第:".$i++."行  ProductID 为空!");
                }
                if(empty($list[$i]['I'])){
                    $this->error("第:".$i++."行  ProductDesc 为空!");
                }
                if(empty($list[$i]['J'])){
                    $this->error("第:".$i++."行  UsergroupID 为空!");
                }                
            }                       
                       
            
            foreach ($list as $val){            
                $post_subscription =
                '<?xml version="1.0" encoding="utf-8"?>
                    <SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
                      <SOAP-ENV:Body>
                        <m:Subscription xmlns:m="http://tempuri.org/BOSS.xsd">
                        <reqSubscription>
                        <UserID>'.trim($val['A']).'</UserID>
                        <Login_name>'.trim($val['B']).'</Login_name>
                        <Password>'.trim($val['C']).'</Password>
                        <Citycode>'.trim($val['D']).'</Citycode>
                        <Countycode>'.trim($val['E']).'</Countycode>
                        <broadbandID>'.trim($val['F']).'</broadbandID>
                        <OLTOffice>'.trim($val['G']).'</OLTOffice>
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
                        <ProductID>'.trim($val['H']).'</ProductID>
                        <ProductDesc>'.trim($val['I']).'</ProductDesc>
                        </ProductListItem>
                        </Productlist>
                        <UsergroupID>'.trim($val['J']).'</UsergroupID>
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
            //var_dump($output);exit;
            $this->success('开户数据导入成功！');
        }
        else{
            $this->error('数据格式不正确！');
        }
    }
    
    
    public function reportmac(){        
            $post_data['username'] = trim(I("username"));
            $post_data['mac'] = str_replace('-', ':',trim(I("mac")));            
            //dump($post_data);
            
            if($post_data['username'] && $this->mac_validate($post_data['mac'])){
                //echo json_encode(array('result'=>2));exit;
                $url = 'http://cms.cuhn.sllhtv.com:5188/index.php/home/IptvService/reportmac';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
                $output = curl_exec($ch);
                curl_close($ch);
                echo json_encode(array('result'=>0));
            }
            else{
                echo json_encode(array('result'=>-1));
            }
    }
    
    private function mac_validate($mac = ''){
        if(!empty($mac)){
            //校验mac格式
            $pattern_mac="/[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f][:]" . "[0-9a-f][0-9a-f]/i";
            $result_mac = preg_match($pattern_mac, $mac);
        
            //校验mac 不全为0
            $mac_str = str_replace(':', '', $mac);
            $mac_len = strlen($mac_str);
            if($mac_str =='000000000000' || $mac_len!=12 || $mac_str =='111111111111'){
                $mac_status = FALSE;
            }
            else{
                return TRUE;
            }
        }
        else{
            return false;
        }
    }    

    private function get_area_sort($map_info, $sort_arr=''){
        if(empty($sort_arr)){
            $sort_arr = array('0377','0375','0396','0391','0373','0395','0370','039A',                
                      '0393','0372','0398','0376','0379','0374','0371','0378','0394','0392');                  
        }
        if($map_info && $sort_arr && is_array($map_info) && is_array($sort_arr)){
            for($i=0;$i<count($sort_arr);$i++){
                for($j=0;$j<count($map_info);$j++){
                    if($sort_arr[$i] == $map_info[$j]['city_code']){
                        $list_info[] = $map_info[$j];
                    } 
                }
            } 
            
            //ksort($list_info);
            return $list_info;
        }
        else{
            echo '参数错误!';
        }
    }    
    
    private function get_iom_area($iom_id = ''){
        $iom_code['11558']=	'商丘';
        $iom_code['14413']=	'永城';
        $iom_code['16833']=	'夏邑';
        $iom_code['16834']=	'虞城';
        $iom_code['16836']=	'宁陵';
        $iom_code['16838']=	'睢县';
        $iom_code['16840']=	'民权';
        $iom_code['16841']=	'柘城';
        $iom_code['55919']=	'睢阳区';
        $iom_code['10779']=	'郑州市';
        $iom_code['10782']=	'登封';
        $iom_code['10876']=	'巩义';
        $iom_code['10877']=	'上街';
        $iom_code['10878']=	'新密';
        $iom_code['10879']=	'荥阳';
        $iom_code['11567']=	'新郑';
        $iom_code['13252']=	'中牟';
        $iom_code['1155851']= '航空港区';
        $iom_code['11563']=	'安阳';
        $iom_code['11652']=	'安阳县';
        $iom_code['11653']=	'林州市';
        $iom_code['11654']=	'汤阴县';
        $iom_code['11655']=	'滑县';
        $iom_code['11656']=	'内黄县';
        $iom_code['11561']=	'新乡市';
        $iom_code['12122']=	'长垣县';
        $iom_code['12123']=	'新乡县';
        $iom_code['12124']=	'封丘县';
        $iom_code['12125']=	'原阳县';
        $iom_code['12126']=	'卫辉市';
        $iom_code['12127']=	'获嘉县';
        $iom_code['12128']=	'延津县';
        $iom_code['12129']=	'辉县市';
        $iom_code['20843']=	'许昌市';
        $iom_code['20844']=	'长葛市';
        $iom_code['20845']=	'禹州市';
        $iom_code['20846']=	'襄城县';
        $iom_code['20847']=	'鄢陵县';
        $iom_code['20848']=	'许昌县';
        $iom_code['11564']=	'平顶山';
        $iom_code['14254']=	'郏县';
        $iom_code['14255']=	'宝丰';
        $iom_code['14256']=	'叶县';
        $iom_code['14257']=	'汝州';
        $iom_code['14259']=	'舞钢';
        $iom_code['14260']=	'鲁山';
        $iom_code['11554']=	'信阳市';
        $iom_code['14421']=	'平桥';
        $iom_code['14422']=	'罗山';
        $iom_code['14423']=	'光山';
        $iom_code['14424']=	'息县';
        $iom_code['14425']=	'新县';
        $iom_code['14426']=	'潢川';
        $iom_code['14427']=	'淮滨';
        $iom_code['14428']=	'商城';
        $iom_code['14429']=	'固始';
        $iom_code['11553']=	'南阳市';
        $iom_code['23145']=	'镇平';
        $iom_code['23146']=	'方城';
        $iom_code['23148']=	'唐河';
        $iom_code['23149']=	'南召';
        $iom_code['23150']=	'内乡';
        $iom_code['23151']=	'西峡';
        $iom_code['23152']=	'淅川';
        $iom_code['23153']=	'桐柏';
        $iom_code['23154']=	'邓州';
        $iom_code['23157']=	'社旗';
        $iom_code['23158']=	'新野';
        $iom_code['11568']=	'开封市';
        $iom_code['13051']=	'杞县';
        $iom_code['13052']=	'通许县';
        $iom_code['13053']=	'尉氏县';
        $iom_code['13054']=	'开封县';
        $iom_code['13055']=	'兰考县';
        $iom_code['16942']=	'偃师市';
        $iom_code['24106']=	'洛阳市';
        $iom_code['24107']=	'孟津县';
        $iom_code['24108']=	'新安县';
        $iom_code['24109']=	'伊川县';
        $iom_code['24110']=	'汝阳县';
        $iom_code['24112']=	'嵩县';
        $iom_code['24113']=	'栾川县';
        $iom_code['24114']=	'洛宁县';
        $iom_code['24115']=	'宜阳县';
        $iom_code['11559']=	'焦作市';
        $iom_code['11731']=	'孟州市';
        $iom_code['11732']=	'沁阳市';
        $iom_code['11733']=	'温县';
        $iom_code['11734']=	'博爱县';
        $iom_code['11735']=	'武陟县';
        $iom_code['11742']=	'修武县';
        $iom_code['600']= '鹤壁市';
        $iom_code['601']= '浚县';
        $iom_code['602']= '淇县';
        $iom_code['11562']=	'濮阳市';
        $iom_code['11815']=	'范县';
        $iom_code['11816']=	'南乐县';
        $iom_code['11817']=	'清丰县';
        $iom_code['11819']=	'台前县';
        $iom_code['11821']=	'濮阳县';
        $iom_code['11570']=	'周口';
        $iom_code['20254']=	'项城市';
        $iom_code['20255']=	'沈丘县';
        $iom_code['20256']=	'郸城县';
        $iom_code['20257']=	'鹿邑县';
        $iom_code['20258']=	'淮阳县';
        $iom_code['20259']=	'太康县';
        $iom_code['20260']=	'扶沟县';
        $iom_code['20261']=	'西华县';
        $iom_code['20262']=	'商水县';
        $iom_code['11556']=	'漯河市';
        $iom_code['13141']=	'舞阳县';
        $iom_code['13142']=	'临颍县';
        $iom_code['1155850']= '郊区';
        $iom_code['11555']=	'驻马店';
        $iom_code['14287']=	'西平县';
        $iom_code['14288']=	'上蔡县';
        $iom_code['14289']=	'汝南县';
        $iom_code['14290']=	'平舆县';
        $iom_code['14291']=	'新蔡县';
        $iom_code['14292']=	'正阳县';
        $iom_code['14293']=	'确山县';
        $iom_code['14294']=	'泌阳县';
        $iom_code['14295']=	'遂平县';
        $iom_code['11565']=	'三门峡';
        $iom_code['11649']=	'灵宝市';
        $iom_code['11790']=	'陕县';
        $iom_code['11791']=	'渑池县';
        $iom_code['11792']=	'义马市';
        $iom_code['11793']=	'卢氏县';
        $iom_code['11560']=	'济源'; 
        
        if(!empty($iom_id)){
            $iom_value = $iom_code[$iom_id]?$iom_code[$iom_id]:'';  
            
            if(empty($iom_value)){
                foreach ($iom_code as $k=>$v){
                    if($v==$iom_id){
                        return $k;
                    }
                }
            }
        }
        else{
            $iom_value = '';
        }
        
        return $iom_value;        
    }
    
    //ubss 开户模板下载
    public function download_template(){
        header("Content-type:text/html;charset=utf-8");
        $save_name = 'ubss_user.xlsx';
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/exceltemplate/ubss_user.xlsx';
        ob_end_clean();
        $hfile = fopen($file_path, "rb") or die("Can not find file: $file_path\n");
        Header("Content-type: application/octet-stream");
        Header("Content-Transfer-Encoding: binary");
        Header("Accept-Ranges: bytes");
        Header("Content-Length: ".filesize($file_path));
        Header("Content-Disposition: attachment; filename=\"$save_name\"");
        while (!feof($hfile)) {
            echo fread($hfile, 32768);
        }
        fclose($hfile);
        exit;
    }    
    
}