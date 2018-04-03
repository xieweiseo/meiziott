<?php
/**
 *
 * 版权所有：恰维网络<qwadmin.qiawei.com>
 * 作    者：小马哥<hanchuan@qiawei.com>
 * 日    期：2015-09-17
 * 版    本：1.0.3
 * 功能说明：文件上传控制器。
 *
 **/

namespace Qwadmin\Controller;

use Think\Upload;

class UploadController extends ComController
{
    public function index($type = null)
    {

    }

    public function uploadpic()
    {
        $Img = I('Img');
        $Path = null;
        if ($_FILES['img']) {
            $Img = 'http://'.$_SERVER['HTTP_HOST'].':'.$_SERVER["SERVER_PORT"].$this->saveimg($_FILES);
        }
        $BackCall = I('BackCall');
        $Width = I('Width');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Uploadpic');
    }
    
    private function saveimg($files)
    {
        $mimes = array(
            'image/jpeg',
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/pjpeg',
            'image/gif',
            'image/bmp',
            'image/x-png'
        );
        $exts = array(
            'jpeg',
            'jpg',
            'jpeg',
            'png',
            'pjpeg',
            'gif',
            'bmp',
            'x-png'
        );
        $upload = new Upload(array(
            'mimes' => $mimes,
            'exts' => $exts,
            'rootPath' => './Public/',
            'savePath' => 'attached/'.date('Y')."/".date('m')."/",
            'subName'  =>  array('date', 'd'),
        ));
        $info = $upload->upload($files);
        if(!$info) {// 上传错误提示错误信息
            $error = $upload->getError();
            echo "<script>alert('{$error}')</script>";
        }else{// 上传成功
            foreach ($info as $item) {
                $filePath[] = __ROOT__."/Public/".$item['savepath'].$item['savename'];
            }
            $ImgStr = implode("|", $filePath);
            return $ImgStr;
        }
    }    
    
    /**
     * ftp 上传图片
     */
    public function uploadftp()
    {
        $Img = I('Img');
        $Path = null;
        $BackCall = I('BackCall');
        $Width = I('Width');
        $Height = I('Height');
        $files = $_FILES;
        if ($files['img']) {
            $fileExt = substr($files['img']['name'],strpos($files['img']['name'],'.'));
            $fileName = 'ds_'.substr($Height, 0,1).'p_'.mt_rand(1, 10000).$fileExt;
            $mimes = array(
                'image/jpeg',
                'image/jpg',
                'image/jpeg',
                'image/png',
                'image/pjpeg',
                'image/gif',
                'image/bmp',
                'image/x-png'
            );
            $exts = array(
                'jpeg',
                'jpg',
                'jpeg',
                'png',
                'pjpeg',
                'gif',
                'bmp',
                'x-png'
            );
            $config = array(
                'fileName'=>$fileName,
                'mimes' => $mimes,
                'exts' => $exts,
                'rootPath' => './',
                'savePath' => date('Ymd')."/",
                'subName'  =>  $Width.'x'.$Height,
            );
            $ftp_config = array(
                'host'=>'192.168.11.32',
                'port'=>'5222',
                'username'=>'cms',
                'password'=>'cms_2017#$%',
                'timeout'=>90,
            );
            $upload = new Upload($config,'Ftp',$ftp_config);
            $info = $upload->upload($files);
            if(!$info) {// 上传错误提示错误信息
                $error = $upload->getError();
                echo "<script>alert('{$error}')</script>";
            }else{// 上传成功
                foreach ($info as $item) {
                    $filePath[] = __ROOT__.$item['savepath'].$item['savename'];
                }
                $ImgStr = implode("|", $filePath);
                $Img = 'http://images.cuhn.sllhtv.com/'.$ImgStr;
            }                        
        }

        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Uploadftp');
    }

    public function batchpic()
    {
        $ImgStr = I('Img');
        $ImgStr = trim($ImgStr, '|');
        $Img = array();
        if (strlen($ImgStr) > 1) {
            $Img = explode('|', $ImgStr);
        }
        $Path = null;
        $newImg = array();
        $newImgStr = null;
        if ($_FILES) {
            $newImgStr = $this->saveimg($_FILES);
            if ($newImgStr) {
                $newImg = explode('|', $newImgStr);
            }

        }
        $Img = array_merge($Img,$newImg);
        $ImgStr = implode("|", $Img);
        $BackCall = I('BackCall');
        $Width = I('u');
        $Height = I('Height');
        if (!$BackCall) {
            $Width = $_POST['BackCall'];
        }
        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        $this->assign('Width', $Width);
        $this->assign('BackCall', $BackCall);
        $this->assign('ImgStr', $ImgStr);
        $this->assign('Img', $Img);
        $this->assign('Height', $Height);
        $this->display('Batchpic');
    }
    
    public function uploadfile(){

        if ($_FILES['src_file_name']) {
            $video = $this->savevideo($_FILES['src_file_name']);
        }
        $Width = I('Width');
        $Height = I('Height');

        if (!$Width) {
            $Width = $_POST['Width'];
        }
        if (!$Height) {
            $Width = $_POST['Height'];
        }
        
        $this->assign('Video',$video);
        $this->assign('Width', $Width);
        $this->assign('Height', $Height);
        $this->display('Uploadfile');
    }
    
    public function savevideo($file='',$code='sd',$type='dsj',$path='',$url='',$up_name=''){
        //文件保存目录路径
        $path = $path ? $path : '/media/new/';
        $php_path = $_SERVER['DOCUMENT_ROOT'];
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
            return array("result"=>"Success","url"=>$data['file_url'],"name"=>$data['file_name'],"size"=>$data['file_size'],"type"=>$data['file_type'],"err_code"=>0);
        }
        else{
            return array("result"=>"Fail","err_code"=>1);
        }       
    }
}
