drop schema if exists konyvtar;
CREATE SCHEMA konyvtar DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ;
use konyvtar;

/*
drop table konyv_szerzo;
drop table kolcsonzesek;
drop table tag;
drop table konyv;
drop table szerzo;
*/

create table konyv
( isbn numeric(10,0) primary key,
  cim varchar(30),
  nyelv varchar(10),
  ar numeric(6,0),
  megjelenes date,
  kiado varchar(30),
  leiras varchar(1500)
);

create table szerzo
( id numeric(10,0) primary key,
  vezeteknev varchar(30),
  keresztnev varchar(30),
  nemzetiseg varchar(10)
);

create table konyv_szerzo
( id numeric(10,0) primary key,
  konyvisbn numeric(10,0),
  szerzoid numeric(10,0),
  reszesedes numeric(3,2),
  foreign key(konyvisbn) references konyv(isbn),
  foreign key(szerzoid) references szerzo(id)
);

create table tag
( id numeric(10,0) primary key,
  vezeteknev varchar(30),
  keresztnev varchar(30),
  iranyitoszam numeric(4,0),
  varos varchar(20),
  utca varchar(40),
  tagsagiervenyes date,
  MaxKolcsonzes numeric(4,0)
);

create table kolcsonzesek
( id numeric(10,0) primary key,
  konyvisbn numeric(10,0),
  tagid numeric(10,0),
  kiviteldatum date,
  lejaratdatum date,
  visszahozataldatum date,
  foreign key(konyvisbn) references konyv(isbn),
  foreign key(tagid) references tag(id)
);

insert into tag values(7,'Kelemen','András',1958,'Budapest','Király u. 1',adddate(curdate(),100),5);
insert into tag values(6,'Balogh','Péter',2440,'Százhalombatta','Kodály u. 55',adddate(curdate(),100),5);
insert into tag values(5,'Sokadik','Béla',2440,'Kerecsend','Kodály u. 45/b',adddate(curdate(),100),5);
insert into tag values(4,'Nagy','Gábor',2040,'Budaörs','Fő u. 45',adddate(curdate(),-200),5);
insert into tag values(3,'Kovách','István',1074,'Budapes','Liliom u 14',adddate(curdate(),100),5);
insert into tag values(2,'Kovács','Jenő',2030,'Érd','Kökörcsin u 23',adddate(curdate(),-100),5);
insert into tag values(1,'Kováts','Béla',1111,'Budapest','Kiss u. 11',adddate(curdate(),100),5);

insert into szerzo values(3,'Kiss','Balázs','Magyar');
insert into szerzo values(10,'Közepes','János','Magyar');
insert into szerzo values(9,'Nagy','Péter','Magyar');
insert into szerzo values(8,'Abramson','Ian','Angol');
insert into szerzo values(7,'Taub','Ben','Angol');
insert into szerzo values(6,'Hoffer','Jeffrey','Angol');
insert into szerzo values(5,'Prescott','Mary','Angol');
insert into szerzo values(4,'McFadden','Fred','Angol');
insert into szerzo values(2,'Jókai','Mór','Magyar');
insert into szerzo values(1,'Rejtő','Jenő','Magyar');

insert into konyv values(5,'Data Warehousing','Angol',35000,str_to_date('1998.10.05','%Y.%m.%d'),'OraclePress',null);
insert into konyv values(4,'Modern Database Managmet','Angol',15000,str_to_date('2000.11.15','%Y.%m.%d'),'Addison-Wesley',null);
insert into konyv values(3,'Három testőr Afrikában','Magyar',350,str_to_date('1998.05.05','%Y.%m.%d'),'Panem',null);
insert into konyv values(2,'Kőszívű ember fiai','Magyar',1000,str_to_date('1985.10.05','%Y.%m.%d'),'Szépirodalmi',null);
insert into konyv values(1,'Adatbázisok','Magyar',1500,str_to_date('1999.10.05','%Y.%m.%d'),'Műszaki','Jó kis könyv');

insert into konyv_szerzo values(10,5,8,.2);
insert into konyv_szerzo values(9,5,7,.3);
insert into konyv_szerzo values(8,5,6,.3);
insert into konyv_szerzo values(7,4,6,.2);
insert into konyv_szerzo values(6,4,5,.4);
insert into konyv_szerzo values(5,4,4,.4);
insert into konyv_szerzo values(4,3,1,1);
insert into konyv_szerzo values(3,2,2,1);
insert into konyv_szerzo values(2,1,10,.3);
insert into konyv_szerzo values(1,1,3,.7);

insert into kolcsonzesek values(14,4,6,adddate(curdate(),-35),adddate(curdate(),-5),null);
insert into kolcsonzesek values(13,4,1,adddate(curdate(),-60),adddate(curdate(),-40),adddate(curdate(),-10));
insert into kolcsonzesek values(12,4,5,adddate(curdate(),-25),adddate(curdate(),+5),null);
insert into kolcsonzesek values(11,3,5,adddate(curdate(),-35),adddate(curdate(),-5),adddate(curdate(),-8));
insert into kolcsonzesek values(10,3,4,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-17));
insert into kolcsonzesek values(9,3,1,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-12));
insert into kolcsonzesek values(8,3,2,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-15));
insert into kolcsonzesek values(7,3,3,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-14));
insert into kolcsonzesek values(6,2,3,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-8));
insert into kolcsonzesek values(5,2,2,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-1));
insert into kolcsonzesek values(4,2,6,adddate(curdate(),-45),adddate(curdate(),-10),null);
insert into kolcsonzesek values(3,1,6,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-23));
insert into kolcsonzesek values(2,1,2,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-4));
insert into kolcsonzesek values(1,1,1,adddate(curdate(),-45),adddate(curdate(),-10),adddate(curdate(),-16));
insert into kolcsonzesek values(15,1,1,adddate(curdate(),-20),adddate(curdate(),-10),null);