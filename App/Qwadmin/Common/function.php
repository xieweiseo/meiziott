<?php
/**
 * 增加日志
 * @param $log
 * @param bool $name
 */

function addlog($log, $name = false)
{
    $Model = M('log');
    if (!$name) {
        session_start();
        $uid = session('uid');
        if ($uid) {
            $user = M('member')->field('user')->where(array('uid' => $uid))->find();
            $data['name'] = $user['user'];
        } else {
            $data['name'] = '';
        }
    } else {
        $data['name'] = $name;
    }
    $data['t'] = time();
    $data['ip'] = $_SERVER["REMOTE_ADDR"];
    $data['log'] = $log;
    $Model->data($data)->add();
}


/**
 *
 * 获取用户信息
 *
 **/
function member($uid, $field = false)
{
    $model = M('Member');
    if ($field) {
        return $model->field($field)->where(array('uid' => $uid))->find();
    } else {
        return $model->where(array('uid' => $uid))->find();
    }
}

/**
 * 上传文件
 * @param glob   $file   全局变量FILE对象
 * @param string $code   文件前缀编码
 * @param string $type   文件前缀编码
 * @param string $path   上传目录
 * @param string $url    上传路径
 * @param string $up_name  上传文件名 
 * @return multitype:string
 */
function  file_upload($file='',$code='sd',$type='dsj',$path='',$url='',$up_name=''){      
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
    }

    return $data;
}

/**
 * 
 * 获取文件后缀名
 * 
 **/
function fileext($filename)
{
    return substr(strrchr($filename, '.'), 1);
}

/**
 * 
 * 生成随机字符
 * 
 */
function str_random($length)
{
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
    $max = strlen($chars) - 1;
    mt_srand((double)microtime() * 1000000);
    for($i = 0; $i < $length; $i++)
    {
        $hash .= $chars[mt_rand(0, $max)];
    }
    return $hash;
}

/**
 * 
 * 去除多维数组中的null值
 * 
 */
function array_filter_more($array_list){
    $arr = array();
    if(is_array($array_list)){        
        foreach ($array_list as $key=>$val){
            foreach (array_filter($val) as $k=>$v){
                $arr[$key][$k] = $v;
            }
        }
    }
    
    return $arr;
}

/**
 * 取出数组中的最大值
 * @param array $arr
 */
 function getMax($arr){
      $max=$arr[0];
     foreach($arr as $k=>$v){
         if($v>$max){
            $max=$v;
         }
     }
     return $max;
}

/**
 * @uses 截取字符串--支持中文截取
 * @return string
 */
function str_cut($string, $length, $dot = '...'){   
    //截字符串函数    GBK,UTF8
    $charset = 'utf-8';

    if(strlen($string) <= $length)
    {   //边界条件
        return $string;
    }
    
    $string = str_replace(array(' ','&nbsp;', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), array('∵',' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), $string);
    $strcut = '';
    if(strtolower($charset) == 'utf-8') {
        $n = $tn = $noc = 0;
        while($n < strlen($string)) {
    
            $t = ord($string[$n]);
            if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1; $n++; $noc++;
            } elseif(194 <= $t && $t <= 223) {
                $tn = 2; $n += 2; $noc += 2;
            } elseif(224 <= $t && $t <= 239) {
                $tn = 3; $n += 3; $noc += 2;
            } elseif(240 <= $t && $t <= 247) {
                $tn = 4; $n += 4; $noc += 2;
            } elseif(248 <= $t && $t <= 251) {
                $tn = 5; $n += 5; $noc += 2;
            } elseif($t == 252 || $t == 253) {
                $tn = 6; $n += 6; $noc += 2;
            } else {
                $n++;
            }
    
            if($noc >= $length) {
                break;
            }
        }
        if($noc > $length)
        {
            $n -= $tn;
        }
    
        $strcut = substr($string, 0, $n);
        $strcut = str_replace(array('∵', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…'), array(' ', '&amp;', '&quot;', '&#039;', '&ldquo;', '&rdquo;', '&mdash;', '&lt;', '&gt;', '&middot;', '&hellip;'), $strcut);
    
    } else{
    
        for($i = 0; $i < $length; $i++)
        {
            $strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
        }
    }
    $strcut = str_replace(array('&', '"', '<', '>'), array('&', '"', '<', '>'), $strcut);
    return $strcut.$dot;
}
