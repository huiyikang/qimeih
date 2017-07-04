<?php
namespace app\home\controller;

use app\common\controller\GenerateInstance;

class Index extends Home{
    
    private static $_instance;
    
    public function __construct(){
        $db = GenerateInstance::getInstance('OperateData');
        self::$_instance = $db;
    }
    
    public function index(){
        $list = self::$_instance->getList(array('db'=>'Category','level'=>'1','t'=>1,'cache_name'=>'category'));
        print_r($list);
    }
    
    
    
    
}
