Sooh\DB
===================
## 常用函数的使用方式

例子说明一切(以mysql为例) 
######select * from tb where a=1 and b=1 limit 0,1
```php
$db->getRecord('tb','*', array('a'=>1,'b='=>1));
```

######select * from tb where a=array(1,2) and b is null order by a ,b desc group by c ,d limit 0,1
```php
$db->getRecord('tb','*', array('a'=>array(1,2),'b'=>null),'sort a rsort b group c group d');
```

######每页10条记录，获取第三页：select * from tb where a>10 limit 10,20
```php
$db->getRecords('tb','*', array('a>'=>10),null,10,20);
```

######获取pair:array('a'=>1,'b'=>1)：select f1,f2 from tb where f1 in ('a','b') and f2=1
```php
$db->getPair('tb','f1','f2', array('f1'=>array('a','b'),'f2'=>1));
```

######获取一列（2000-1-1日注册的用户的userid列表：array(u1,u2,u3)）：select uid from tb where ymdreg=20000101;
```php
$db->getCol('tb','uid', array('ymdreg'=>20000101));
```

######获取assoc（2000-1-1日注册的用户的信息：array(uid1=>array(gender=>'f',age=>21),uid2=>array(gender=>'m',age=>22)))
```php
$db->getAssoc('tb','uid', 'gender,age', array('ymdreg'=>20000101));
```

######获取单值（userid=1的生日）：select birthday from tb where userid=1;
```php
$db->getOne('tb','birthday', array('userid'=>1));
```

######insert into tb (a,b) values(1,2)
```php
$db->addRecord('tb',array('a'=>1,'b'=>1));
//如果有递增字段，这里返回的应该是lastInsertId,否则返回true
```

######delete from tb where a=1
```php
$db->delRecords('tb',array('a'=>1));
```

######update tb tb set b=1 where a=1
```php
$db->updRecords('tb',array('b'=>1),array('a'=>1));
```

######组合拳：update tb tb_b set b=1 where a in (select id from tb_a)
```php
$db->updRecords('tb_b',array('b'=>1),array('a'=>$db->getCol('tb_a','id')));
```


######自定义查询 show variables like '%char%'
```php
$result = $db->execCustom(array('sql'=>"show variables like '%char%'"));
$rs = $db->fetchAssocThenFree($result);
```


######处理预定异常（比如添加时发现键冲突，改update）
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