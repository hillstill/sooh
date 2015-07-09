Sooh: stone of  other hill
===================

搞这个库的初衷是没找到用的舒服的数据库访问层。我想用的库有以下几个特点:

1）便于编写
2）便于调试

看过pdo,看过Statements的写法，都觉得欠了点，于是自己捣鼓出下面的写法：
```php
//获取记录总数
$recordCount = $db->getRecordCount('tb_user');
//tb_login_log中取出20121230登入的用户，然后到tb_user表查出uid和phone的对应数组
//phone = array(123123123=>12314123,.....)
$phone = $db->getPair('tb_user','uid','phone',array('uid'=>$db->getCol('tb_login_log','uid',array('ymdLogin'=>'20121230'))));


try{
	//尝试添加，捕获丢出的异常，如果是键重复，改为update, 如果不是键重复，打印日志
	\Sooh\DB\Broker::errorMarkSkip(\Sooh\DB\Error::duplicateKey);
	$db->addRecord('tb_user',array('uid'=>'123123123','phone'=>'12314123'));
}catch(\ErrorException){
	if(\Sooh\DB\Broker::errorIs(\Sooh\DB\Error::duplicateKey)){
		$db->updRecords('tb_user',array('phone'=>'12314123'),array('uid'=>'123123123'));
	}else{
		error_log(Sooh\DB\Broker::lastCmd());
		\\打印日志：[db_host_portIfSetted]insert into tb_user set uid='123123123','phone'=>'12314123'
	}
}
```

加上no-sql大行其道的今天，我又追加了针对此类情况的封装类，根据设置：
1）数据可以分拆存储到不同物理服务器、库、表；
2）可以优先读取cache库，更新的时候才落地；


##Base\\*
basic classes, such as Ini,Time, Tools ....

基础类, 诸如 ini ( 配置类 ), Time (时间类), Tools(工具类).... 都是些小东西。
[see details](mds/base.md "see details")

##DB\\*
classes based on database (abstract layer and classes for special cases)， include kvobj (support table-split and cache) and other classes for special cases.

数据库类, 数据库访问封装，KVOBJ类（内置支持分表分库，缓存表）以及一些特定应用场景的封装类
[see details](mds/db.md "see details")

后续目标：
目前支持mysql,mssql(微软提供的库),看过mongo,redis,oracle的文档，应该可以支持，但没写出对应封装类。