<?php
namespace app\common\controller;
//抽象类提供方法，需要载入DB和controller，保存控制器或者数据库实例

abstract class DbOperate{
    //实现插入和更新操作
    abstract public function operate(array $obj);
    //实现删除
    abstract public function deleteData(array $obj);
    //实现获取
    abstract public function getList(array $obj);
    //实现单一获取
    abstract public function getSingle(array $obj);
    //获取权限
    abstract public function getRelation(array $obj);
    //获取tree
    abstract public function getTree(array $obj);
}
