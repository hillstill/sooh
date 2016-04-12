<?php
namespace Sooh\DB\Base;

/**
 * KVObj 第2版，主要改造的是配置方面的
 *
 * @author wang.ning
 */
class KVObjV2 extends KVObjV2PKey{
	const onAfterLoaded='onAfterLoad';
	const onBeforeSave ='onBeforeSave';
	const onAfterSave = 'onAfterSave';
	protected $listener=array();//上述几个更新前后的几个监听者回调函数队列
	protected $fieldName_verid='iRecordVerID';//数字序列锁字段
	protected $fieldName_lockmsg='sLockData';//锁定用字段，建议100字节长的字符串(默认'')，54个字节的基本长度，剩下的给lock的说明
	
	protected $chged=array();//记录本次实际更新了哪些字段
	protected $fieldsDatetime=array();//记录需要进行转换的时间型字段
	
	protected $r=array();//数据表字段集合
	protected $loads=null;//加载数据时使用的参数设置
	protected $cacheWhenVerIDIs=0;//是否启用内存表
	protected $fieldAutoInc=null;//如果有自增字段，指出字段名
	/**
	 * 当前是否已被锁定
	 * @var \Sooh\DB\Base\KVObjV2Lock
	 */
	protected $lock=null;
	/**
	 * 传递自定义的数据用的
	 * @var mixed 
	 */
	public $customData;

	/**
	 * 如果没加载过，从数据库加载pkey对应的数据，
	 * （没有严格测试过加载部分数据的情况，至少，启用cache的情况下一定要用*）
	 * @param type $fields
	 * @return array | null 成功加载返回pkey数组
	 */
	public function load($fields='*')
	{
		if($this->exists()===false){
			$this->loads=$fields;
			return $this->reload();
		}else{
			return $this->pkey;
		}
	}
	/**
	 * 强制重新加载pkey对应的数据
	 * 发现json数据还原成array
	 * @return pkey | null 成功加载返回pkey数组
	 */
	public function reload()
	{
		if(!empty($this->pkey) && !empty($this->loads)){
			//deal with cache
			if($this->cacheWhenVerIDIs){
				$tbCache=$this->tbname(true);
				$this->r = $this->db(true)->kvoLoad($tbCache, $this->loads,$this->pkey);
				if(empty($this->r)){
					$this->r = $this->db()->kvoLoad($this->tbname(), $this->loads,$this->pkey);
					if(!empty($this->r)){
						$this->db(true)->kvoNew($tbCache, $this->r, $this->pkey,array($this->fieldName_verid=>$this->r[$this->fieldName_verid]),$this->fieldAutoInc);
					}else{
						$this->r=array();
					}
				}
			}else{
				$this->r = $this->db()->kvoLoad($this->tbname(), $this->loads,$this->pkey);
			}

			if(!empty($this->r)){
				//try jsondecode
				foreach($this->r as $k=>$v){
					if(isset($this->fieldsSimple[$k])){
						continue;
					}elseif($v!==null && !is_scalar($v)){
						if(!isset($this->fieldsDatetime[$k]) &&is_a($v,'Datetime')){
							$this->fieldsDatetime[$k]=$k;
						}
					}elseif(is_string($v)){
						if(substr($v,0,1)==='{' && substr($v,-1)==='}'){
							$this->r[$k] = json_decode($v,true);
							if(empty($this->r[$k])){
								$this->r[$k]=$v;
							}
						}elseif(substr($v,0,1)==='[' && substr($v,-1)===']'){
							if($v=='[]'){
								$this->r[$k] = array();
							}else{
								$this->r[$k] = json_decode($v,true);
								if(empty($this->r[$k])){
									$this->r[$k]=$v;
								}
							}
						}
					}
				}
				foreach($this->fieldsDatetime as $k){
					$this->r[$k] = $this->r[$k]->getTimestamp();
				}

				$this->chged=array();
				if(!empty($this->r[$this->fieldName_lockmsg])){
					$this->lock = \Sooh\DB\Base\KVObjV2Lock::facotry($this->r[$this->fieldName_lockmsg]);
				}
				$this->callbackOn(self::onAfterLoaded);
				return $this->pkey;
			}
		}else{
			throw new \ErrorException('known what to load(pkey, fields all empty)');
		}
		return null;
	}
	/**
	 * 获得用于sql的指定的字段的字符串化后的数组
	 * @return array
	 */
	protected function fieldsForSqlUpds($fields)
	{
		$tmp = array();
		foreach($fields as $k){
			if(!is_array($this->r[$k])){
				$tmp[$k]=$this->r[$k];
			}else {
				$tmp[$k] = json_encode($this->r[$k]);
			}
		}
		foreach($this->fieldsDatetime as $k){
			if(isset($tmp[$k])){
				$tmp[$k] = date('Y-m-d H:i:s',$tmp[$k]);
			}
		}
		$tmp[$this->fieldName_verid] = \Sooh\DB\Base\SQLDefine::nextCircledInt($this->r[$this->fieldName_verid]);
		return $tmp;
	}
	/**
	 * 当前记录是否已经锁定
	 * @return bool
	 */
	public function isLocked()
	{
	    return $this->lock===null?false:true;
	}
	/**
	 * 锁定一条记录(TODO: 分散设计后，应该没有很多的冲突几率，考虑加个冲突日志并酌情报警)
	 * @param string $msg msg describe the reason
	 * @param int $secExpire default 3year
	 * @return boolean 
	 * @throws ErrorException when record is locked already
	 */
	public function lock($msg,$secExpire=94608000)
	{
		$dt = \Sooh\Base\Time::getInstance();
		if($this->lock!==null){
			error_log('locked already:'.  get_called_class().' '.  json_encode($this->pkey));
			return false;
		}else{
			$this->lock = new \Sooh\DB\Base\KVObjV2Lock;
			$this->lock->create = $dt->timestamp();
			$this->lock->expire = $this->lock->create + $secExpire;
			$this->lock->msg = $msg;
			$this->lock->ip = \Sooh\Base\Tools::remoteIP();
			$this->lock->lockedByThisProcess=true;
			$dbDisk = $this->db();
			$tbDisk = $this->tbname();
			if($this->cacheWhenVerIDIs){
				$dbCache = $this->db(true);
				$tbCache = $this->tbname(true);
			}
			$where = $this->pkey;
			$where[$this->fieldName_verid] = $this->r[$this->fieldName_verid];
			$nextId = \Sooh\DB\Base\SQLDefine::nextCircledInt($this->r[$this->fieldName_verid]);
			$tmp = $this->lock->toString();
			if($this->cacheWhenVerIDIs==0){
				$ret = $dbDisk->updRecords($tbDisk, 
					array(	$this->fieldName_verid=>$nextId,
							$this->fieldName_lockmsg=>$tmp), 
					$where);
				$locked = $ret==1;
			}elseif($this->cacheWhenVerIDIs==1){
				$ret = $dbCache->updRecords($tbCache, 
					array(	$this->fieldName_verid=>$nextId,
							$this->fieldName_lockmsg=>$tmp), 
					$where);
				$locked = $ret==1;
				if($locked){
					$dbDisk->updRecords($tbDisk, 
					array(	$this->fieldName_verid=>$nextId,
							$this->fieldName_lockmsg=>$tmp), 
					$where);
				}
			}else{
				$ret = $dbCache->updRecords($tbCache, 
					array(	$this->fieldName_verid=>$nextId,
							$this->fieldName_lockmsg=>$tmp), 
					$where);
				$locked = $ret==1;
			}

			if($locked){
				$this->r[$this->fieldName_verid] = $nextId;
				$this->r[$this->fieldName_lockmsg]=$tmp;

				return true;
			}else{
				error_log('locked failed:'.  implode("\n", \Sooh\DB\Broker::lastCmd(false)));
				return false;
			}
		}
	}
	/**
	 * 解锁记录
	 * @param boolean $noMatterByWho 是否无条件解锁（哪怕被其他人锁定）
	 */
	public function unlock($noMatterByWho=false)
	{
		$where = $this->pkey;
		if($noMatterByWho===false){
			$where[$this->fieldName_lockmsg]=$this->lock->toString();
		}
		$nextId = \Sooh\DB\Base\SQLDefine::nextCircledInt($this->r[$this->fieldName_verid]);
		$dbDisk = $this->db();
		$tbDisk = $this->tbname();
		if($this->cacheWhenVerIDIs){
			$dbCache = $this->db(true);
			$tbCache = $this->tbname(true);
		}
		if($this->cacheWhenVerIDIs==0){
			$ret = $dbDisk->updRecords($tbDisk, 
				array(	$this->fieldName_verid=>$nextId,
						$this->fieldName_lockmsg=>''), 
				$where);
			$unlocked = $ret==1;
		}elseif($this->cacheWhenVerIDIs==1){
			$ret = $dbCache->updRecords($tbCache, 
				array(	$this->fieldName_verid=>$nextId,
						$this->fieldName_lockmsg=>''), 
				$where);
			$unlocked = $ret==1;
			if($unlocked){
				$dbDisk->updRecords($tbDisk, 
				array(	$this->fieldName_verid=>$nextId,
						$this->fieldName_lockmsg=>''), 
				$where);
			}
		}else{
			$ret = $dbCache->updRecords($tbCache, 
				array(	$this->fieldName_verid=>$nextId,
						$this->fieldName_lockmsg=>''), 
				$where);
			$unlocked = $ret==1;
		}

		if($unlocked){
			$this->r[$this->fieldName_verid] = $nextId;
			$this->r[$this->fieldName_lockmsg]='';

			return true;
		}else{
			error_log('unlock failed:'.  implode("\n", \Sooh\DB\Broker::lastCmd(false)));
			return false;
		}
	}
	/**
	 * 检查某个字段是否存在  或  对象是否成功加载数据
	 * @param type $field
	 * @return type
	 */
	public function exists($field=null)
	{
		if($field!==null){
			return isset($this->r[$field]);
		}else{
			return !empty($this->r);
		}
	}
	/**
	 * dump record
	 * @return array
	 */
	public function dump()
	{
		return $this->r;
	}
	/**
	 * 获取某个字段的值
	 * @param string $field 字段名
	 * @param boolean $nullAccepted 当取得的值是null的时候，是否应该丢出异常
	 */	
	public function getField($field,$nullAccepted=false)
	{
		if(!isset($this->r[$field])){
			if($nullAccepted==false){
				$err = new \ErrorException("fieldGet of $field not loaded or is NULL \nwhen request:"
													.$_SERVER["REQUEST_URI"]
											."\n check code of load(cur loaded:"
													.(is_array($this->r)?implode(',',array_keys($this->r)):"NULL")
											.")\npkey=". json_encode($this->pkey));
				error_log($err->getMessage()."\n".$err->getTraceAsString());
				throw $err;
			}else{
				return null;
			}
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
	/**
	 * 设置某个字段的值
	 * @param string $field 字段名
	 * @param mixed $numAdd  增加多少
	 */
	public function incField($field,$numAdd)
	{
		$this->chged[$field]=$field;
		$this->r[$field] += $numAdd;
	}
	/**
	 * 注册一个更新前后的监听事件(跟上个版本比改造了参数顺序)
	 * @param string $evt
	 * @param callback $callback
	 */
	public function registerOn($evt,$callback)
	{
		$this->listener[$evt][]=$callback;
	}
	/**
	 * 调用相关监听回调
	 * @param callback $type
	 */
	protected function callbackOn($type)
	{
		if(!empty($this->listener[$type])){
			foreach($this->listener[$type] as $listener){
				if(is_array($listener)) {
					call_user_func ($listener, $this);
				}elseif(is_string($listener)){
					call_user_func ($listener, $this);
				}else {
					$listener($this);
				}
			}
		}
	}
	/**
	 * 删除数据库中对应的记录(verid始终是忽略的)
	 * @param boolean $skipLock 是否忽略锁定标志
	 */
	public function delete($skipLock=false)
	{
		try{
			$where =  $this->pkey;
			if($skipLock==false){
				$where[$this->fieldName_lockmsg]='';
			}
			if($this->cacheWhenVerIDIs){
				$this->db(true)->kvoDelete($this->tbname(true), $where);
			}
			$this->db()->kvoDelete($this->tbname(), $where);
error_log("[KVObjV2 - delete]".  implode("\n", \Sooh\DB\Broker::lastCmd(false)));
			$this->chged=array();
			$this->r=array();
		} catch (\Exception $ex) {
			error_log($ex->getMessage().$ex->getTraceAsString());
		}
	}
	/**
	 * 兼容旧版
	 */
	public function update()
	{
	    return $this->save();
	}
	/**
	 * 保存到数据库（新建或更新）
	 * @return type
	 * @throws \ErrorException
	 */
	function save()
	{
		$retry = 0;
		while($retry<=2){
			try{
				$this->callbackOn(self::onBeforeSave);
				$_ret = $this->trySave();
				$this->callbackOn(self::onAfterSave);
				return $_ret;
			}catch(\ErrorException $e){
				throw $e;
			}
		}
		throw $e;
	}
	/**
	 * 实际保存到数据库的执行代码
	 * @return int
	 * @throws \ErrorException
	 * @throws \Sooh\Base\ErrException 锁定了，又或诸如没什么要更改的确调用了save
	 */
	protected function trySave()
	{
		$tbDisk = $this->tbname();
		$dbDisk = $this->db();
		if($this->cacheWhenVerIDIs){
			$tbCache = $this->tbname(true);
			$dbCache = $this->db(true);
		}else{
			$tbCache = null;
			$dbCache = null;
		}
		$class = get_called_class();
		if(empty($this->chged)){
			throw new \ErrorException($class.':nothing needs to do');
		}
		
		try{
			if($this->lock && !$this->lock->lockedByThisProcess) {
				throw new \ErrorException("Can not update as record is locked");
			}
			if(!isset($this->r[$this->fieldName_verid])){
				
				$verCurrent = array($this->fieldName_verid=>1);
				$fields = $this->fieldsForSqlUpds($this->chged);
				$pkeyBak = $dbDisk->kvoNew($tbDisk, $fields, $this->pkey,$verCurrent,$this->fieldAutoInc);
				$this->r[$this->fieldName_verid]=1;
				foreach($this->pkey as $k=>$v){
					$this->r[$k]=$v;
				}
				$md5Key0 = md5(json_encode($this->pkey));
				$md5Key2 = md5(json_encode($pkeyBak));
				if($md5Key0 !== $md5Key2){
					unset(self::$_copies[$class][$md5Key0]);
					self::$_copies[$class][$md5Key2] = $this;
					$this->pkey = $pkeyBak;
				}
				if($this->cacheWhenVerIDIs > 0){
					$dbCache->kvoNew($tbCache, $fields, $this->pkey, $verCurrent,$this->fieldAutoInc);
				}
				return 1;
			}else{
				$verCurrent = array($this->fieldName_verid=>$this->r[$this->fieldName_verid]);
				$whereForUpdate = $this->pkey;
				if($this->lock){
					if( !$this->lock->lockedByThisProcess){
						throw new \Sooh\Base\ErrException(\Sooh\Base\ErrException::msgLocked);
					}else{
						$this->setField($this->fieldName_lockmsg, '');
					}
				}
//				else{
//					$whereForUpdate[$this->fieldName_lockmsg]='';
//				}

				if($this->cacheWhenVerIDIs<=1){
					if($dbDisk->kvoFieldSupport()){
						$fields = $this->fieldsForSqlUpds($this->chged);
						$_ret = $dbDisk->kvoUpdate($tbDisk, $fields, $whereForUpdate, $verCurrent);
						$nextVerId = $fields[$this->fieldName_verid];
					}else{
						$fieldsAll=$this->fieldsForSqlUpds(array_keys($this->r));
						$_ret = $dbDisk->kvoUpdate($tbDisk, $this->r, $whereForUpdate, $verCurrent);
						$nextVerId = $fieldsAll[$this->fieldName_verid];
					}
                    if($this->cacheWhenVerIDIs){
						if($dbCache->kvoFieldSupport()){
							if(empty($fields)){
								$fields = $this->fieldsForSqlUpds($this->chged);
								$nextVerId = $fields[$this->fieldName_verid];
							}
							$dbCache->kvoUpdate($tbCache, $fields, $whereForUpdate, $verCurrent,true);
						}else{
							if(empty($fieldsAll)){
								$fieldsAll=$this->fieldsForSqlUpds(array_keys($this->r));
								$nextVerId = $fieldsAll[$this->fieldName_verid];
							}
							$dbCache->kvoUpdate($tbCache, $fieldsAll, $whereForUpdate, $verCurrent,true);
						}
                    }
                    $this->r[$this->fieldName_verid]= $nextVerId;
                }else{
					if($dbCache->kvoFieldSupport()){
					    $fields = $this->fieldsForSqlUpds($this->chged);
					    $nextVerId = $fields[$this->fieldName_verid];
						$_ret=$dbCache->kvoUpdate($tbCache, $fields, $whereForUpdate, $verCurrent);
					}else{
					    $fieldsAll=$this->fieldsForSqlUpds(array_keys($this->r));
					    $nextVerId = $fieldsAll[$this->fieldName_verid];
						$_ret=$dbCache->kvoUpdate($tbCache, $fieldsAll, $whereForUpdate, $verCurrent);
					}
					if($this->r[$this->fieldName_verid]%$this->cacheWhenVerIDIs==0){
						try{
						    if($dbDisk->kvoFieldSupport()){
						        if(empty($fields)){
						            $fields = $this->fieldsForSqlUpds($this->chged);
					                $nextVerId = $fields[$this->fieldName_verid];
						        }
						        $_ret = $dbDisk->kvoUpdate($tbDisk, $fields, $whereForUpdate, $verCurrent,true);
						    }else{
						        if(empty($fieldsAll)){
						            $fieldsAll=$this->fieldsForSqlUpds(array_keys($this->r));
					                $nextVerId = $fieldsAll[$this->fieldName_verid];
						        }
						        $_ret = $dbDisk->kvoUpdate($tbDisk, $fieldsAll, $whereForUpdate, $verCurrent,true);
						    }
						}catch(\ErrorException $e){
							error_log("fatal error: $class : update disk failed after cache updated");
							throw $e;
						}
					}
					$this->r[$this->fieldName_verid]= $nextVerId;
				}
			}
			$this->lock=null;
error_log("[KVObjV2 - save]".  implode("\n", \Sooh\DB\Broker::lastCmd(false)));
			return $_ret;
		}catch(\ErrorException $e){//key duplicate -> add failed
			throw $e;
		}

	}
}
