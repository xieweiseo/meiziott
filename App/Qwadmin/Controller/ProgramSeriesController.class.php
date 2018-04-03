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
use Qwadmin\Model\ProgramModel;
class ProgramSeriesController extends ComController
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

        $program_series = M('ProgramSeries');
        $pagesize = 100;#每页数量
        $this->assign('pagesize',$pagesize);
        $offset = $pagesize * ($p - 1);//计算记录偏移量
        $prefix = C('DB_PREFIX');
        $sid = isset($_GET['sid']) ? $_GET['sid'] : '';
        $keyword = trim(isset($_GET['keyword']) ? htmlentities($_GET['keyword']) : '');
        $years = isset($_GET['years']) ? $_GET['years'] : '';
        $order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        
        //$where = "1 = 1 ";
        $where = $this->AREA['area_id']?'area_id='.$this->AREA['area_id'].' ':'1 = 1 ';        
        if($years){
            $where .= "and years ='{$years}'";
        }
        if ($keyword) {
            $where .= "and program_series_name like '%{$keyword}%' ";
        }
        if($status){
            $where .= "and status ='".$status."'";
        }
        //默认按照时间降序
        $orderby = "create_date desc";
        if ($order == "asc") {

            $orderby = "create_date asc";
        }
        
        $count = $program_series->where($where)->count();
        $this->assign('count',$count);
        $lists = $program_series->field('PROGRAM_SERIES_ID,PROGRAM_SERIES_NAME,YEARS,CREATE_DATE,STATUS,AREA_ID')->where($where)->order($orderby)->limit($offset . ',' . $pagesize)->select();
        $program_series_list = array();
        $program_modle = D('program');
        $program = A('Program');
        foreach ($lists as $k=>$val){
            $program_series_list[$k]=$val;
            $program_series_list[$k]['program_count'] = $program_modle->getProgramCount($val['program_series_id']);
            $program_series_list[$k]['area_name'] = $program->get_area_name($val['area_id']);
        }
        
        $page = new \Think\Page($count, $pagesize);
        $page = $page->show();
        $this->assign('list', $program_series_list);
        $this->assign('page', $page);
        $this->display();
    }

    public function del()
    {

        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        if ($aids) {
            if (is_array($aids)) {
                $aids = implode(',', $aids);
                //$map['program_series_id'] = array('in', $aids);
                $map = 'program_series_id in ('.$aids.')';
            } else {
                $map = 'program_series_id=' . $aids;
            }
            $program_series_rel = M('program_series_rel');
            $psr_count = $program_series_rel->where("program_series_id in ($aids)")->count();
            
            /*
            if(M('program_series')->where($map)->delete() && M('program_series_rel')->where($map)->delete()){
                $this->success('节目集删除成功！',U('index'));
             }
             else{
                 $this->error('节目集删除失败！');
             }
            */
            
            if($psr_count){
                $this->error('该节目集下有节目，无法删除。');
            }
            else{ 
              try {
                  M('program_series')->where($map)->delete();
                  M('program_series_rel')->where($map)->delete();
                  addlog('删除节目集，PSD：' . $aids);
                  $this->success('恭喜，节目集删除成功！');
              }
              catch (\Exception $exc){
                  $this->error('删除异常！');
              }              
            }
            
        } else {
            $this->error('参数错误！');
        }

    }

    public function online()
    {    
        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;      
        if ($aids) {
            if (is_array($aids)) {
                $aids = implode(',', $aids);
                $map['PROGRAM_SERIES_ID'] = array('in', $aids);
            } else {
                $map = 'PROGRAM_SERIES_ID=' . $aids;
            }
            $status = M('Program_series')->where($map)->getField('status');
            if(strtolower($status)==='offline'){            
                if ($r=M('Program_series')->where($map)->setField('STATUS','ONLINE')) {
                    addlog('上线节目集，ONL：' . $aids);
                    $this->success('恭喜，节目集上线成功！');
                } else {
                    //dump( M('Program_series')->getLastSql());
                    $this->error('参数错误！');
                }
            }
            else{
                $this->error('该节目集已上线，错误！');
            }
            
        } else {
            $this->error('参数错误！');
        }
    }    
    
    public function offline()
    {
        $aids = isset($_REQUEST['aids']) ? $_REQUEST['aids'] : false;
        if ($aids) {
            if (is_array($aids)) {
                $aids = implode(',', $aids);
                $map['PROGRAM_SERIES_ID'] = array('in', $aids);
            } else {
                $map = 'PROGRAM_SERIES_ID=' . $aids;
            }
            $status = M('Program_series')->where($map)->getField('status');
            if(strtolower($status)==='online'){
                if (M('Program_series')->where($map)->setField('STATUS','OFFLINE')) {                
                    addlog('下线节目集，OFL：' . $aids);
                    D('program')->program_usync($aids); //下线通知
                    $this->success('恭喜，节目集下线成功！');
                    
                } else {
                    $this->error('参数错误！');
                }
            }
            else{
                $this->error('该节目集已下线，错误！');
            }
            
            
        } else {
            $this->error('参数错误！');
        }
        exit;
    }    
    
    public function edit($aid)
    {

        $aid = intval($aid);
        $program_series = M('program_series')->where('program_series_id=' . $aid)->find();
        
        if($this->AREA['area_id']){
            if($program_series['area_id']!==$this->AREA['area_id']){
                $this->error('没有权限访问本页面!');
            }
        }        
        
        if ($program_series) {
            $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();
            $tree = new Tree($category);
            $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式            
            $category = $tree->get_tree(0, $str, $program_series['program_type_id']);
            $this->assign('category', $category);//分类
            $this->assign('program_series', $program_series);
        } else {
            $this->error('参数错误！');
        }
        $this->display('form');
    }
    
    public function review($aid)
    {    
        $aid = intval($aid);
        $program_series = M('program_series')->where('program_series_id=' . $aid)->find();
        if ($program_series) {
            $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();
            $tree = new Tree($category);
            $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
            $category = $tree->get_tree(0, $str, $program_series['program_type_id']);
            $this->assign('category', $category);//分类
            $this->assign('program_series', $program_series);
        } else {
            $this->error('参数错误！');
        }
        $this->display();
    } 
    
    public function review_update($aid = 0){
        $aid = intval($aid); //program_series_id
        $data_program_series['STATUS'] = isset($_POST['status'])?'ONLINE':'OFFLINE';
        $data_program_series['STATUS_TIME'] = date('Y-m-d H:i:s');
        $data_program_series['PROGRAM_SERIES_REVIEW_DESC'] = isset($_POST['status'])?'':I('program_series_review_desc','','strip_tags');        
        
        if($data_program_series['STATUS']=='ONLINE'){
            $data_program_series['MESSAGE'] = '节目集上线成功！';
        }
        else if($data_program_series['STATUS']=='OFFLINE'){
            $data_program_series['MESSAGE'] = '节目集下线成功！';
        }
        
        //print_r($data_program_series);exit;
        if ($aid) {            
            $series_result = M('program_series')->data($data_program_series)->where('program_series_id=' . $aid)->save();
            if($series_result){
                addlog('编辑节目集，UPS：' . $aid);
                $this->success($data_program_series['MESSAGE'],'index');
            }
            else{
                $this->success('节目集审核失败~~');
            }
        }
        else{
            $this->error('aid参数缺失！');
        }
    }

    public function update($aid = 0)
    {

        $aid = intval($aid); //program_series_id
        //dump($_POST);exit;
        
        $data_program_series['PROGRAM_TYPE_ID'] = isset($_POST['sid']) ? intval($_POST['sid']) : 0;  //节目类别
        $data_program_series['PROGRAM_SERIES_NAME'] = I('post.title', '', array('strip_tags','trim')); //节目名
        $data_program_series['PROGRAM_PINYIN'] = I('post.program_pinyin', '', array('strip_tags','trim')); //节目名拼音
        
        $data_program_series['PROGRAM_SERIES_ALIAS'] = I('post.program_series_alias','','trim'); //别名
        $data_program_series['PROGRAM_SERIES_EN_NAME'] = I('post.program_series_en_name','','trim'); //英文名                
        $data_program_series['PROGRAM_CLASS'] = I('post.program_class','','trim'); //节目类型        
        $data_program_series['PROGRAM_CONTENT_TYPE'] = 'video'; 
        $data_program_series['TAG'] = I('post.tag', '', array('strip_tags','trim')); //标签
        $data_program_series['DEFINITION_CODE'] =  'HD';
        $data_program_series['DIRECTOR'] = I('post.director', '', array('strip_tags','trim')); //导演
        $data_program_series['LEADING_ROLE'] = I('post.leading_role', '', array('strip_tags','trim'));
        $data_program_series['LEADING_ROLE_PINYIN'] = I('post.leading_role_pinyin','','trim');
        $data_program_series['YEARS'] = isset($_POST['years']) ? $_POST['years'] : 0;
        $data_program_series['LANGUAGE_ID'] = 1;
        $data_program_series['PREMIERE_DATE'] = !empty($_POST['premiere_date']) ? $_POST['premiere_date'] : null;  //上映时间
        $data_program_series['SORT_TEYPE'] = 'desc';
        //$data_program_series['STATUS'] = isset($_POST['status'])?'ONLINE':'OFFLINE';
             
        $data_program_series['TIME_LENGTH'] = I('post.time_length', 0 ,array('intval','trim')); //节目时长
        $data_program_series['ZONE'] = I('post.zone', '', array('strip_tags','trim'));  //地区
        $data_program_series['CP_CODE'] = $data_program_series['DATA_PROVIDER'] = 'CMS';    //TENCENT
        $data_program_series['IS_CDN'] = 1;
        $data_program_series['WEIGHT'] = 0;
       
        $data_program_series['PROGRAM_SERIES_DESC'] = I('program_series_desc','','strip_tags');         

        $data_program_series['POSTER'] = $data_program_series['UPLOAD_POSTER'] = I('post','',array('strip_tags','trim'));//strtolower(I('post.thumbnail')); //海报        
        $data_program_series['SMALL_POSTER_ADDR'] = I('small_post_addr','',array('strip_tags','trim'));
        $data_program_series['BIG_POSTER_ADDR'] = I('big_post_addr','',array('strip_tags','trim'));     
        //$data_program_series['UPLOAD_POSTER'] = explode('/', $data_program_series['POSTER']);
        //$data_program_series['UPLOAD_POSTER'] = $data_program_series['UPLOAD_POSTER'][8]; //上传图(大图文件名不包含路径)
  
         
        
        if (!$data_program_series['PROGRAM_TYPE_ID'] or !$data_program_series['PROGRAM_SERIES_NAME'] or !$data_program_series['PROGRAM_SERIES_DESC']) {
            $this->error('警告！节目分类、节目题及节目简介为必填项目。');
        }
        if ($aid) {
            $data_program_series['LAST_MODIFY_DATE'] = date('Y-m-d H:i:s');
            M('program_series')->data($data_program_series)->where('program_series_id=' . $aid)->save();
            addlog('编辑节目集，UPS：' . $aid);
            $this->success('恭喜！节目集编辑成功！',U('index'));
        } else {
            $data_program_series['STATUS'] = 'OFFLINE';
            $data_program_series['CREATE_DATE'] = date('Y-m-d H:i:s');
            $data_program_series['AREA_ID'] = $this->AREA['area_id']; //地区ID
            $aid = M('program_series')->data($data_program_series)->add();
            if ($aid) {                               
                addlog('新增节目集，CPS：' . $aid);
                $this->success('恭喜！节目集新增成功！',U('index'));
            } else {
                $this->error('抱歉，未知错误！');
            }
        }
    }
    
    public function import(){
//         if(empty($this->AREA['area_id'])){
//             $this->error('区域ID不存在！');
//         }
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
                if(1.2 > $version['version']){
                    $this->error('此模板已更新，请先下载最新版本！！');
                }
                if(strtolower(CONTROLLER_NAME) !== strtolower($version['series'])){
                    $this->error('此模板不正确，请选择节目集导入模板！！');
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
                $data = $this->importExcel_RAW($ExlData);                    // 调用公用方法的读数组并写入数据库操作
                //dump($data['program_series']);exit;
                $program_series= M('Program_series');                                         // 生成数据库对象
                $result = $program_series->addAll($data['program_series']);                             // 批量写入数据库
                if ($result) {                                               // 验证
                    $this->success("恭喜，节目集成功导入".count($data['program_series'])."条！",U('index'));
                } else {
                    $this->error("导入失败，原因可能是excel表中有些节目已经导入，或表格格式错误！");// 提示错误
                }
            }
        }else {
            $this->display();
        }
    }
     
    public function  importExcel_RAW($ExlData){   // 将导入表中的数据添加到  数据库数组中去
        for($i = 2,$j=0;$i<=sizeof($ExlData)+1;$i++,$j++){
            $dataList['program_series'][] = array(
                'PROGRAM_SERIES_NAME'   =>$ExlData[$i]['A'],
                'PROGRAM_PINYIN'        =>$ExlData[$i]['B']?$ExlData[$i]['B']:'',
                'PROGRAM_SERIES_DESC'   =>strip_tags($ExlData[$i]['C']),
                'POSTER'                =>$ExlData[$i]['D']?$ExlData[$i]['D']:'',
                'SMALL_POSTER_ADDR'     =>$ExlData[$i]['E']?$ExlData[$i]['E']:'',
                'BIG_POSTER_ADDR'       =>$ExlData[$i]['F']?$ExlData[$i]['F']:'',
                'PROGRAM_CLASS'         =>$ExlData[$i]['G'],
                'ZONE'                  =>$ExlData[$i]['H'],
                'YEARS'                 =>$ExlData[$i]['I'],
                'LAUNGUAGE_ID'          =>$ExlData[$i]['J'],
                'PROGRAM_TOTAL_COUNT'   =>$ExlData[$i]['K']?$ExlData[$i]['K']:1,
                'DIRECTOR'              =>$ExlData[$i]['L'],
                'LEADING_ROLE'          =>$ExlData[$i]['M'],
                'STATUS'                =>'OFFLINE',//$ExlData[$i]['N'],
                'DEFINITION_CODE'       =>strtoupper($ExlData[$i]['O']), 
                'PROGRAM_COUNT'         =>$ExlData[$i]['P']?$ExlData[$i]['P']:1,
                'CP_CODE'               =>$ExlData[$i]['Q'],
                'PROGRAM_TYPE_ID'       =>64,   //河南党建
                'CREATE_DATE'           =>date('Y-m-d H:i:s'),
                'AREA_ID'               =>intval($this->AREA['area_id']),   //地区ID
            );
        }
        return $dataList;
    }    

    public function download_template(){
        header("Content-type:text/html;charset=utf-8");
        $save_name = 'set22.xlsx';
        $file_path = $_SERVER['DOCUMENT_ROOT'].'/data/exceltemplate/set22.xlsx';
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
            $version = array('version'=>$ExlData[1][A],'series'=>$ExlData[2][A]);
    
            return $version;
        }
    }    
}