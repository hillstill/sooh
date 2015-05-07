<?php
// 最基本的使用方式

$GLOBALS['CONF']['dbConf']=array(
	'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
					'dbEnums'=>array('default'=>'db_0','TestObj'=>'test')),//根据模块选择的具体的数据库名
	'mssql'=>array('host'=>'fe_auth.db.dev.ad.jinyinmao.com.cn','user'=>'User_nyanya','pass'=>'Password01!','type'=>'sqlsrv',
					'dbEnums'=>array('default'=>'master','TestObj'=>'FE_Auth.dbo')),//根据模块选择的具体的数据库名
);
$GLOBALS['CONF']['dbByObj']=array(//KVObj 根据类名在这里找具体的dbConf的id（对应链接所需的配置）
		'TestObj'=>'mssql'
	);
//使用mssql的配置
$db = Sooh\DB\Broker::getInstance('mssql');
//直接执行sql的方式
$rs2 = $db->execCustom(array('sql'=>'select top 1 * from FE_Auth.dbo.VeriCodes where Id=1'));
$r = $db->fetchAssocThenFree($rs2);
//sql server 里datetime2 类型的字段转成timestamp
Sooh\DB\Conv\Datetime::loop_to_timestamp(array('BuildAt'), $r, 'rows');
//使用封装函数获取指定的格式的数据
$pair = $db->getPair('tablename','field_as_key', 'field_as_val',array('id]'=>0,'id<'=>10,'field_not_null!'=>null));

