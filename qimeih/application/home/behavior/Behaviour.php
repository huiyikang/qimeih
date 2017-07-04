<?php
namespace app\home\behavior;

class Behaviour{
    public function run(){}
    
    //写入日志行为
    public function addLog($content){
        
        //写入日志
        $db = db('MemberLog');
        
        //操作变量
        $data = array(
            'time'=>time(),
            'content'=>$content['type'].$content['number'].'个'.config($content['dbname']),
            'ip'=>$_SERVER['SERVER_ADDR'],
            //'mname'=>session('member')['mname'],
            //'mid'=>session('member')['mid'],
        );
        //新增日志
        $db->insert($data);
    }
}