<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：寒川<hanchuan@qiawei.com>
 * 日    期：2016-09-20
 * 版    本：1.0.0
 * 功能说明：文章控制器。
 *
 **/

namespace Qwadmin\Controller;

use Vendor\Tree;

class CategoryController extends ComController
{

    public function index()
    {

        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name,pub_sort_num')->order('pub_sort_num asc')->select();
        $category = $this->getCategoryMenu($category);
        $this->assign('category', $category);
        $this->display();
    }

    public function del()
    {

        $id = isset($_GET['id']) ? intval($_GET['id']) : false;
        if ($id) {
            $data['id'] = $id;
            $category = M('material_type');
            $program = M('program');
            $program_count = $program->where("material_type_id in ($id)")->count();
            $cate_count = $category->where('material_type_pid=' . $id)->count();
            if ($cate_count || $program_count) {
                die('2');//存在子类或节目，严禁删除。
            } else {
                $category->where('material_type_id=' . $id)->delete();
                addlog('删除分类，ID：' . $id);
            }
            die('1');
        } else {
            die('0');
        }

    }

    public function edit()
    {
        $id = isset($_GET['id']) ? intval($_GET['id']) : false;
        $currentcategory = M('material_type')->where('material_type_id=' . $id)->find();
        $this->assign('currentcategory', $currentcategory);

        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name')->where("material_type_id <> {$id}")->order('pub_sort_num asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
        $category = $tree->get_tree(0, $str, $currentcategory['material_type_pid']);
        $this->assign('category', $category);
        $this->display('form');
    }

    public function add()
    {

        $pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
        $category = M('material_type')->field('material_type_id,material_type_pid,material_type_name')->order('pub_sort_num asc')->select();
        $tree = new Tree($category);
        $str = "<option value=\$material_type_id \$selected>\$spacer\$material_type_name</option>"; //生成的形式
        $category = $tree->get_tree(0, $str, $pid);
        $this->assign('category', $category);
        $this->display('form');
    }

    public function update($act = null)
    {
        if ($act == 'order') {
            $id = I('post.id', 0, 'intval');
            if (!$id) {
                die('0');
            }
            $o = I('post.o', 0, 'intval');
            M('material_type')->data(array('PUB_SORT_NUM' => $o))->where("MATERIAL_TYPE_ID='{$id}'")->save();
            addlog('分类修改排序，ID：' . $id);
            die('1');
        }

        $id = I('post.id', false, 'intval');

        $data['MATERIAL_TYPE_PID'] = I('post.pid', 0, 'intval');
        $data['MATERIAL_TYPE_NAME'] = I('post.name');

        $data['PUB_SORT_NUM'] = I('post.o', 0, 'intval');
        if ($data['MATERIAL_TYPE_NAME'] == '') {
            $this->error('分类名称不能为空！');
        }
        if ($id) {
            if (M('material_type')->data($data)->where('MATERIAL_TYPE_ID=' . $id)->save()) {
                addlog('文章分类修改，ID：' . $id . '，名称：' . $data['material_type_name']);
                $this->success('恭喜，分类修改成功！');
                die(0);
            }
        } else {
            $id = M('material_type')->data($data)->add();
            if ($id) {
                addlog('新增分类，ID：' . $id . '，名称：' . $data['material_type_name']);
                $this->success('恭喜，新增分类成功！', 'index');
                die(0);
            }
        }
        $this->success('恭喜，操作成功！');
    }
}
