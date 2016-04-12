<?php
namespace Sooh\DB\Base;
/**
 * 围绕主键进行分表分库的封装类
 * @author Simon Wang <sooh_simon@163.com> 
 */
class KVObjV2PKey
{
	/**
	 * 主键
	 * @var array 
	 */
	protected $pkey;
	/**
	 * 根据主键算出的分表依据
	 * @var int 
	 */
	protected $pkey_val; 
	/**
	 * 从配置中获取相应配置的时候使用的标识，默认是类名
	 * @var string
	 */
	protected $objIdentifier='';
	/**
	 * 分页获取记录时的主键以及排序依据，目前最多支持2个主键
	 * 格式：['field1name'=>'sort','field2name'=>'sort'];
	 * @var array
	 */
	protected $sortDefineForPage;
	/**
	 * 共分几张表,默认不分表:[硬盘的，cache的]
	 * @var array [硬盘的，cache的]，
	 */
	protected $numToSplit=[1,1];
	/**
	 * 数据库表的格式串，包含两个参数{db}和{id} : [硬盘的，cache的]
	 * @var array  [硬盘的，cache的]
	 */
	protected $tbFormat=[];//['{db}.tb_user_cache_{id}','{db}.tb_user_cache_{id}',]
	/**
	 * 部署在哪个数据库上 [硬盘的，cache的]
	 * @var array [硬盘的，cache的]
	 */
	protected $dbname=[];
	/**
	 * 分布在哪些服务器上[硬盘的，cache的]
	 * @var array [硬盘的，cache的]
	 */
	protected $_allHosts=[];	
	/**
	 * 所有的KVOBJ实例
	 * @var array
	 */
	protected static $_copies=array();

	/**
	 * @param array $pkey
	 * @return \Sooh\DB\Base\KVObjV2
	 */	
	public static function getCopy($pkey)
	{
		$class = get_called_class();
		$md5 = md5(json_encode($pkey));
		if(!isset(self::$_copies[$class][$md5])){
			self::$_copies[$class][$md5] = $o = new $class;
			$tmp = explode('\\', $class);
			$o->objIdentifier = array_pop($tmp);
			$o->initPkey($pkey);
		}
		return self::$_copies[$class][$md5];
	}
	/**
	 * 设置主键
	 * @param array $pkey
	 * @return \Sooh\DB\Base\KVObjV2
	 */
	protected function initPkey($pkey)
	{
		$this->pkey=$pkey;
		$this->pkey_val = $this->calcPkey($pkey);
		$ini = \Sooh\Base\Ini::getInstance();
		
		$tmp = $ini->get('dbByObj.'.$this->objIdentifier);
		if(!is_array($tmp)){
		    $tmp = $ini->get('dbByObj.default');
		    if(!is_array($tmp)){
		        $tmp = [1,'default'];
		    }
		}
		$this->initdb($ini,$tmp, 0);
		
		$tmp = $ini->get('dbByObj.'.$this->objIdentifier.'Cache');
		if(!is_array($tmp)){
		    $tmp = $ini->get('dbByObj.defaultCache');
		    if(!is_array($tmp)){
		        $tmp = [1,'default'];
		    }
		}
		$this->initdb($ini,$tmp, 1);		

		return $this;
	}
	/**
	 * 设置数据库相关配置
	 * @param \Sooh\Base\Ini $ini
	 * @param array $tmp
	 * @param int $disk_cache 0:disk 1:cache
	 */
	protected function initdb($ini,$tmp,$disk_cache)
	{
	    $this->numToSplit[$disk_cache] = $tmp[0];
	    $db = $ini->get('dbConf.'.$tmp[1].'.dbEnums');
	    $this->dbname[$disk_cache] = isset($db[$this->objIdentifier])?$db[$this->objIdentifier]:$db['default'];
	    array_shift($tmp);
	    $this->_allHosts[$disk_cache] = $tmp;
	}

	/**
	 * 当前key对应的完整的数据库表名称
	 * @param boolean $cache 要的是cache表吗
	 * @return string
	 */
	public function tbname($cache=false)
	{
		
		if($cache){
			$id = $this->pkey_val % $this->numToSplit[1];
			return str_replace(['{db}','{id}'], [$this->dbname[1],$id], $this->tbFormat[1]);
		}else{
			$id = $this->pkey_val % $this->numToSplit[0];
			return str_replace(['{db}','{id}'], [$this->dbname[0],$id], $this->tbFormat[0]);
		}
		return $this->tbname;
	}
	/**
	 * 当前key对应的数据库对象
	 * @param boolean $cache 要的是cache表吗 
	 * @return \Sooh\DB\Interfaces\All
	 */
	public function db($cache=false)
	{
		if($cache){
			$id = $this->pkey_val % sizeof($this->_allHosts[1]);
			$dbId = $this->_allHosts[1][$id];
		}else{
			$id = $this->pkey_val % sizeof($this->_allHosts[0]);
			$dbId = $this->_allHosts[0][$id];
		}
    	return \Sooh\DB\Broker::getInstance($dbId);
		
	}
	/**
	 * 根据主键计算出分表依据
	 * @param type $pkey
	 * @return type
	 */
	protected function calcPkey($pkey)
	{
	    if(empty($pkey)){
	        return 0;
	    }
		if(sizeof($pkey)==1){
			$n = current($pkey);
			if(is_numeric($n) && !strpos($n, '.')){
				return $n%10000;
			}
		}
		$s = md5(json_encode($pkey));
		$n1 = base_convert(substr($s,-3), 16, 10);
		$n2 = base_convert(substr($s,-6,3), 16, 10);
		$n = $n2*100+($n1%100);
		return $n%10000;
	}
	/**
	 * 当前实例清理函数
	 */
	protected function freeCopy()
	{
	    
	}
	/**
	 * 释放掉调用类的资源
	 * @param type $pkey
	 */
	public static function freeAll($pkey=null)
	{
		$class = get_called_class();
		if($pkey){
			$md5 = md5(json_encode($pkey));
			self::$_copies[$class][$md5]->freeCopy();
			unset(self::$_copies[$class][$md5]);
			if(empty(self::$_copies[$class])){
				unset(self::$_copies[$class]);
			}
		}else{
			foreach(self::$_copies as $class=>$rs){
				foreach($rs as $k=>$o){
					$o->freeCopy();
					unset($rs[$k]);
					unset(self::$_copies[$class][$k]);
				}
				if(!empty(self::$_copies[$class])){
					unset(self::$_copies[$class]);
				}
			}
		}
	}
	public static function loopGetRecordsCount($where=null)
	{
	    $obj = self::getCopy(null);
	    $tool = new KVObjV2Loop();
	    $tool->where = $where;
	    $obj->loop([$tool,'kvoGetRecordCount']);
	    return array_sum($tool->records);
	}
	/**
	 * 分页获取记录
	 * 
	 * 分表后，查询结果分页很复杂，提供这个函数解决部分情况(需要唯一索引，且最多2个字段)：
	 * 
	 * @param \Sooh\DB\Pager $pager 
	 * @param array $whereOrLastpage array-of-where OR string-of-lastPage
	 * @param string $fields
	 * @return array [lastPage=>'xxxx', records=array()]
	 */
	public static function loopGetRecords($pager,$whereOrLastpage=null,$fields='*')
	{
	    $obj = self::getCopy(null);
	    $tool = new KVObjV2Loop();
	    $tool->sort_field_type = $obj->sortDefineForPage;
	    $tool->pager = $pager;
	    $tool->fields = $fields;
	    $targetPage = $tool->pager->pageid();
	    $pager->init($pager->total,1);
	    $db = $obj->db();
	    $pageStep = $tool->kvoPagePrepare($targetPage,$whereOrLastpage,$db);
	    while($pageStep>0){
	        $tool->records=[];
    	    $obj->loop([$tool,'kvoGetRecordsStd']);
// echo "\n----------\n";
// echo implode("\n",\Sooh\DB\Broker::lastCmd(false));
// echo "\n----------\n";
// foreach($tool->records as $r){
//     echo json_encode($r)."\n";
// }
// echo "---------\n";
    	    $lastPage = $tool->kvoAfterOnePageLoaded($targetPage-$pageStep+1);
// echo "new lastPage: ".json_encode($lastPage)."\n";	    
// echo "---------\n";   
    	    $tool->kvoPagePrepare($targetPage,$lastPage,$db);
    	    $pageStep --;
	    }
	    $pager->init($pager->total,$targetPage);
	    return ['lastPage'=>$lastPage,'records'=>$tool->records];
	}
	/**
	 * 循环遍历用的所有的数据库连接标识和表（不包括cache）
	 * 回调函数接受两个参数：dbBroker和表名
	 * @param type $callback_dbid_fulltbname
	 */
	protected function loop($callback_dbid_fulltbname,$searchInCache=false)
	{
	    $bak = $this->pkey_val;
	    try{
	        $numSplit = $this->numToSplit[0];
	        if(is_callable($callback_dbid_fulltbname)){
	            for($i=0;$i<$numSplit;$i++){
	                $this->pkey_val=$i;
	                $dbid = $this->_allHosts[$searchInCache?1:0][$i%$numSplit];
	                $tbname = $this->tbname($searchInCache);
	                $callback_dbid_fulltbname(\Sooh\DB\Broker::getInstance($dbid),$tbname);
	            }
	        }else{
	            for($i=0;$i<$numSplit;$i++){
	                $this->pkey_val=$i;
	                $dbid = $this->_allHosts[$searchInCache?1:0][$i%$numSplit];
	                $tbname = $this->tbname($searchInCache);
	                call_user_func($callback_dbid_fulltbname, \Sooh\DB\Broker::getInstance($dbid),$tbname);
	            }
	        }
	    } catch (\ErrorException $e){
	        error_log("error found when ".get_called_class().'->loop():'.$e->getMessage()."\n".$e->getTraceAsString());
	    }
	    $this->pkey_val = $bak;
	}	
}
