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

class ProgramBindController extends ComController
{
   
    public function index($sid = 0, $p = 1)
    {

        $p = intval($p) > 0 ? $p : 1;

        $program = M('program');
        $pagesize = 50;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $aid = isset($_GET['aid']) ? $_GET['aid'] : ''; //program_series_id
        $sid = isset($_GET['sid']) ? $_GET['sid'] : ''; 
        $keyword = isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '';
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        //$where = '1 = 1 ';
        $where = $this->AREA['area_id']?'area_id='.$this->AREA['area_id'].' ':'1 = 1 ';
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


        $count = $program->where($where)->count();
        $list = $program->field("{$prefix}program.*,{$prefix}material_type.material_type_name")->where($where)->order($orderby)->join("{$prefix}material_type ON {$prefix}material_type.material_type_id = {$prefix}program.material_type_id")->limit($offset . ',' . $pagesize)->select();

        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('aid',$aid);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function view($sid = 0, $aids = 0 ,$p = 1){
        
        $p = intval($p) > 0 ? $p : 1;
        
        $program = M('program');
        $pagesize = 20;#每页数量
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $sid = isset($_GET['sid']) ? $_GET['sid'] : '';
        $aids = isset($_GET['aids']) ? $_GET['aids'] : '';
        $keyword = trim(isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '');
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        //$where = '1 = 1 ';
        $where = $this->AREA['area_id']?'area_id='.$this->AREA['area_id'].' ':'1 = 1 ';        
        if ($sid) {
            $sids_array = category_get_sons($sid);
            $sids = implode(',',$sids_array);
            $where .= "and {$prefix}program.material_type_id in ($sids) ";
        }
        if($aids){
            $ids = M('program_series_rel')->field('PROGRAM_ID')->where("PROGRAM_SERIES_ID=".$aids)->select();
            //dump($ids);
            $aids_list = array();
            foreach ($ids as $aid){
                $aids_list[] = $aid['program_id'];
            }
            $progrma_ids = implode(',', $aids_list);
            if($ids){
                $where .= "and {$prefix}program.program_id in ($progrma_ids)";
            }
            else{               
                $where .= "and {$prefix}program.program_id = 0";  //无绑定节目条件
            }
        }
        if ($keyword) {
            $where .= "and {$prefix}program.program_name like '%{$keyword}%' ";
        }
        //默认按照taxis降序       
        $orderby = "{$prefix}program_series_rel.taxis asc";
        if ($order == "desc") {        
            $orderby = "{$prefix}program_series_rel.taxis desc";
        }
        if($order == "asc"){
            $orderby = "{$prefix}program.create_date asc";
        }
        //获取栏目分类
        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
        $category = $tree->get_tree(0, $str, $sid);
        $this->assign('category', $category);//导航
            
        $count = $program->where($where)->count();
        $list = $program->field("{$prefix}program_series_rel.taxis,{$prefix}program_series_rel.rel_id,{$prefix}program.*,{$prefix}material_type.material_type_name")
                                ->where($where)->order($orderby)
                                  ->join("{$prefix}material_type ON {$prefix}material_type.material_type_id = {$prefix}program.material_type_id")
                                   ->join("{$prefix}program_series_rel ON {$prefix}program_series_rel.program_id = {$prefix}program.program_id")
                                    ->limit($offset . ',' . $pagesize)->group('program_id')->select();
   
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('program_ids',$progrma_ids);
        $this->assign('program_series_ids',$aids); 
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();        
    }
    
    public function play($aid){
        $aid = intval($aid);
        $program_info = M('program')->where('program_id=' . $aid)->find();   
        $program_bitrate = M('program_bitrate')->field('program_rate_id,program_id,definition_code,src_file_name,src_file_path,src_file_path,play_url,down_url,cp_code')->where("program_id=$aid")->select();
        //dump($program_bitrate);
        foreach ($program_bitrate as $v1){
            foreach ($v1 as $k=>$v){
                if(strtolower($v) == 'sd' || strtolower($v) == 'hd' || strtolower($v) == 'uhd'){
                    $tv = $v1;
                    $tv['view_play'] = str_replace('.ts', '.m3u8', $v1['play_url']);
                }
                if(strtolower($v) == 'md'){
                    $md = $v1;
                }
            }
        }
        $program_bitrate['tv'] = $tv;
        $program_bitrate['md'] = $md;        
        //dump($program_bitrate);exit;
        $this->assign('program_info',$program_info);
        $this->assign(array_filter($program_bitrate));
        $this->display();
    }
    
    public function bind($aid=0, $bids=0){
        
        $aid = intval($aid);      //program_series_id
        $bids = I('post.bids');   //program_ids;

        //dump($_POST);exit;       
        if(empty($aid) && empty($bids)){
            
             $this->error('参数错误！',U('ProgramSeries/index'));
        }    
        else{
            //检查节目是否注入
            $program = D('Program');
//             foreach ($bids as $ids){
//                 $program_info = $program->field('program_id,program_name,cms_result_hw_status,cms_result_zte_status')->where('program_id='.$ids)->find();
//                 if(empty($program_info['cms_result_hw_status']) || empty($program_info['cms_result_zte_status'])){
//                     $this->error('节目【'.$program_info['program_name'].'】还未注入...');
//                 }
//             }
            //节目绑定
            $rel_time = date('Y-m-d H:i:s');
            $taxis = $program->getSeriesSortID($aid); //获取节目集中最大taxis
            $taxis = $taxis>1?$taxis+1:$taxis;
            foreach ($bids as $ids){
                $data['PROGRAM_SERIES_ID'] = $aid;
                $data['PROGRAM_ID'] = $ids;
                $data['STATUS'] = 1;
                $data['TAXIS'] = $taxis; //100; //sort
                $data['REL_TIME'] = $rel_time;
                $data['REL_USER'] = $this->USER['user'];
                M('program_series_rel')->data($data)->add();
                addlog('绑定节目，BP：' . $aid.'->'.$ids);
                $taxis++;
            }
            
            $this->success('恭喜！节目绑定成功！',U('ProgramSeries/index'));
        }
    }
    
    public function unbind(){
        $program_ids = I('post.program_ids');
        $program_series_ids = I('post.program_series_ids');
        
        //dump($program_ids);exit;
        if($program_ids && $program_series_ids){
            foreach ($program_ids as $ids){
                M('program_series_rel')->where(array('PROGRAM_ID'=>$ids,'PROGRAM_SERIES_ID'=>$program_series_ids))->delete();
                addlog('解绑节目，UBP：' . $program_series_ids.'->'.$ids);
            }            
            
            $this->success('节目解绑成功！');
        }
        else{
            $this->error('参数错误！');
        }
    }
    
   public function program_review($program_id){
       $program_id = intval($program_id);
       $program_status_id = isset($_GET['program_status'])?I('get.program_status','',array('strip_tags','trim')):'';

       if($program_id){
           if($program_status_id=='online'){
               $program_status_id = 'OFFLINE';
               $result = M('program')->data(array('PROGRAM_STATUS_ID'=>$program_status_id))->where("PROGRAM_ID='{$program_id}'")->save();
               
               if($result){
                   $this->success('节目下线成功！');
               }
               else{
                   $this->error('节目下线失败!');
               }               
           }
           if($program_status_id=='offline' || empty($program_status_id)){
               $program_status_id = 'ONLINE';
               $result = M('program')->data(array('PROGRAM_STATUS_ID'=>$program_status_id))->where("PROGRAM_ID='{$program_id}'")->save();
               
               if($result){
                   $this->success('节目上线成功！');
               }
               else{
                   $this->error('节目上线失败!');
               }               
           }
       }
       else{
           $this->error('参数缺失！');
       }
       
   }
   
   public function review_list(){
       $program_ids = I('post.program_ids');
       $program = M('program');       
       $pids = implode(',', $program_ids);
       $map = 'program_id in ('.$pids.')';
       //dump($program_ids);exit;
       if(!empty($program_ids)){
           $status_ids = $program->field('PROGRAM_ID,PROGRAM_STATUS_ID')->where($map)->select();
           //dump($status_ids);exit;
           foreach ($status_ids as $key=>$val){
               if($val['program_status_id']=='OFFLINE'){
                   $online_status = 'ONLINE';
               }
               else if($val['program_status_id']=='ONLINE'){
                   $online_status = 'OFFLINE';
               }
               else if($val['program_status_id']==NULL){
                   $online_status = 'ONLINE';
               }
               
              $program->data(array('PROGRAM_STATUS_ID' => $online_status))->where("program_id='{$val['program_id']}'")->save();
           }
           //dump($program_id);exit;
           $this->success('节目审核成功！');
       }
       else{
         $this->error('参数错误！');  
       }
   }
    
   public function sort(){
       $id = I('post.id', 0, 'intval');
       if (!$id) {
           die('0');
       }
       $o = I('post.o', 0, 'intval');
       M('program_series_rel')->data(array('TAXIS' => $o))->where("PROGRAM_ID='{$id}'")->save();
       addlog('绑定节目排序，ID：' . $id);
       die('1');      
   }
   
   public function sort_list(){
       $ids = I("program_ids");
       $oids = I("o");
       $program_series_rel = M('program_series_rel');
       
       //dump($_POST);exit;
       if(count($ids)){
           foreach ($ids as $val){
               foreach ($oids as $k=>$v){
                   if($val==$k){
                      $program_series_rel->data(array('TAXIS' => $v))->where("REL_ID=".$k)->save(); 
                   }
               }
           }
       }       
       $this->success('排序更新成功！');
   }
}