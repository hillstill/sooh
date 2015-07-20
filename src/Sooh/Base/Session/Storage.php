<?php
namespace Sooh\Base\Session;
/**
 * Description of Session
 *
 * @author Simon Wang <hillstill_simon@163.com>
 */
class Storage {
	protected static $_instance=null;
	public static function setStorageIni($dbGrpId='default',$numSplit=2)
	{
		\Sooh\DB\Cases\SessionStorage::$__id_in_dbByObj=$dbGrpId;
		\Sooh\DB\Cases\SessionStorage::$__nSplitedBy=$numSplit;
	}

	/**
	 * 
	 * @param \Sooh\Base\Rpc\Base $rpcOnNew
	 * @return Storage
	 */
	public static function getInstance($rpcOnNew=null)
	{
		if(self::$_instance===null){
			$c = get_called_class();
			self::$_instance = new $c;
			self::$_instance->rpc = $rpcOnNew;
		}
		return self::$_instance;
	}
	/**
	 *
	 * @var \Sooh\Base\Rpc\Base 
	 */	
	protected $rpc=null;
	
	/**
	 * 读出数据并返回：array(
	 *				data=>array(__eXpIrE__=>array(k2=>expire2),k1=>v1,k2=>v2), 
	 *				trans=array(...)
	 *			)
	 * @param type $sessionId
	 * @return array array(data=>array(__eXpIrE__=>array(k2=>expire2),k1=>v1,k2=>v2), trans=array(...))
	 */
	public function load($sessionId)
	{
		if($this->rpc===null){
			$obj = \Sooh\DB\Cases\SessionStorage::getCopy($sessionId);
			$obj->load();
			if(!$obj->exists()){
				return array('data'=>array(),'trans'=>array());
			}else{
				return array('data'=> $obj->getSessionData(),'trans'=>$obj->getArrayTrans());
			}
		}else{
			$class = explode('\\',get_called_class());
			$class = array_pop($class);
			return $this->rpc->call($class.'/'.__FUNCTION__, array($sessionId));
		}
	}
	
	public function update($sessionId,$sessData,$trans)
	{
		if($this->rpc===null){
			try{
				if (!empty($trans['sessionId']) && $trans['sessionId']!=$sessionId){
					\Sooh\Base\Log\Data::error("ERROR on update session with sessionId dismatch: $sessionId",array('trans'=>$trans,'data'=>$sessData));
					return false;
				}else{
					$obj = \Sooh\DB\Cases\SessionStorage::getCopy($sessionId);
					//unset($trans['sessionId']);
					if(!empty($trans['iRecordVerID'])){
						$obj->setVerId($trans['iRecordVerID']);
					}
					//unset($trans['iRecordVerID']);
					$obj->setSessionData($sessData);
					$obj->update();
					return true;
				}
			}  catch (\Exception $e){
				\Sooh\Base\Log\Data::error('errorOnUpdateSession',$e);
				return false;
			}
		}else{
			$class = explode('\\',get_called_class());
			$class = array_pop($class);
			return $this->rpc->call($class.'/'.__FUNCTION__, array($sessionId,$sessData,$trans));
		}
	}
}
