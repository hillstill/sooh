drop database if exists db_0;
drop database if exists db_1;

create database db_0;
create database db_1;

CREATE TABLE [db_0].[dbo].[tb_0] ([autoid] int NOT NULL IDENTITY(1,1) ,[pkey] int NULL ,[subkey] int NULL , [val] varchar(50) COLLATE Chinese_PRC_CI_AS NULL ,[iRecordVerID] bigint NOT NULL DEFAULT ((0)) ,
CONSTRAINT [PK__tb_0__3853EA807E42C360] PRIMARY KEY ([autoid])) ON [PRIMARY]
GO;

CREATE UNIQUE INDEX [psub] ON [db_0].[dbo].[tb_0]([pkey] ASC, [subkey] ASC) WITH (IGNORE_DUP_KEY = ON)ON [PRIMARY]
GO;

CREATE TABLE [db_1].[dbo].[tb_1] ([autoid] int NOT NULL IDENTITY(1,1) ,[pkey] int NULL ,[subkey] int NULL , [val] varchar(50) COLLATE Chinese_PRC_CI_AS NULL ,[iRecordVerID] bigint NOT NULL DEFAULT ((0)) ,
CONSTRAINT [PK__tb_0__3853EA807E42C360] PRIMARY KEY ([autoid])) ON [PRIMARY]
GO;

CREATE UNIQUE INDEX [psub] ON [db_1].[dbo].[tb_1]([pkey] ASC, [subkey] ASC) WITH (IGNORE_DUP_KEY = ON)ON [PRIMARY]
GO;

insert into db_0.dbo.tb_0 values (2,	201,	220,	'a',	0);
insert into db_0.dbo.tb_0 values (4,	201,	240,	'b',	0);
insert into db_0.dbo.tb_0 values (6,	201,	260,	'c',	0);
insert into db_0.dbo.tb_0 values (8,	202,	220,	'd',	0);
insert into db_0.dbo.tb_0 values (10,	202,	240,	'e',	0);
insert into db_0.dbo.tb_0 values (12,	202,	260,	'f',	0);
						             
insert into db_1.dbo.tb_1 values (1,	201,	210,	'a',	0);
insert into db_1.dbo.tb_1 values (3,	201,	230,	'b',	0);
insert into db_1.dbo.tb_1 values (5,	201,	250,	'c',	0);
insert into db_1.dbo.tb_1 values (7,	202,	210,	'd',	0);
insert into db_1.dbo.tb_1 values (9,	202,	230,	'e',	0);
insert into db_1.dbo.tb_1 values (11,	202,	250,	'f',	0);
