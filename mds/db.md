Sooh\DB
===================
## 基础类使用基础类使用

数据库读写封装类，常用方式如下：
##### 1)配置参数设置方式：
```php
$GLOBALS['CONF']['dbConf']=array(
 'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'db_0')),
 'other'=>array('host'=>'fe_auth.db.dev.ad.jinyinmao.com.cn','user'=>'User_nyanya','pass'=>'Password01!','type'=>'sqlsrv',
                  'dbEnums'=>array('default'=>'master','TestObj'=>'FE_Auth.dbo')),
    );
```
##### 2）设置最后的清理
在入口文件的最后（一般是发布目录的index.php）以两种方式释放相关资源
```php
define("APP_PATH",  dirname(__DIR__));
$app  = new Yaf_Application(APP_PATH . "/conf/application.ini");
$dispatcher = $app->getDispatcher();
$app->run();
//>>code here<<
```
方式1的code：
```php
\Sooh\DB\Broker::free();
```
方式2的code：
```php
\Sooh\Base\Ini::registerShutdown();
```

##### 3）获取数据库访问实例：
```php
$db = Sooh\DB\Broker::getInstance('mssql');
```
##### 4）执行各种命令
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
这里只是举了几个例子，应该不难理解其作用。更多说明和举例参看：

[where的构建](db_where.md "数据库类基础使用方式")

[db常用函数](db_basic.md "数据库类基础使用方式")

## KVOBJ

支持分表分库的，支持缓存的，no-sql模式的封装类，一个类负责一套表结构的读写。基本用法是：
假设用到的表的字段是（iRecordVerID是KVObj用的序列锁字段）
| ID |  VAL  | iRecordVerID
| -- | ---- |   1
| 11  | abci  |  2
| 22  | cdel  |  3


(注意样例代码中'TestObj'相关的部分)

##### 配置设置：
```php
$GLOBALS['CONF']['dbConf']=array(
 'default'=>array('host'=>'127.0.0.1','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp0')),
 'other'=>array('host'=>'127.0.0.2','user'=>'root','pass'=>'','type'=>'mysql','port'=>'3306',
                  'dbEnums'=>array('default'=>'temp','TestObj'=>'db_tmp1')),
    );
$GLOBALS['CONF']['dbByObj']=array('TestObj'=>array('default','other'));
```

##### 类定义
关键点：
- 指定通过TestObj定位是哪个数据库配置
- numToSplit（）说明一共分10张表
- splitedTbName（）确定当前记录使用的是那一张表

```php
class TestClass extends Sooh\DB\Base\KVObj
{
	//默认是使用类名称在配置中定位，通过这里函数可以更改
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

##### 具体使用：
按上面的设置，结果是按照ID的值分到2个库10张表中：

	127.0.0.1: db_tmp0.tb_test_0,db_tmp0.tb_test_2,db_tmp0.tb_test_4,db_tmp0.tb_test_6,db_tmp0.tb_test_8,

	127.0.0.2: db_tmp1.tb_test_1,db_tmp1.tb_test_3,db_tmp1.tb_test_5,db_tmp1.tb_test_7,db_tmp1.tb_test_9,

样例代码：

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

[更多kvobj说明](db_kvobj.md "更多kvobj说明")
