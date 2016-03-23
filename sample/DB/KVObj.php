<?php
//kv-obj 的使用方式
$GLOBALS['CONF']['dbConf']=array(
	'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
					'dbEnums'=>array('default'=>'db_0','TestObj'=>'test')),//根据模块选择的具体的数据库名
	'mssql'=>array('host'=>'fe_auth.db.dev.ad.jinyinmao.com.cn','user'=>'User_nyanya','pass'=>'Password01!','type'=>'sqlsrv',
					'dbEnums'=>array('default'=>'master','TestObj'=>'FE_Auth.dbo')),//根据模块选择的具体的数据库名
);
$GLOBALS['CONF']['dbByObj']=array(//KVObj 根据类名在这里找具体的dbConf的id（对应链接所需的配置）
		'TestObj'=>'mssql'
	);

/**
 * test obj
 */
class TestObj extends Sooh\DB\Base\KVObj
{
	//默认是使用类名称在配置中定位，通过这里函数可以更改
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return get_called_class().($isCache?'Cache':'');
	}
	/**
	 * 
	 * @param int $id
	 * @return \TestObj
	 */
	public static function getCopy($id)
	{
		return parent::getCopy(array('Id'=>$id));
	}
	protected static function splitedTbName($n,$isCache)
	{
		return 'VeriCodes';
//		if($isCache)return 'cache_'.($n%10);
//		else return 'test_id_v';
	}	
}


try{
	$oo = TestObj::getCopy(1);
	$oo->load();
	$oo->registerOn('updateOk', TestObj::onAfterSave);
	//$oo->setField('v', 'val123');
	//$oo->update();
	echo "test.php:";
	echo Sooh\DB\Broker::lastCmd()."\n";
	$r = $oo->dump();
	var_dump($r);
	echo "<hr>";

//echo "[done-".$oo->getField('Cellphone')."]";
}  catch (\Sooh\DB\Error $e){
	echo $e->getCode().":".$e->getMessage()."<br><pre>\n".$e->strLastCmd."<br>\n".$e->getTraceAsString();
} catch (\ErrorException $e){
	echo $e->getCode().":".$e->getMessage()."<br><pre>\n".$e->getTraceAsString();
}