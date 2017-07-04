<?php
namespace app\common\controller;
use app\common\controller\DbOperate;
use think\Validate;
use think\Hook;
use think\Error;

class OperateData extends DbOperate{
    
    //实现插入和更新操作
    public function operate(array $obj){
        //$obj的参数
        if (!empty($obj)){
            //数据库对象
            !empty($obj['db']) ? $dbname = $obj['db'] : Error::appError(1, '未传递数据库对象！');//$this->error();
            //类型新增还是编辑
            !empty($obj['t']) ? $t = 1 : $t = 0;
            //数据数组
            !empty($obj['d']) ? $data = $obj['d'] : Error::appError(2, '新增数值不能为空！');//$this->error('新增的数值不能为空！');
            //验证模型
            !empty($obj['vm']) ? $validateModel = $obj['vm'] : $validateModel = '';
            //条件语句
            !empty($obj['c']) ? $map = $obj['c'] : Error::appError(4, '编辑语句未传条件！');
            //多条数据
            !empty($obj['multi']) ? $multi = 1 : $multi = 0;
            //默认需要写入日志
            !empty($obj['log']) ? $log = 0 : $log = 1;

            $validate = new Validate();
            //验证规则写在基类里
            if (!$validate->check($data,$validateModel)){
                //抛出异常
                $validate->getError();
            }
            
            //初始化db
            $db = db($dbname);
            
            if (!empty($t)){
                
                //编辑状态，分几种情况，默认是id进行关联
                if (is_numeric($map)){
                    $res = $db->where('id',$map)->update($data);
                }else if (is_array($map)){
                    //一种是条件修改
                    $res = $db->where($map)->update($data);
                }
                 
            }else{
                if ($multi){
                    //多条新增,返回新增条数
                    $res = $db->name($dbname)->insertAll($data);
                }else{
                    //单条新增
                    $res = $db->name($dbname)->insert($data);
                }
            }
            
            //写入日志及返回结果
            if ($res){
                
                 $logType = !empty($t) ? '编辑' : '新增';
                
                //写日志
                if (!empty($log)){
                    $content = array(
                        'number'=>$res,
                        'dbname'=>$dbname,
                        'type'=>$logType,
                        //扩展更多参数
                    );
                    
                    Hook::exec('app\\home\\behavior\\Behaviour','addLog',$content);
                } 
                
                //编辑直接返回结果
                if (!empty($t)){
                    return $res;
                }
                
                //新增判断
                return !empty($multi) ? $res : $db->getLastInsID();
                    
            }else{
                return 0;
            }
               
                
        }else{
            Error::appError(3, '传递对象参数有误！');
         }
        
    }
    
    //实现删除
    public function deleteData(array $obj){
        return 456;
    }
    
    //实现多列获取
    /**
     * 
     * {@inheritDoc}
     * @see \app\common\controller\DbOperate::getList()
     */
    public function getList(array $obj){
        //数据库对象
        !empty($obj['db']) ? $dbname = $obj['db'] : Error::appError(1, '未传递数据库对象！');//$this->error();
        //类型新增还是编辑
        !empty($obj['t']) ? $type = $obj['t'] : $type = 0;
        !empty($obj['c']) ? $map = $obj['c'] : '';
        switch ($type){
            case 1:
                //递归获取,cache名称
                !empty($obj['cache_name']) ? $cache_name = $obj['cache_name'] : $cache_name = '';
                //遍历深度，如果不传则默认最大传值
                !empty($obj['level']) ? $level = $obj['level'] : '';                
                if (!empty($cache_name)){
                    if (empty(cache($cache_name))){
                        //获取缓存
                        $tmplist = self::_getRecursiveInfoTree($dbname,0,$level);
                        $cache = serialize($tmplist);
                        //写入缓存
                        cache($cache_name,$cache);
                        return $tmplist;
                    }else{
                        //从缓存中取
                        $list = unserialize(cache($cache_name));
                    }
                }else{
                    $list = self::_getRecursiveInfoTree($dbname,0,$level);
                }
                
                break;
            case 2:
                //扩展行为
                break;
            default:
                $list = self::_getModelList($obj);               
        }
                
        return $list;
    }
    
    
    private static function _getRecursiveInfoTree($dbname,$pid,$level){
        //树状图
        $list = self::_createTree($dbname,0,$level);
        //id映射
        $arr = self::_idReflection($dbname,0,$level);
        
        $list['id_array'] = $arr;
        return $list;
    }
        
    
    //id映射表
    private static function _idReflection($dbname,$pid,$level){
        static $reflection = array();
        static $id = array();
        $arr = array();
        //取得id
        $list = self::_getModelList(array('db'=>$dbname,'c'=>array('parent_id'=>$pid),'f'=>'id,parent_id'));
        foreach ($list as $v){
            $id[$level][] = $v['id'];
            if ($v['parent_id'] != 0){
                $reflection[$level][$v['id']] = $v['parent_id'];
            }
            self::_idReflection($dbname, $v['id'], $level+1);
        }
        
        $arr['id_reflection'] = $reflection;
        $arr['id'] = $id;
        return $arr;
    }
    
    
    
    
    /**
     * 
     * @param $pid              父id数组初始为0
     * @param unknown $level    深度，默认为3
     * @return void|unknown[]
     */
    private static function _createTree($dbname,$pid=0,$level){
        $id = array();
        $list = self::_getModelList(array('db'=>$dbname,'c'=>array('parent_id'=>$pid)));
        $arr=array();
        $idreflection = array();
        
        if (!empty($list)){
            foreach ($list as $k=>$v){
                //$id[$level][] = $v['id'];
                $idreflection[$level][$v['id']] = $v['parent_id'];
                if ($v['parent_id'] == 0){
                    $arr['child'][$v['id']] = $v;
                    $arr['child'][$v['id']]['child'] = self::_createTree($dbname, $v['id'],$level+1);
                }else{
                    $arr[$v['id']] = $v;
                    $arr[$v['id']]['child'] = self::_createTree($dbname, $v['id'],$level+1);
                }
                 
            }
        }

        return $arr;
    }
    
    
    
    
    
    /**
     * 获取全部信息
     * @param unknown $model
     * @param unknown $field
     * @param unknown $map
     * @param unknown $page
     * @param unknown $limit
     * @param unknown $order
     */
    private static function _getModelList($o){
        
        !empty($o['o']) ? $order = $o['o'] : $order = '';
        !empty($o['p']) ? $page = $o['p'] : $page = '';
        !empty($o['f']) ? $field = $o['f'] : $field ='*';
        !empty($o['c']) ? $map = $o['c'] : $map = '';
        !empty($o['db']) ? $name = $o['db'] : Error::appError(1, '模型不得为空！');
        //$limit如果存在
        if (!empty($o['l'])){
            $limit = $o['l'];
        }else{
            if (!empty($o['p'])){
                $limit = $page;
            }else{
                $limit = '';
            }
        }
        
        $model=db($name);
        
        $total = $model->where($map)->count();
        
        $list = $model->field($field)
                      ->where($map)
                      ->limit($limit)
                      ->order($order)
                      ->select();
        return $list;
    }
    
    
    //实现单一获取
    public function getSingle(array $obj){
        //数据库对象
        !empty($obj['db']) ? $dbname = $obj['db'] : Error::appError(1, '未传递数据库对象！');
        //条件语句
        !empty($obj['id']) ? $id = $obj['c'] : Error::appError(2, '编辑语句未传条件！');
        //filed域
        !empty($obj['f']) ? $field = $obj['f'] : $field = '*';
        $db = db($obj['dbname']);
        return $info =$db->where('id',$id)->find();
    }
    
    //获取关联
    public function getRelation(array $obj){
        
    }
    
    //获取tree
    public function getTree(array $obj){
        
    }
}