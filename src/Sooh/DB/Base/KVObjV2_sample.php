<?php
namespace Sooh\DB\Base;

/**
 * Description of KVObjV2_sample
 *
 * @author wang.ning
 */
class KVObjV2_sample extends \Sooh\DB\Base\KVObjV2{
	/**
	 * 这里换上实际的类名，便于编辑器正确定位类从而提供真正的方法
	 * @return KVObjV2_sample
	 */
	public static function getCopy($pkey) {
		return parent::getCopy($pkey);
	}
	/**
	 * 各种需要的初始化
	 * @param type $pkey
	 * @return type
	 */
	protected function initPkey($pkey) {
		$ret = parent::initPkey($pkey);
		if(empty($this->tbFormat)){
			$this->tbFormat=[
				'{db}.tb_user_{id}',		//硬盘的表
				'{db}.tb_user_cache_{id}',  //缓存的表，可选
			];
			//$this->fieldName_verid='iRecordVerID'; //更改默认的序列锁字段名
			//$this->fieldName_lockmsg='sLockData';  //更改默认的乐观锁字段名
			//$this->cacheWhenVerIDIs=1;   //开启读缓存表
			//$this->numToSplit=1;         //更改默认分几张表，慎用，尽量用配置的方式 
			//$this->objIdentifier = 'newId';//更改默认的配置里的识别标识

			//$this->fieldsDatetime=['register_time'];//指出数据表中时间类型的字段
			//$this->fieldAutoInc='autoid';//如果有自增字段，指出字段名
			//$this->sortDefineForPage=['field1name'=>'sort','field2name'=>'sort'];//分页获取记录时的主键以及排序依据，目前最多支持2个主键
		}
		return $ret;
	}
//	//如果需要进一步改表名，比如带上日期
//	public function tbname($cache=false){
//		return str_replace('{ymd}', date('Ymd'), parent::tbname($cache));
//	}
}
