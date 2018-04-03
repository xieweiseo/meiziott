<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：节目控制器。
 *
 **/

namespace Qwadmin\Controller;

use Vendor\Tree;

class ProgramController extends ComController
{

    public function add()
    {

        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name')->order('pub_sort_num asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
        $category = $tree->get_tree(0, $str, 0);
        $this->assign('category', $category);//导航
        $this->display('form');
    }

    public function index($sid = 0, $p = 1)
    {
        $p = intval($p) > 0 ? $p : 1;
         
        $program = M('program');
        $pagesize = 100;#每页数量
        $this->assign('pagesize',$pagesize);
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $sid = isset($_GET['sid']) ? $_GET['sid'] : '';
        $keyword = trim(isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '');
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        //$where = '1 = 1 ';
        $where = $this->AREA['area_id']?'area_id='.$this->AREA['area_id'].' ':'1 = 1 ';
        //dump($where);exit;
        if ($sid) {
            $sids_array = category_get_sons($sid);
            $sids = implode(',',$sids_array);
            $where .= "and {$prefix}program.material_type_id in ($sids) ";
        }
        if ($keyword) {
            $where .= "and {$prefix}program.program_name like '%{$keyword}%' ";
        }
        //默认按照时间降序
        $orderby = "{$prefix}program.create_date desc";
        if ($order == "asc") {

            $orderby = "{$prefix}program.create_date asc";
        }
        //获取栏目分类
        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();     
        $tree = new Tree($category);
        $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
        $category = $tree->get_tree(0, $str, $sid);
        $this->assign('category', $category);//导航

         //dump($where);exit;
        //$count = $program->where($where)->count();
        $count = $program->where($where)->order($orderby)->join("{$prefix}material_type ON {$prefix}material_type.material_type_id = {$prefix}program.material_type_id")->count();
        $this->assign('count',$count);
        $lists = $program->field("{$prefix}program.*,{$prefix}material_type.material_type_name")->where($where)->order($orderby)->join("{$prefix}material_type ON {$prefix}material_type.material_type_id = {$prefix}program.material_type_id")->limit($offset . ',' . $pagesize)->select();
        if($lists){
            foreach ($lists as $k=>$v){
                  $list[$k] = $v;
                  $list[$k]['area_name'] = $this->get_area_name($v['area_id']);
              }
        }
        
        $this->assign('pages',ceil($count/$pagesize));
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }
    
    public function get_area_name($area_id){
        if($area_id){            
            $area_list['4'] = '信阳';		
            $area_list['5'] = '焦作';	
            $area_list['6'] = '南阳';	
            $area_list['7'] = '洛阳	';
            $area_list['11'] = '郑州';	
            $area_list['13'] = '禹州';	
            $area_list['22'] = '开封';	
            $area_list['24'] = '安阳';
            
           $area_name = $area_list[$area_id];
           
           return $area_name?$area_name:$area_id;
        }        
    }
    
    public function inject_list($sid = 0, $p = 1){
        
        $p = intval($p) > 0 ? $p : 1;
        
        $cmd_order_model = M('Cmd_order');
        $pagesize = 100;#每页数量
        $this->assign('pagesize',$pagesize);
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $firms = isset($_GET['firms']) ? $_GET['firms'] : '';
        $at_time_start = isset($_GET['at_time_start']) ? $_GET['at_time_start'] : '';
        $at_time_end = isset($_GET['at_time_end']) ? $_GET['at_time_end']." 23:59:59" : '';
        $cmd_result = isset($_GET['cmd_result']) ? $_GET['cmd_result'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        
        $keyword = trim(isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '');
        
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        $where = '1 = 1 ';
        if ($firms) {
            $where .= "and {$prefix}cmd_order.lspid ='".$firms."'";
        }
        //默认按照时间降序
        $orderby = "order_id desc";
        if ($order == "asc") {
        
            $orderby = "order_id asc";
        }
        //搜索时间段
        if($at_time_start && $at_time_end){
            $where .= "and {$prefix}cmd_order.at_time >= UNIX_TIMESTAMP('".$at_time_start."') and {$prefix}cmd_order.at_time <= UNIX_TIMESTAMP('".$at_time_end."')";           
        }
        //注入返回结果
        if($cmd_result=='0' || $cmd_result=='-1' ||$cmd_result=='1'){
            $where .= "and {$prefix}cmd_order.cmd_result ='".$cmd_result."'";
        }
        //工单状态
        if($status){
            $where .= "and {$prefix}cmd_order.status ='".$status."'";;
        }
        
        //print_r($where);exit;
                
        $count = $cmd_order_model->where($where)->count();
        if ($keyword) {
            $where .= "and {$prefix}program.program_name like '%{$keyword}%' ";
            $count = $cmd_order_model->where($where)
                    ->join("{$prefix}program ON {$prefix}program.program_id = {$prefix}cmd_order.program_ids")
                    ->count();
        }
        
        $list = $cmd_order_model->field("{$prefix}cmd_order.*,{$prefix}program.program_name,{$prefix}program.program_id")
                                    ->where($where)
                                    ->order($orderby)
                                    ->join("{$prefix}program ON {$prefix}program.program_id = {$prefix}cmd_order.program_ids")
                                    ->limit($offset . ',' . $pagesize)
                                    ->select();
              
        //dump($cmd_order_model->getLastSql());exit;
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        //dump($list);exit;
        $this->assign('count',$count);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {

        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        
        //dump($aids);exit;
        if ($aids) {
            if (is_array($aids)) {
                $aids = implode(',', $aids);
                //$map['program_id'] = array('in', $aids);
                $map = 'program_id in ('.$aids.')';
            } else {
                $map = 'program_id=' . $aids;
            }
            //dump($map);exit;
            try{
                $program_series_rel = M('program_series_rel');
                $psr_count = $program_series_rel->where("program_id in ($aids)")->count();    
                if($psr_count){
                    $this->error('节目已添加在节目集中，无法删除!',U('index'));
                }
                
                M('program')->where($map)->delete();
                M('program_bitrate')->where($map)->delete();
                addlog('删除节目，DP：' . $aids);
                $this->success('节目删除成功！',U('index'));
            }
            catch (\Exception $e){
                  echo $e->getMessage();
            }
            
        } else {
            $this->error('参数错误！');
        }
    }
    
    public function del_order()
    {
    
        $oids = isset($_REQUEST['oids']) ? $_REQUEST['oids'] : false;
        if ($oids) {
            if (is_array($oids)) {
                $oids = implode(',', $oids);
                $map = 'order_id in ('.$oids.')';
            } else {
                $map = 'order_id=' . $oids;
            }
            if (M('cmd_order')->where($map)->delete()) {
                addlog('删除节目注入订单，PO：' . $oids);
                $this->success('恭喜，订单删除成功！');
            } else {
                $this->error('参数错误！');
            }
        } else {
            $this->error('参数错误！');
        }
    }    
    
    public function inject()
    {
        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        $firms = I('firms','','trim'); //格式：lspid-cspid;      
        $cancell_order = I('order','','trim');  //取消节目注入工单
        
        if ($aids && $firms) { 
            
           if(empty($cancell_order)){ 
               //商家标识, ///注入
               $lscs_pid = explode('-', $firms);
               $lspid = $lscs_pid[0];
               $cspid = $lscs_pid[1];  
           }
           
           foreach($aids as $val){ 
               if(stripos($val,"#")!==false){
                  //撤单
                  $aids_list = explode('#', $val);
                  $val = $aids_list[0];  //1146257#55555-dangjian
                  $firms = $aids_list[1];
                   
                  $firms_info = explode('-', $firms);
                  $lspid = $firms_info[0];
               }                             
    
               //读取注入状态
               $program = D('Program');
               $program_info = $program->field('CMS_RESULT_HW_INJECT,CMS_RESULT_HW_STATUS,CMS_RESULT_ZTE_INJECT,CMS_RESULT_ZTE_STATUS')->where('program_id='.$val)->find();                       
               switch ($lspid){
                   case '55555':
                       if($program_info['cms_result_hw_inject']=='-2' && $program_info['CMS_RESULT_HW_STATUS']==''){
                           $status = 'create';
                       }
                       if($program_info['cms_result_hw_inject']=='0'){
                           $status = 'update';
                       }
                       if($cancell_order=='ok'){
                           $status = 'delete';
                       }
                       break;
                   case 'LTDJIPTV':
                       if($program_info['cms_result_zte_inject']=='-2' && $program_info['CMS_RESULT_ZTE_STATUS']==''){
                           $status = 'create';
                       }
                       if($program_info['cms_result_zte_inject']=='0'){
                           $status = 'update';
                       } 
                       if($cancell_order=='ok'){
                           $status = 'delete';
                       }                       
                       break;
               }
               
               //dump($val.' '.$status.' '.$firms);
              
               //节目注入
               $cmd_result = $program->program_sync($val, $status, $firms);

           }
           if(is_array($cmd_result)){
               if($cmd_result['result']){
                   $this->success('节目注入['.$cmd_result['status'].']成功');
               }
               else{
                   $this->success('节目注入['.$cmd_result['status'].']失败');
               }              
           }
           else{
               $this->error('xml url address returned incorrectly!');
           }
           
        } 
        else {
            $this->error('参数错误！');
        }
    }    
    
    public function edit($aid)
    {
        $aid = intval($aid);
        $program = M('program')->where('program_id=' . $aid)->find();   //17859561        
        
        if($this->AREA['area_id']){
            if($program['area_id']!==$this->AREA['area_id']){
                $this->error('没有权限访问本页面!');
            }            
        }
        
        
        $program_bitrate = M('program_bitrate')->field('program_rate_id,program_id,definition_code,src_file_name,src_file_path,src_file_path,play_url,down_url,cp_code')->where("program_id=$aid")->select();
        //dump($program_bitrate);
        foreach ($program_bitrate as $v1){
            foreach ($v1 as $k=>$v){
                if(strtolower($v) == 'sd' || strtolower($v) == 'hd' || strtolower($v) == 'uhd'){     
                   $tv = $v1;
                }
                if(strtolower($v) == 'md'){
                   $md = $v1;         
                }
            }
        } 
        $program_bitrate['tv'] = $tv;
        $program_bitrate['md'] = $md;
        //dump($program_bitrate);exit;
        //dump(array_filter($program_bitrate));exit;
        if ($program) {
            $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();
            $tree = new Tree($category);
            $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式            
            $category = $tree->get_tree(0, $str, $program['material_type_id']);
            $this->assign('category', $category);//分类
            $this->assign('program', $program);
            $this->assign('program_bitrate',array_filter($program_bitrate));
        } else {
            $this->error('参数错误！');
        }
        
        $this->assign('act',I('act'));    
        $this->display('form');
    }

    public function update($aid = 0, $tvid = 0, $mdid= 0)
    {

        $aid = intval($aid); //program_id
        $tvid = intval($tvid); //program_rate_id
        $mdid = intval($mdid);  
        //dump($aid);exit;
        
        $data_program['MATERIAL_TYPE_ID'] = isset($_POST['sid']) ? intval($_POST['sid']) : 0;  //节目类别
        $data_program['PROGRAM_NAME'] = isset($_POST['title']) ? $_POST['title'] : false;
        $data_program['MATERIAL_CLASS'] = isset($_POST['material_class']) ? $_POST['material_class'] : ''; //节目类型
        $data_program['ZONE'] = I('post.zone', '', array('strip_tags','trim'));  //地区
        $data_program['DIRECTOR'] = I('post.director', '', array('strip_tags','trim'));
        $data_program['PREMIERE_DATE'] = isset($_POST['premiere_date']) ? $_POST['premiere_date'] : 0;  //上映时间
        $data_program['PROGRAM_LENGTH'] = I('post.program_length', 0 ,array('intval','trim')); //节目时长
        
        $data_program['ENGLISH_NAME'] =  I('post.english_name', '', array('strip_tags','trim'));
        $data_program['DEFINITION_CODE'] =  I('post.definition_code', '', array('strip_tags','trim')); //SD,HD
        $data_program['SET_NUMBER'] = !empty($_POST['set_number'])?I('post.set_number', '' ,'trim'):1;
        $data_program['LEADING_ROLE'] = I('post.leading_role', '', array('strip_tags','trim'));         
        $data_program['LANGUAGE_ID'] = 1;
        $data_program['PROGRAM_STATUS_ID'] = 'ONLINE'; //OFFLINE
        $data_program['ADDR_TYPE'] = 'http';        
        $data_program['OUT_SOURCE_ID'] = strtolower(str_random(11)); //11位随机数        
        $data_program['CP_CODE'] = $data_program['DATA_PROVIDER'] = 'CMS';    //CMS
        $data_program['TAG'] = I('post.tag', '', array('strip_tags','trim')); //标签
        $data_program['IF_PREVIEW'] = 0 ;    
        $data_program['YEARS'] = isset($_POST['years']) ? $_POST['years'] : 0; 
        $data_program['PROGRAM_DESC'] = I('program_desc','','strip_tags');         
        $data_program['POSTER'] = I('post','',array('strip_tags','trim')); //strtolower(I('post.thumbnail')); //海报
        $data_program['QR_CODE'] = I('qr_code','','trim'); 
        
        //dump($data_program);exit;
        //检验必填字段
        $tv_play_url = I('post.play_url', '', array('strip_tags','trim'));
        $tv_down_url = I('post.down_url', '', array('strip_tags','trim'));
        $md_play_url = I('post.md_play_url', '', array('strip_tags','trim'));
        $md_down_url = I('post.md_down_url', '', array('strip_tags','trim'));
        if (!$data_program['MATERIAL_TYPE_ID'] or !$data_program['PROGRAM_NAME'] or !$data_program['PROGRAM_DESC']) {
            $this->error('警告！节目分类、节目标题及节目简介为必填项目。');
        }
        if(!$tv_play_url or !$tv_down_url){
            $this->error('TV端节目播放地址、节目下载地址 ，必填！');
        }
        if(!$md_down_url or !$md_play_url){
            $this->error('手机端节目播放地址、节目下载地址 ，必填！');
        }        
        
        if ($aid) {
            $data_program['LAST_MODIFY_DATE'] = date('Y-m-d H:i:s');
            //dump($data_program);exit;
            $program_id = M('program')->data($data_program)->where('program_id=' . $aid)->save();
            //上传节目progrma_bitrate
            //$file_uplod = file_upload($_FILES['src_file_name'],$data_program['DEFINITION_CODE']);
            $program_bitrate['BITRATE_ID'] = 1 ;
            $program_bitrate['PROGRAM_ID'] = $aid;
            $program_bitrate['SRC_FILE_NAME']  = I('post.src_file_name', '', array('strip_tags','trim'));  //文件名
            $program_bitrate['MD5'] = md5($program_bitrate['SRC_FILE_NAME']);
            $program_bitrate['FILE_SIZE'] = I('post.file_size', '', array('intval','trim'));
            $program_bitrate['FILE_TYPE'] = I('post.file_type', '', array('strip_tags','trim'));  
            $program_bitrate['SRC_FILE_PATH'] = I('post.src_file_path', '', array('strip_tags','trim'));  
            $program_bitrate['DOWN_URL'] = I('post.down_url', '', array('strip_tags','trim'));
            $program_bitrate['play_url'] = I('post.play_url', '', array('strip_tags','trim'));
            
            $program_bitrate['BITRATE_STATUS'] = 3;
            $program_bitrate['CP_CODE'] = $program_bitrate['DATA_PROVIDER'] = $data_program['CP_CODE'];
            $program_bitrate['DEFINITION_CODE'] = $data_program['DEFINITION_CODE'];
            $program_bitrate['IS_CP_DELETE'] = 0;
            $program_bitrate['LAST_MODIFY_DATE'] = $data_program['LAST_MODIFY_DATE'];
            //dump($tvid);
            //dump($program_bitrate);exit;
            if(!empty($tvid)){
                $program_bitrate_id = M('program_bitrate')->data($program_bitrate)->where('program_rate_id=' . $tvid)->save(); 
            }
            
            //手机端码率
            $program_bitrate['SRC_FILE_NAME'] = I('post.md_src_file_name', '', array('strip_tags','trim'));
            $program_bitrate['SRC_FILE_PATH'] = I('post.md_src_file_path', '', array('strip_tags','trim'));
            $program_bitrate['DOWN_URL'] = I('post.md_down_url', '', array('strip_tags','trim'));
            $program_bitrate['play_url'] = isset($_POST['md_play_url'])? I('post.md_play_url', '', array('strip_tags','trim')) : $program_bitrate['SRC_FILE_PATH'];
            $program_bitrate['DEFINITION_CODE'] = 'MD';
            if(!empty($mdid)){
               $program_bitrate_id = M('program_bitrate')->data($program_bitrate)->where('program_rate_id=' . $mdid)->save();  
            }
            else{
                //手机端如果没有节目就新增
                $program_bitrate_id = M('program_bitrate')->data($program_bitrate)->add();
            }
            
                        
            addlog('编辑节目，UP：' . $aid);
            $this->success('恭喜！节目编辑成功！',U('index'));
        } else {                       
            //检验必填字段
            if(!$tv_play_url or !$tv_down_url){
                $this->error('TV端节目播放地址、节目下载地址 ，必填！');
            }
            if(!$md_down_url or !$md_play_url){
                $this->error('手机端节目播放地址、节目下载地址 ，必填！');
            }   
            
            $data_program['CREATE_DATE'] = date('Y-m-d H:i:s');
            $data_program['AREA_ID'] = $this->AREA['area_id'];
            $data_program['PROGRAM_STATUS_ID'] = 'OFFLINE';
            $aid = M('program')->data($data_program)->add();
            if ($aid) {
                //上传节目program_bitrate
                //$file_uplod = file_upload($_FILES['src_file_name'],$data_program['DEFINITION_CODE']);
                $program_bitrate['BITRATE_ID'] = 1 ;
                $program_bitrate['PROGRAM_ID'] = $aid;
                $program_bitrate['SRC_FILE_NAME']  = I('post.src_file_name', '', array('strip_tags','trim'));  //文件名
                $program_bitrate['MD5'] = md5($program_bitrate['SRC_FILE_NAME']);
                $program_bitrate['FILE_SIZE'] = I('post.file_size', '', array('intval','trim'));                                                
                $program_bitrate['FILE_TYPE'] = I('post.file_type', '', array('strip_tags','trim'));  
                $program_bitrate['SRC_FILE_PATH'] = I('post.src_file_path', '', array('strip_tags','trim'));  
                $program_bitrate['DOWN_URL'] = I('post.down_url', '', array('strip_tags','trim'));
                $program_bitrate['play_url'] = isset($_POST['play_url'])? I('post.play_url', '', array('strip_tags','trim')) : $program_bitrate['SRC_FILE_PATH'];
                
                $program_bitrate['BITRATE_STATUS'] = 3;
                $program_bitrate['CP_CODE'] = $program_bitrate['DATA_PROVIDER'] = $data_program['CP_CODE'];
                $program_bitrate['DEFINITION_CODE'] = strtolower($data_program['DEFINITION_CODE']);
                $program_bitrate['IS_CP_DELETE'] = 0;
                $program_bitrate['CREATE_DATE'] = $data_program['CREATE_DATE'];
                               
                $program_bitrate_id = M('program_bitrate')->data($program_bitrate)->add();  //添加tv端码率  
                
                //手机端码率
                $program_bitrate['SRC_FILE_NAME'] = I('post.md_src_file_name', '', array('strip_tags','trim')); 
                $program_bitrate['SRC_FILE_PATH'] = I('post.md_src_file_path', '', array('strip_tags','trim'));
                $program_bitrate['DOWN_URL'] = I('post.md_down_url', '', array('strip_tags','trim'));
                $program_bitrate['play_url'] = isset($_POST['md_play_url'])? I('post.md_play_url', '', array('strip_tags','trim')) : $program_bitrate['SRC_FILE_PATH'];
                $program_bitrate['DEFINITION_CODE'] = 'MD';
                $program_bitrate_id = M('program_bitrate')->data($program_bitrate)->add(); //添加手机端码率
                
                addlog('新增节目，CP：' . $aid);
                $this->success('恭喜！节目新增成功！',U('index'));
            } else {
                $this->error('抱歉，未知错误！');
            }
        }
    }
    
   public function import(){
//        if(empty($this->AREA['area_id'])){
//            $this->error('区域ID不存在！');
//        }       
       if (!empty ( $_FILES)){      
           $upload = new \Think\Upload();                            
           $upload->maxSize   =     1048576000 ;                        
           $upload->exts      =     array('xls','xlsx');            
           $upload->rootPath  = './Public/attached/excel/';                
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
               $version = $this->excel_version($PHPExcel);
               //echo $version;
               if(1.2 > $version){
                   $this->error('此模板已更新，请先下载最新版本！！');
               }
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
               $data = $this->importExcel_RAW($ExlData);                      
               if ($data) {                                              
                   $this->success("恭喜，节目成功导入".$data."条！",U('index'));     
               } else {       
                   //$this->error("导入失败，原因可能是excel表中有些节目已经导入，或表格格式错误！");// 提示错误       
                   echo "导入失败，原因可能是excel表中有些节目已经导入，或表格格式错误！";// 提示错误
               }     
           }      
       }else {       
           $this->display();       
       }                     
   }
   
   public function  importExcel_RAW($ExlData){   // 将导入表中的数据添加到  数据库数组中去  
       $program= M('Program');
       $program_bitrate = M('Program_bitrate');
       //dump(sizeof($ExlData));exit;
       $import_number = 0;
              
       for($i = 2,$j=0;$i<=sizeof($ExlData)+1;$i++,$j++){
           if(empty($ExlData[$i]['A'])){
               $this->error("第:".$i++."行  PROGRAM_NAME 为空!");
           }
           if(empty($ExlData[$i]['K'])){
               $this->error("第:".$i++."行  DEFINITION_CODE 为空!");
           }
           if(empty($ExlData[$i]['T'])){
               $this->error("第:".$i++."行  DEFINITION_CODE(MD) 为空!");
           }
           if(empty($ExlData[$i]['R'])){
               $this->error("第:".$i++."行  play_url(SD) 为空!");
           }
           if(empty($ExlData[$i]['S'])){
               $this->error("第:".$i++."行  DOWN_URL(SD) 为空!");
           }           
           if(empty($ExlData[$i]['X'])){
               $this->error("第:".$i++."行  play_url(MD) 为空!");
           }
           if(empty($ExlData[$i]['Y'])){
               $this->error("第:".$i++."行  DOWN_URL(MD) 为空!");
           }
       }
           
       for($i = 2,$j=0;$i<=sizeof($ExlData)+1;$i++,$j++){  
           $dataList['program'][] = array(
               'PROGRAM_NAME'      =>$ExlData[$i]['A'],                
               'METERIAL_CLASS'    =>$ExlData[$i]['B'],   
               'ZONE'              =>$ExlData[$i]['C'],                  
               'YEARS'             =>$ExlData[$i]['D'],                  
               'LAUNGUAGE_ID'      =>$ExlData[$i]['E'],                 
               'DIRECTOR'          =>$ExlData[$i]['F'],                 
               'LEADING_ROLE'      =>$ExlData[$i]['G'],
               'PROGRAM_STATUS_ID' =>$ExlData[$i]['H'],
               'POSTER'            =>$ExlData[$i]['I'],
               'PROGRAM_DESC'      =>$ExlData[$i]['J'],
               'DEFINITION_CODE'   =>$ExlData[$i]['K'],                             
               'SET_NUMBER'        =>$ExlData[$i]['L'],                             
               'CP_CODE'           =>$ExlData[$i]['M'],                             
               'PROGRAM_LENGTH'    =>$ExlData[$i]['N'], 
               'MATERIAL_TYPE_ID'  =>64, //河南党建
               'CREATE_DATE'       =>date('Y-m-d H:i:s'),
               'AREA_ID'           =>intval($this->AREA['area_id']), //地区ID
               'PROGRAM_STATUS_ID' => 'OFFLINE',
           );
           try{
           $program_id = $program->data($dataList['program'][$j])->add();
           $create_time = date('Y-m-d H:i:s');
           $dataList['program_bitrate'] = array(
               'SD'=>array(
                   'PROGRAM_ID'      => $program_id,
                   'CP_CODE'         =>$ExlData[$i]['M'],
                   'FILE_SIZE'       =>$ExlData[$i]['O'],
                   'SRC_FILE_NAME'   =>trim($ExlData[$i]['P']),
                   'SRC_FILE_PAT'    =>trim($ExlData[$i]['Q']),
                   'play_url'        =>trim($ExlData[$i]['R']),
                   'DOWN_URL'        =>trim($ExlData[$i]['S']),               
                   'MD5'             =>md5($ExlData[$i]['P']),
                   'DEFINITION_CODE' =>strtoupper($ExlData[$i]['K']),
                   'CREATE_DATE'     =>$create_time
               ),
              'MD'=>array(
                   'PROGRAM_ID'      => $program_id,
                   'DEFINITION_CODE' =>strtoupper($ExlData[$i]['T']),
                   'CP_CODE'         =>$ExlData[$i]['M'],
                   'FILE_SIZE'       =>$ExlData[$i]['U'],
                   'SRC_FILE_NAME'   =>trim($ExlData[$i]['V']),
                   'SRC_FILE_PAT'    =>trim($ExlData[$i]['W']),
                   'play_url'        =>trim($ExlData[$i]['X']),
                   'DOWN_URL'        =>trim($ExlData[$i]['Y']),
                   'MD5'             =>md5($ExlData[$i]['V']),                   
                   'CREATE_DATE'     =>$create_time
               ),                                             
           );  

            $program_bitrate->data($dataList['program_bitrate']['SD'])->add();
            $program_bitrate->data($dataList['program_bitrate']['MD'])->add();
              
            $import_number++;
           }catch (\Exception $e){
               echo $e->getMessage();
           }
          
       } 
       
       return $import_number;  
   }   
    
   public function  upload($file='',$code='sd',$type='dsj',$path='',$url='',$up_name=''){      
        //文件保存目录路径
        $path = $path ? $path : '/media/new/';
        $php_path = $_SERVER['HTTP_HOST']; //$_SERVER['DOCUMENT_ROOT'];
        $save_path = $php_path . $path;  //设置文件保存目录 注意包含/
        
        $file = $file ? $file :$_FILES['src_file_name'];
        //文件重命名
        $filename = explode(".",$file['name']);
        $filename[0]= $up_name ? $up_name : strtolower($code).'_'.$type.'_'.$filename[0].'_'.date("Ymd"); //设置文件名
        $name = implode(".",$filename);
        
        $data = array();
        $url = $url ? $url : date("Y")."/".date("m")."/".date("d")."/";  
        $save_path .= $url;
        
        if (!file_exists($save_path) || !is_dir($save_path)) {
            mkdir($save_path, 511,true);
        }
        if(move_uploaded_file($file['tmp_name'], $save_path.$name)){
           $data['file_name'] = $name;
           $data['file_url'] = $file['tmp_name'] ? C('URL').$path.$url.$name : '';
           $data['file_size'] = $file['size'];
           $data['file_type'] = $file['type'];
           exit(json_encode(array("result"=>"Success","url"=>$data['file_url'],"name"=>$data['file_name'],"size"=>$data['file_size'],"type"=>$data['file_type'],"err_code"=>0)));
        }
        else{
            exit(json_encode(array("result"=>"Fail","err_code"=>1)));
        }
    }

    public function get_index()
    {
        $path = $path ? $path : '/media/new';
        $php_path = $_SERVER['DOCUMENT_ROOT'];
        $save_path = $php_path . $path;  //设置文件保存目录 注意包含/        
        echo '<link rel="stylesheet" href="'."http://".$_SERVER['HTTP_HOST'].'/public/qwadmin/css/bootstrap.css"/>';
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
        echo '<div class="cf">
              <input id="chose" class="btn btn-info" type="button" value="确定">
              </div>';
        $this->bianli($save_path);
     
        $this->display();
    }
    
    public function bianli($path = '.'){
        $file_list =  array();
        $current_dir = opendir($path);
        $str .= '<table class="table table-striped table-bordered table-hover" style="margin:0">';
        while(($file = readdir($current_dir)) !==false){
            $sub_dir = $path .'/' . $file;
            if($file == '.' || $file == '..'){
                continue;
            }elseif(is_dir($sub_dir)){//如果是目录，则递归目录
               //echo "目录".$path.DIRECTORY_SEPARATOR .$file.":<br />";
                $this->bianli($sub_dir);
            }else{//如果是文件直接输出文件
                //echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;文件: ".$path.DIRECTORY_SEPARATOR.$file .' 大小：'.round(filesize($path.DIRECTORY_SEPARATOR.$file)/1024)."<br />";
                $filename =  mb_convert_encoding($file, 'UTF-8', 'GB18030');
                $fileurl = "http://".$_SERVER['HTTP_HOST'].'/'.substr(mb_convert_encoding($path, 'UTF-8', 'GB18030').'/'.$filename,strpos(mb_convert_encoding($path, 'UTF-8', 'GB18030').'/'.$filename,'media'));
                $file_list = explode('.', $filename);
                $file_type = $file_list[1];
                $str.='<tr>
                        <td class="center"><input class="ids" type="checkbox" name="ids[]" file_type="'.$file_type.'" src_file_name="'.$filename.'" file_size="'.filesize($path.DIRECTORY_SEPARATOR.$file).'" value="'.$fileurl.'"></td>
                        <td>'.mb_convert_encoding($path, 'UTF-8', 'GB18030').'/'.$filename.'</td>
                            <!--<td>'.filesize($path.DIRECTORY_SEPARATOR.$file).'</td>-->
                     </tr>';
            }
        }
        echo $str;
    }
    
    public function download_template(){
        header("Content-type:text/html;charset=utf-8");
        $save_name = 'set21.xlsx';
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/exceltemplate/set21.xlsx';      
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
    
    public function excel_version($excel=''){
        $version = '';
        if($excel){            
            $currentSheet = $excel->getSheet(1);
            $allColumn = $currentSheet->getHighestColumn();             
            $allRow = $currentSheet->getHighestRow();
            for ($currentRow = 1; $currentRow <= $allRow; $currentRow ++) {
                for ($currentColumn = 'A'; $currentColumn < $allColumn; $currentColumn ++) {
                    $address = $currentColumn . $currentRow;            
                    $ExlData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
                }
            }         
            $version = $ExlData[1][A];
            
            return $version;
        }
    }
}