执行mysql.sql, mssql.sql 建立初始测试库
〉〉会在需要的数据库建立两个新的测试库：db_0,db_1(提供的sql脚本建立的是这两个名字),
〉〉并在其下建立新表tb_0,tb_1并填充数据（字段定义和默认数据参见各个类型数据库的sql脚本）

根据需要修改 connect.ini（连接数据库的参数）

还有被测试类库要能通过autoload自动加载（需要改composer里autoload_namespace.php）