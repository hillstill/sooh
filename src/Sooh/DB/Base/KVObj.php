<?php
namespace Sooh\DB\Base;

//use \Sooh\DB\Base\Error as sooh_dbField;
use \Sooh\Base\Trace as sooh_trace;
use \Sooh\DB\Broker as sooh_broker;
use \Sooh\Base\Ini as sooh_ini;
use \Sooh\DB\Broker as sooh_dbBroker;
/**
	public function kvoFieldSupport();//true or false: if must load all
	public function kvoLoad($obj,$fields,$arrPkey);
	public function kvoUpdate($obj,$fields,$arrPkey);
	public function kvoNew($obj,$fields, $arrPkey);
 */

/**
 * jsonencode for array field
 * 数组字段会调用jsonencde
 * @author Simon Wang <sooh_simon@163.com> 
 */
abstract class KVObj
{
	const onAfterLoaded='onAfterLoad';
	const onBeforeSave ='onBeforeSave';
	const onAfterSave = 'onAfterSave';
	protected $listener=array();
	protected $chged=array();
	protected $pkey;
	protected $tbname;
	protected $r=array();
	protected $loads=null;
	protected $cacheWhenVerIDIs=0;
	protected $fieldName_verid='iRecordVerID';
	protected static $_copies=array();
	
	public static function searchAll($fields,$where,$tableByPkey=null)
	{
		if($tableByPkey!==null){
			if(is_numeric($tableByPkey)){
				$id = $tableByPkey%static::numToSplit();
			}else{
				$id = self::indexForSplit($tableByPkey);
			}
			$tbFullName = null;
			$db = self::getDBAndTbName($tbFullName,$id,null);
		}else{
			$max = static::numToSplit();
			for($id=0;$id<$max;$id++){
				$db = self::getDBAndTbName($tbFullName,$id,null);
			}
			
		}
	}

	/**
	 * @param array $pkey
	 * @return \Sooh\DB\Base\KVObj
	 */	
	public static function getCopy($pkey)
	{
		$class = get_called_class();
		$md5 = md5(json_encode($pkey));
		if(!isset(self::$_copies[$class][$md5])){
			$o = new $class;
			self::$_copies[$class][$md5] = $o->initConstruct()->initPkey($pkey);
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::getCopy('. json_encode($pkey).') called, by new instance '.$md5);
		}else{
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::getCopy('. json_encode($pkey).') called, by exists instance '.$md5);
		}
		return self::$_copies[$class][$md5];
	}
//	public static function query($where)
//	{
//		$db = self::getDB($pkey);
//		$db->dbCurrent(null);
//	}
	public static function freeAll($pkey=null)
	{
		$class = get_called_class();
		if($pkey){
			$md5 = md5(json_encode($pkey));
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::freeAll('.  json_encode($pkey).') call, current has '.  sizeof(self::$_copies[$class]).' instances');
			self::$_copies[$class][$md5]->free(false);
			unset(self::$_copies[$class][$md5]);
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::freeAll('.  json_encode($pkey).') done, current has '.  sizeof(self::$_copies[$class]).' instances');
			if(empty(self::$_copies[$class]))unset(self::$_copies[$class]);
		}else{
			foreach(self::$_copies as $class=>$rs){
				if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::freeAll() start, current '.$class.' has '.  sizeof($rs).' instances');
				foreach($rs as $k=>$o){
					$o->free(false);
					unset($rs[$k]);
					unset(self::$_copies[$class][$k]);
				}
				if(!empty(self::$_copies[$class]))unset(self::$_copies[$class]);
			}
			if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str($class.'::freeAll() done');
		}
		
	}
	public function free($removeGlobal=true)
	{
		if(sooh_trace::needsWrite(__CLASS__))sooh_trace::str(get_called_class().'->free('.($removeGlobal?'needsRemoveGlobal':'').') called');
		$pkey = $this->pkey;
		$this->r=array();
		$this->pkey=null;
		$this->chged=array();
		$this->tbname=null;
		$ks = array_keys($this->listener);
		foreach($ks as $k)unset($this->listener[$k]);
		$this->listener=array();
		if($removeGlobal)
			static::freeAll ($pkey);
	}
	protected static function indexForSplit($pkey)
	{
		if(sizeof($pkey)==1){
			$n = current($pkey);
			$n = substr($n,-2);
			if($n==='0'||$n==='00')return 0;
			$n= $n-0;
			if($n>0)return $n;
		}
		$s = md5(json_encode($pkey));
		$s = substr($s,-3);
		$n = base_convert($s, 16, 10);
		return $n % static::numToSplit();
	}
	/**
	 * 拆分成几个表
	 * @return int
	 */
	protected static function numToSplit(){return 10;}
	/**
	 * 根据拆分id，确认实际的表名
	 * @param int $n
	 * @param bool $isCache 
	 * @return string
	 */
	protected static function splitedTbName($n,$isCache)
	{
		if($isCache)return 'redis_test_'.($n % static::numToSplit());
		else return 'mysql_test_'.($n % static::numToSplit());
	}
	/**
	 * @param array $pkey
	 * @return \Sooh\DB\Interfaces\All
	 */
	protected static function getDBAndTbName(&$tbnameToSet,$pkey,$isCache=false)
	{
		$splitedId = self::indexForSplit($pkey);
		$ret = self::getDBAndTbNameById($tbnameToSet,$splitedId, $isCache);
		if($ret===null)
			throw new \ErrorException('can NOT find dbConf for '.get_called_class().($isCache?"Cache":'').':'.  json_encode($pkey));
		return $ret;
	}
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return get_called_class().($isCache?'Cache':'');
	}
	protected static function getDBAndTbNameById(&$tbnameToSet,$splitedId,$isCache=false)
	{
		$dbByObj = static::idFor_dbByObj_InConf($isCache);
		$ini = sooh_ini::getInstance();
		$dbId = $ini->get('dbByObj.'.$dbByObj);
		if(is_array($dbId)){
			$i = $splitedId % sizeof($dbId);
			$conf = $ini->get('dbConf.'.$dbId[$i]);
		}elseif(!empty($dbId)){
			$conf = $ini->get('dbConf.'.$dbId);
		}else{
			$conf = $ini->get('dbConf.'.$ini->get('dbByObj.default'));
		}
		if(empty($conf))return null;
	
		$db = sooh_dbBroker::getInstance($conf,$dbByObj);
		$tbnameToSet = $db->dbCurrent(null).'.'.static::splitedTbName($splitedId,$isCache);
		return $db;
	}
	protected function initConstruct($cacheSetting=0,$fieldVer='iRecordVerID')
	{
		$this->cacheWhenVerIDIs = $cacheSetting;
		$this->fieldName_verid = $fieldVer;
		return $this;
	}
	/**
	 * 
	 * @param array $pkey
	 * @return \Sooh\DB\Base\KVObj
	 */
	protected function initPkey($pkey)
	{
		$this->pkey=$pkey;
		return $this;
	}
	/**
	 * 
	 * @param type $fields
	 * @return pkey | null
	 */
	public function load($fields='*')//---------------------------------------------load 指定字段的情况
	{
		if($this->cacheWhenVerIDIs)$fields = '*';//-----------------------------有 cache
		$this->loads=$fields;
		return $this->reload();
	}
	public function tbname()
	{
		if($this->tbname!=null)return $this->tbname;
		else static::getDBAndTbName($this->tbname, $this->pkey,false);
		return $this->tbname;
	}
	/**
	 * 
	 * @return \Sooh\DB\Interfaces\All
	 */
	public function db()
	{
		return static::getDBAndTbName($this->tbname, $this->pkey,false);
	}
	
	/**
	 * 
	 * @return pkey | null
	 */
	public function reload()
	{
		$class = get_called_class();
		if(sooh_trace::needsWrite($class))sooh_trace::str($class.'->'.__FUNCTION__.':iniCacheWhenVerIDIs='.$this->cacheWhenVerIDIs);
		if(!empty($this->pkey) && !empty($this->loads)){
			//deal with cache
			if($this->cacheWhenVerIDIs){
				$tbCache=null;
				$dbCache = static::getDBAndTbName($tbCache, $this->pkey,true);
				$this->r = $dbCache->kvoLoad($tbCache, $this->loads,$this->pkey);
				if(empty($this->r)){
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' cache miss try disk:'.  sooh_broker::lastCmd());
					$db = static::getDBAndTbName($this->tbname, $this->pkey,false);
					$this->r = $db->kvoLoad($this->tbname, '*',$this->pkey);
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' load from disk:'.sooh_broker::lastCmd());
					if(!empty($this->r)){
						$dbCache->kvoNew($tbCache, $this->r, $this->pkey,array($this->fieldName_verid=>$this->r[$this->fieldName_verid]));
						if(sooh_trace::needsWrite($class))sooh_trace::str($class.' and update cache:'.sooh_broker::lastCmd());
					}else{
						$this->r=array();
						if(sooh_trace::needsWrite($class))sooh_trace::str($class.' record not exists for ('.  json_encode($this->pkey).'):'.sooh_broker::lastCmd());
					}
				}else{
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' load from cache:'.sooh_broker::lastCmd());
				}
			}else{
				$db = static::getDBAndTbName($this->tbname, $this->pkey,false);
				$this->r = $db->kvoLoad($this->tbname, $this->loads,$this->pkey);
				if(sooh_trace::needsWrite($class))sooh_trace::str($class.' load from disk:'.sooh_broker::lastCmd());
			}

			if(!empty($this->r)){
				//try jsondecode
				foreach($this->r as $k=>$v){
					if(isset($this->fieldsSimple[$k]))continue;
					elseif($v!==null && !is_scalar($v)){
						if(!isset($this->fieldsDatetime[$k]) &&is_a($v,'Datetime')){
							$this->fieldsDatetime[$k]=$this->fieldsDatetime[$k];
						}
					}elseif(is_string($v)){
						if($v[0]=='{' && substr($v,-1)=='}'){
							$this->r[$k] = json_decode($v,true);
							if(empty($this->r[$k]))$this->r[$k]=$v;
						}elseif($v[0]=='[' && substr($v,-1)==']'){
							if($v=='[]')$this->r[$k] = array();
							else{
								$this->r[$k] = json_decode($v,true);
								if(empty($this->r[$k]))$this->r[$k]=$v;
							}
						}
					}
				}
				\Sooh\DB\Conv\Datetime::loop_to_timestamp($this->fieldsDatetime, $this->r, 'row');

				$this->chged=array();
				$this->callbackOn(self::onAfterLoaded);
				return $this->pkey;
			}
		}else{
			throw new \ErrorException('known what to load(pkey, fields all empty)');
		}
		return null;
	}
	protected $fieldsDatetime=array();
	protected $fieldsSimple=array();
	/**
	 * 获取某个字段的值
	 * @param string $field 字段名
	 * @param boolean $nullAccepted 当取得的值是null的时候，是否应该丢出异常
	 */	
	public function getField($field,$nullAccepted=false)
	{
		if(!isset($this->r[$field])){
			if($nullAccepted==false){
				$err = new \ErrorException("fieldGet of $field not loaded \nwhen request:"
													.$_SERVER["REQUEST_URI"]
											."\n check code of load(cur loaded:"
													.(is_array($this->r)?implode(',',array_keys($this->r)):"NULL")
											.")\npkey=". json_encode($this->pkey));
				sooh_trace::exception($err);
				throw $err;
			}else return null;
		}
		return $this->r[$field];
	}
	/**
	 * 设置某个字段的值
	 * @param string $field 字段名
	 * @param mixed $val  值
	 */
	public function setField($field,$val)//--------------------val 是 FIeld + 的情况
	{
		$this->chged[$field]=$field;
		$this->r[$field]=$val;
	}
	
	protected function fieldsUpds()
	{
		$tmp = array();
		foreach($this->chged as $k){
			if(!is_array($this->r[$k])) $tmp[$k]=$this->r[$k];
			else $tmp[$k] = json_encode($this->r[$k]);
		}
		\Sooh\DB\Conv\Datetime::loop_from_timestamp($this->fieldsDatetime, $tmp, 'row');
		return $tmp;
	}

	protected function trySave()
	{
		$tbCache=null;
		$db = static::getDBAndTbName($this->tbname, $this->pkey,false);
		if($this->cacheWhenVerIDIs)
			$dbCache = static::getDBAndTbName($tbCache, $this->pkey,true);
		$class = get_called_class();
		if(empty($this->chged)){
			$err = new \ErrorException(get_called_class().':nothing needs to do');
			sooh_trace::exception($err);
			throw new $err;
		}
		
		try{
			if(!isset($this->r[$this->fieldName_verid])){
				
				$verCurrent = array($this->fieldName_verid=>0);
				$pkeyBak=$this->pkey;
				$this->pkey = $db->kvoNew($this->tbname, $this->fieldsUpds(), $this->pkey,$verCurrent);
				$this->r[$this->fieldName_verid]=0;
				foreach($this->pkey as $k=>$v)$this->r[$k]=$v;
				if(sooh_trace::needsWrite($class))sooh_trace::str($class.' createNew '.sooh_broker::lastCmd());
//				if($this->cacheWhenVerIDIs){
//					$dbCache->kvoNew($tbCache, $this->r, $this->pkey,$verCurrent);
//					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' update cache :'.sooh_broker::lastCmd());
//				}
				if(json_encode($pkeyBak)!=  json_encode($this->pkey)){
					$sOld=json_encode($pkeyBak);
					$md5 = md5($sOld);
					$sNew = json_encode($this->pkey);
					$md5New = md5($sNew);
					unset(self::$_copies[$class][$md5]);
					self::$_copies[$class][$md5New] = $this;
					$class = get_class();
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' createNew with pkey changed, md5 from:'.$md5.'#'.$sOld.' to '.$md5New.'#'.$sNew);
				}
				
			}else{
				$verCurrent = array($this->fieldName_verid=>$this->r[$this->fieldName_verid]);
				if($this->cacheWhenVerIDIs<=1){
					if($db->kvoFieldSupport())
						$db->kvoUpdate($this->tbname, $this->fieldsUpds(), $this->pkey, $verCurrent);
					else
						$db->kvoUpdate($this->tbname, $this->r, $this->pkey, $verCurrent);
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' update disk first:'.sooh_broker::lastCmd());
					$this->r[$this->fieldName_verid]++;
					if($this->cacheWhenVerIDIs){
						if($db->kvoFieldSupport()==true){
							$all = $dbCache->kvoLoad($tbCache, '*', $this->pkey);
						}else $all=$this->r;
						$dbCache->kvoUpdate($tbCache, $all, $this->pkey, $verCurrent,true);
						if(sooh_trace::needsWrite($class))sooh_trace::str($class.' update cache:'.sooh_broker::lastCmd());
					}
				}else{
					if($dbCache->kvoFieldSupport())
						$dbCache->kvoUpdate($tbCache, $this->fieldsUpds(), $this->pkey, $verCurrent);
					else
						$dbCache->kvoUpdate($tbCache, $this->r, $this->pkey, $verCurrent);
					if(sooh_trace::needsWrite($class))sooh_trace::str($class.' update cache['.  current($verCurrent).']:'.sooh_broker::lastCmd());
					$this->r[$this->fieldName_verid]++;
					if($this->r[$this->fieldName_verid]%$this->cacheWhenVerIDIs==0){
						try{
							$all = $dbCache->kvoLoad($tbCache, '*', $this->pkey);
							$db->kvoUpdate($this->tbname, $all, $this->pkey, $verCurrent,true);
							if(sooh_trace::needsWrite($class))sooh_trace::str($class.' update disk:'.sooh_broker::lastCmd());
						}catch(\ErrorException $e){
							error_log("fatal error: $class : update disk failed after cache updated");
							throw $e;
						}
					}
				}
			}
		}catch(\ErrorException $e){//key duplicate -> add failed
			throw $e;
		}

	}
	protected $lastErrCmd;
	function update($callback=null)
	{
		$retry = 0;
		while($retry<=2){
			if($callback!=null){
				if(is_array($callback)){
					$class = $callback[0];
					$func = $callback[1];
					$class->$func($this,$retry);
				}elseif(is_callable($callback)){
					$callback($this,$retry);
				}else throw new \ErrorException('invalid callback given');
			}

			try{
				$this->callbackOn(self::onBeforeSave);
				$this->trySave();
				$this->callbackOn(self::onAfterSave);
				return;
			}catch(\ErrorException $e){
				if($callback!==null){$retry++;$this->reload();}
				else throw $e;
			}
		}
		sooh_trace::str($e->getMessage()."\nDB-CMD:".json_encode($this->lastErrCmd).$e->getTraceAsString());
		throw $e;
	}
	
	public function registerOn($callback,$evt)
	{
		$this->listener[$evt][]=$callback;
	}
	protected function callbackOn($type)
	{
		if(!empty($this->listener[$type])){
			foreach($this->listener[$type] as $listener){
				if(is_array($listener) || is_string($listener))	
					call_user_func ($listener, $this);
				else $listener($this);
			}
		}
	}
	public function dump()
	{
		return $this->r;
	}

}
