Sooh\DB
===================
## ������ʹ�û�����ʹ��

���ݿ��д��װ�࣬���÷�ʽ���£�
##### 1)���ò������÷�ʽ��
```php
$GLOBALS['CONF']['dbConf']=array(
 'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'db_0')),
 'other'=>array('host'=>'fe_auth.db.dev.ad.jinyinmao.com.cn','user'=>'User_nyanya','pass'=>'Password01!','type'=>'sqlsrv',
                  'dbEnums'=>array('default'=>'master','TestObj'=>'FE_Auth.dbo')),
    );
```
##### 2��������������
������ļ������һ���Ƿ���Ŀ¼��index.php�������ַ�ʽ�ͷ������Դ
```php
define("APP_PATH",  dirname(__DIR__));
$app  = new Yaf_Application(APP_PATH . "/conf/application.ini");
$dispatcher = $app->getDispatcher();
$app->run();
//>>code here<<
```
��ʽ1��code��
```php
\Sooh\DB\Broker::free();
```
��ʽ2��code��
```php
\Sooh\Base\Ini::registerShutdown();
```

##### 3����ȡ���ݿ����ʵ����
```php
$db = Sooh\DB\Broker::getInstance('mssql');
```
##### 4��ִ�и�������
```php
$recordCount = $db->getRecordCount($tbname);
$userPhone = $db->getPair('tb_user','uid','phone',array('ymdRegister'=>20121230));

try{
	\Sooh\DB\Broker::errorMarkSkip(\Sooh\DB\Error::duplicateKey);
	$db->addRecord('tb_user',array('uid'=>'123123123','phone'=>'12314123'));
}catch(\ErrorException){
	if(\Sooh\DB\Broker::errorIs(\Sooh\DB\Error::duplicateKey)){
		$db->updRecords('tb_user',array('phone'=>'12314123'),array('uid'=>'123123123'));
	}else{
		error_log(Sooh\DB\Broker::lastCmd());
	}
}
```
����ֻ�Ǿ��˼������ӣ�Ӧ�ò�����������á�����˵���;����ο���

[where�Ĺ���](db_where.md "���ݿ������ʹ�÷�ʽ")

[db���ú���](db_basic.md "���ݿ������ʹ�÷�ʽ")

## KVOBJ

֧�ֱַ�ֿ�ģ�֧�ֻ���ģ�no-sqlģʽ�ķ�װ�࣬һ���ฺ��һ�ױ�ṹ�Ķ�д�������÷��ǣ�
�����õ��ı���ֶ��ǣ�iRecordVerID��KVObj�õ��������ֶΣ�
| ID |  VAL  | iRecordVerID
| -- | ---- |   1
| 11  | abci  |  2
| 22  | cdel  |  3


(ע������������'TestObj'��صĲ���)

##### �������ã�
```php
$GLOBALS['CONF']['dbConf']=array(
 'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp0')),
 'other'=>array('host'=>'127.0.0.2','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp1')),
    );
$GLOBALS['CONF']['dbByObj']=array('TestObj'=>array('default','other'));
```

##### �ඨ��
�ؼ��㣺
- ָ��ͨ��TestObj��λ���ĸ����ݿ�����
- numToSplit����˵��һ����10�ű�
- splitedTbName����ȷ����ǰ��¼ʹ�õ�����һ�ű�

```php
class TestClass extends Sooh\DB\Base\KVObj
{
	//Ĭ����ʹ���������������ж�λ��ͨ�����ﺯ�����Ը���
	protected static function idFor_dbByObj_InConf($isCache)
	{
		return 'TestObj'.($isCache?'Cache':'');
	}
	protected static function splitedTbName($n,$isCache)
	{
		if($isCache)return 'tb_test_cache_'.($n % static::numToSplit());
		else return 'tb_test_'.($n % static::numToSplit());
	}
	protected static function numToSplit(){return 10;}
}
```

##### ����ʹ�ã�
����������ã�����ǰ���ID��ֵ�ֵ�2����10�ű��У�

	127.0.0.1: db_tmp0.tb_test_0,db_tmp0.tb_test_2,db_tmp0.tb_test_4,db_tmp0.tb_test_6,db_tmp0.tb_test_8,

	127.0.0.2: db_tmp1.tb_test_1,db_tmp1.tb_test_3,db_tmp1.tb_test_5,db_tmp1.tb_test_7,db_tmp1.tb_test_9,

�������룺

```php
try{
	$oo = TestObj::getCopy(array('ID'=>11));
	$oo->load();
	//$oo->registerOn('updateOk', TestObj::onAfterSave);
	$oo->getField('VAL'); // = abci
	$oo->setField('VAL', 'val123');
	$oo->update();
}catch(\ErrorException $e){
	error_log(Sooh\DB\Broker::lastCmd());
}
```

[����kvobj˵��](db_kvobj.md "����kvobj˵��")
