drop database if exists ZH;
create database ZH;
use ZH;

create table ember (
	id integer auto_increment primary key,
    nev varchar(50) not null unique
);

create table DVD (
	id integer auto_increment primary key,
    cim varchar(50) not null, -- lehet ugyan az a DVD többször is a rendszerben, nem csak 1 ∃
    tulajdonos integer not null,
    foreign key (tulajdonos) references ember(id)
);

create table kolcsonzes (
    dvd_id integer primary key, -- alapból unique, nem kell félni hogy 1 konkrét dvd többször is szerepel
    kolcsonzo_id integer,
    foreign key (dvd_id) references DVD(id),
    foreign key (kolcsonzo_id) references ember(id)
);

insert into ember values (default, 'Alfonz');
insert into ember values (default, 'Béla');
insert into ember values (default, 'Cecil');

insert into DVD values (default, 'Harry Potter 3',      1);
insert into DVD values (default, 'Gyaloggalopp',        2);
insert into DVD values (default, 'Jóbarátok 1. évad',   2);
insert into DVD values (default, 'Gravity',             2);

insert into kolcsonzes values (1, 3);
insert into kolcsonzes values (2, 1);
insert into kolcsonzes values (4, 1);


-- select d.cim cim, t.nev tulaj, ifnull(k.nev, '(nincs kölcsön adva)') kolcsonzo from DVD d inner join ember t on t.id = d.tulajdonos left join kolcsonzes kt on kt.dvd_id = d.id left join ember k on kt.kolcsonzo_id = k.id;


-- select d.cim cim, t.nev tulaj, ifnull(k.nev, '(nincs kölcsön adva)') kolcsonzo from DVD d inner join ember t on t.id = d.tulajdonos left join ember k on k.id = d.kolcsonzo where k.nev = 'Alfonz';
