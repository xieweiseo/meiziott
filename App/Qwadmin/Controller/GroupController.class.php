<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-01-20
 * 版    本：1.0.0
 * 功能说明：用户控制器。
 *
 **/

namespace Qwadmin\Controller;

class GroupController extends ComController
{
    public function index()
    {
        $group = M('auth_group')->select();
        
        $area = M('auth_area')->select();
        
        $area_list = array();
        foreach ($area as $k=>$val){          
            $area_list[$val['id']] = $val['area_name'];           
        }
        
        //dump($area_list);exit;
        
        foreach ($group as $k=>$val){
            
            foreach ($area_list as $a=>$b){
   
                if($val['area_id']==$a){
                    $group[$k]['area_name'] = $b;
                }
            }
            
//             switch ($val['area_id']){
//                 case '371' :
//                   $group[$k]['area_id'] = '郑州';  
//                   break;
//                 case '372':
//                   $group[$k]['area_id'] = '禹州';
//                   break;
//                 case '379' :
//                   $group[$k]['area_id'] = '洛阳';
//                   break;
//                 case '378':
//                   $group[$k]['area_id'] = '开封';
//                   break;

//                 case '377':
//                   $group[$k]['area_id'] = '南阳';
//                   break;                    
//                 case '391':
//                   $group[$k]['area_id'] = '焦作';
//                   break;    
//                 case '376':  
//                   $group[$k]['area_id'] = '信阳';
//                   break;                                     
//                 default:
//                     $group[$k]['area_id'] = '--';
//                     break;
//             }
        }
        
        //dump($group);exit;
        
        $this->assign('list', $group);
        $this->assign('nav', array('user', 'grouplist', 'grouplist'));//导航
        $this->display();
    }

    public function del()
    {

        $ids = isset($_POST['ids']) ? $_POST['ids'] : false;
        if (is_array($ids)) {
            foreach ($ids as $k => $v) {
                $ids[$k] = intval($v);
            }
            $ids = implode(',', $ids);
            $map['id'] = array('in', $ids);
            if (M('auth_group')->where($map)->delete()) {
                addlog('删除用户组ID：' . $ids);
                $this->success('恭喜，用户组删除成功！');
            } else {
                $this->error('参数错误！');
            }
        } else {
            $this->error('参数错误！');
        }
    }

    public function update()
    {

        $data['title'] = isset($_POST['title']) ? trim($_POST['title']) : false;
        $id = isset($_POST['id']) ? intval($_POST['id']) : false;
        if ($data['title']) {
            $status = isset($_POST['status']) ? $_POST['status'] : '';
            if ($status == 'on') {
                $data['status'] = 1;
            } else {
                $data['status'] = 0;
            }
            //如果是超级管理员一直都是启用状态
            if ($id == 1) {
                $data['status'] = 1;
            }

            $rules = isset($_POST['rules']) ? $_POST['rules'] : 0;
            if (is_array($rules)) {
                foreach ($rules as $k => $v) {
                    $rules[$k] = intval($v);
                }
                $rules = implode(',', $rules);
            }
            $data['rules'] = $rules;
            if ($id) {
                $data['area_id'] = I('area_id');
                $group = M('auth_group')->where('id=' . $id)->data($data)->save();
                if ($group) {
                    addlog('编辑用户组，ID：' . $id . '，组名：' . $data['title']);
                    $this->success('恭喜，用户组修改成功！');
                    exit(0);
                } else {
                    $this->success('未修改内容');
                }
            } else {
                $data['area_id']= I('area_id');
                M('auth_group')->data($data)->add();
                addlog('新增用户组，ID：' . $id . '，组名：' . $data['title']);
                $this->success('恭喜，新增用户组成功！');
                exit(0);
            }
        } else {
            $this->success('用户组名称不能为空！');
        }
    }

    public function edit()
    {

        $id = isset($_GET['id']) ? intval($_GET['id']) : false;
        if (!$id) {
            $this->error('参数错误！');
        }

        $group = M('auth_group')->where('id=' . $id)->find();
        if (!$group) {
            $this->error('参数错误！');
        }
        //获取所有启用的规则
        $rule = M('auth_rule')->field('id,pid,title')->where('status=1')->order('o asc')->select();
        $area = M('auth_area')->field('id,area_name,code')->where('status=1')->order('sort desc')->select();
        $group['rules'] = explode(',', $group['rules']);
        $rule = $this->getMenu($rule);
        $this->assign('rule', $rule);
        $this->assign('group', $group);
        $this->assign('area', $area);
        $this->assign('nav', array('user', 'grouplist', 'addgroup'));//导航
        $this->display('form');
    }

    public function add()
    {

        //获取所有启用的规则
        $rule = M('auth_rule')->field('id,pid,title')->where('status=1')->order('o asc')->select();
        $area = M('auth_area')->field('id,area_name,code')->where('status=1')->order('sort desc')->select();
        $rule = $this->getMenu($rule);
        $this->assign('rule', $rule);
        $this->assign('area',$area);
        $this->display('form');
    }

    public function status()
    {

        $id = I('id');
        if (!$id) {
            $this->error('参数错误！');
        }
        if ($id == 1) {
            $this->error('此用户组不可变更状态！');
        }
        $group = M('auth_group')->where('id=' . $id)->find();
        if (!$group) {
            $this->error('参数错误！');
        }
        $status = $group['status'];
        if ($status == 1) {
           $res = M('auth_group')->data(array('status' => 0))->where('id=' . $id)->save();
        }
        if ($status != 1 ) {
            $res = M('auth_group')->data(array('status' => 1))->where('id=' . $id)->save();
        }
        if ($res) {
            $this->success('恭喜，更新状态成功！');
        } else {
            $this->error('更新失败！');
        }
    }
    
    public function area(){
        
        $area = M('auth_area')->select();
        
        $this->assign('area',$area);
        
        $this->display();
    }
    
    public function area_edit($id = 0){
        $id = intval($id);
        $area_info = M('auth_area')->where('id=' . $id)->find();
        $this->assign('area',$area_info);
        $this->display('edit');
    }
    
    public function area_update($aid = 0){
        $id = intval($aid);    
        $data['area_name'] = I('post.area_name','',array('strip_tags','trim'));
        $data['code'] = I('post.code',array('strip_tags','trim'));
        $data['sort'] = I('post.sort',array('strip_tags','trim'));
        $data['status'] = I('post.status')?I('post.status',array('intval','trim')):0;
        
        if ($data['area_name'] == '') {
            $this->error('地区名称不能为空！');
        }
        if ($id) {
            if (M('auth_area')->data($data)->where('id=' . $id)->save()) {
                addlog('地区修改，ID：' . $id . '，名称：' . $data['area_name']);
                $this->success('恭喜，地区修改成功！');
                die(0);
            }
            else{
                $this->error('抱歉，地区没有被改变！');
                die(0);                
            }
         }else {
            $id = M('auth_area')->data($data)->add();
            if ($id) {
                addlog('新增地区，ID：' . $id . '，名称：' . $data['area_name']);
                $this->success('恭喜，新增地区成功！', 'area');
                die(0);
            }
            else{
                $this->success('抱歉，新增地区失败！', 'area');
                die(0);
            }
        }
     }
     
     public function area_del()
     {    
         $ids = isset($_POST['ids']) ? $_POST['ids'] : false;
         if (is_array($ids)) {
             foreach ($ids as $k => $v) {
                 $ids[$k] = intval($v);
             }
             $ids = implode(',', $ids);
             $map['id'] = array('in', $ids);
             if (M('auth_area')->where($map)->delete()) {
                 addlog('删除地区ID：' . $ids);
                 $this->success('恭喜，地区删除成功！');
             } else {
                 $this->error('参数错误！');
             }
         } else {
             $this->error('参数错误！');
         }
     }     
     
    
}