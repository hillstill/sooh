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
		if($this->rpc!==null){
			return $this->rpc->initArgs(array('sessionId'=>$sessionId))->send(__FUNCTION__);
		}else{
			$obj = \Sooh\DB\Cases\SessionStorage::getCopy($sessionId);
			$obj->load();
			if(!$obj->exists()){
				return array('data'=>array(),'trans'=>array());
			}else{
				$data = $obj->getSessionData();
				$this->md5Last = md5(json_encode($data));
				return array('data'=> $data,'trans'=>$obj->getArrayTrans());
			}
		}
	}
	protected $md5Last = '';
	public function update($sessionId,$sessData,$trans)
	{
		if($this->rpc!==null){
			return $this->rpc->initArgs(array('sessionId'=>$sessionId,'sessData'=>$sessData,'trans'=>$trans))->send(__FUNCTION__);
		}else{
			try{
				$err= new \Sooh\Base\ErrException('');
				if (!empty($trans['sessionId']) && $trans['sessionId']!=$sessionId){
					\Sooh\Base\Log\Data::error("ERROR on update session with sessionId dismatch: $sessionId",array('trans'=>$trans,'data'=>$sessData));
					return 'error';
				}else{
					$obj = \Sooh\DB\Cases\SessionStorage::getCopy($sessionId);
					//unset($trans['sessionId']);
					if(!empty($trans['iRecordVerID'])){
						$obj->setVerId($trans['iRecordVerID']);
					}
					//unset($trans['iRecordVerID']);
					ksort($sessData);
					$md5 = md5(json_encode($sessData));
//					error_log('[TRACE-session '.$_COOKIE['SoohSessId'].' storage] md5_'.($md5!=$this->md5Last?'NE':'EQ').' old='.$this->md5Last.' vs new '.$md5);
					if($md5!=$this->md5Last){
						$obj->setSessionData($sessData);
						$obj->setField('lastUpdate', time());
						$obj->update();
					}
					//error_log(">>>>>>>>>>>session>>>$sessionId\n".  var_export($sessData,true)."\n".  var_export($trans,true));
					return 'done';
				}
			}  catch (\Exception $e){
				\Sooh\Base\Log\Data::error('errorOnUpdateSession',$e);
				return 'error:'.$e->getMessage();
			}
		}
	}
}
