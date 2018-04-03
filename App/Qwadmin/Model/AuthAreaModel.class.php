<?php
namespace Qwadmin\Model;
use Think\Model;

class AuthAreaModel extends Model {
    protected $tableName = 'auth_area';
    
    public function getGroupList($group =''){
        if($group){
            foreach ($group as $k=>$val){
            switch ($val['area_id']){
                case '371' :
                    $group[$k]['area_id'] = '郑州';
                    break;
                case '372':
                    $group[$k]['area_id'] = '禹州';
                    break;
                case '379' :
                    $group[$k]['area_id'] = '洛阳';
                    break;
                case '378':
                    $group[$k]['area_id'] = '开封';
                    break;
            
                case '377':
                    $group[$k]['area_id'] = '南阳';
                    break;
                case '391':
                    $group[$k]['area_id'] = '焦作';
                    break;
                case '376':
                    $group[$k]['area_id'] = '信阳';
                    break;
                default:
                    $group[$k]['area_id'] = '--';
                    break;
              }
           }                      
        }
         return $group;
    }
   
    public function get_area_kv(){
        $area = $this->field('id,area_name,code')->where('status=1')->order('sort desc')->select();
        $area_list = array();
        
        if($area){
            
            foreach ($area as $k=>$val){
                $area_list[$val['id']] = $val['area_name'];
            }
        }
        
        //dump($area_list);exit;
        return $area_list;             
    }
    
    
    public function getAreaName($group= ''){
        if($group){
            
            $group = M('auth_group')->select();
            
            $area = $this->field('id,area_name,code')->where('status=1')->order('sort desc')->select();
            
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
            
            
//             foreach ($group as $k=>$val){
//             switch ($val['area_id']){
//                 case '371' :
//                     $group[$k]['title']= $val['title'] .'--'. '郑州';
//                     break;
//                 case '372':
//                     $group[$k]['title']= $val['title'] .'--'. '禹州';
//                     break;
//                 case '379' :
//                     $group[$k]['title']= $val['title'] .'--'. '洛阳';
//                     break;
//                 case '378':
//                     $group[$k]['title']= $val['title'] .'--'. '开封';
//                     break;
            
//                 case '377':
//                     $group[$k]['title']= $val['title'] .'--'. '南阳';
//                     break;
//                 case '391':
//                     $group[$k]['title']= $val['title'] .'--'. '焦作';
//                     break;
//                 case '376':
//                     $group[$k]['title']= $val['title'] .'--'. '信阳';
//                     break;
//                 default:
//                     $group[$k]['area_id'] = '';
//                     break;
//               }
//            }                      
           }
        }
            
       // print_r($group);
        return $group;
    }
    
}