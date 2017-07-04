<?php
namespace app\common\controller;

abstract class Factory{
   //数据库操作 
   abstract public static function getInstance($string);
}