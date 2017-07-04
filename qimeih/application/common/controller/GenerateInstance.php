<?php
namespace app\common\controller;

use app\common\controller\Factory;
//抽象类提供方法，需要载入DB和controller，保存控制器或者数据库实例
class GenerateInstance extends Factory{
    //
    public static function getInstance($string){
        //解析是否字符串
        if (is_string($string)){

                switch ($string){
                    case 'OperateData' :
                        return new OperateData();
                        break;
                }
                
        }else{
            $this->error('参数类型错误！');
        }
    }
    
}
