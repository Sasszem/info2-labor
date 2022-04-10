-- Név: Baráth László
-- Neptun: Q6KTPF

-- use konyvtar;

-- 1. Feladat - listázzuk ki a könyvtár tagjait
select * from tag;

-- 2. feladat - Listázza ki, hogy az egyes tagok eddig milyen könyveket kölcsönöztek ki!
select t.vezeteknev, t.keresztnev, k.cim 
from tag as t 
inner join kolcsonzesek as ko on ko.tagid = t.id 
inner join konyv as k on ko.konyvisbn = k.isbn;

-- 3. feladat - Listázza ki, hogy az egyes tagok milyen szerzők könyveit kölcsönözték ki
select distinct concat(t.vezeteknev, ' ', t.keresztnev) as tagnev,
	concat(sz.vezeteknev, ' ', sz.keresztnev) as szerzo 
from tag as t 
inner join kolcsonzesek as ko on ko.tagid = t.id 
inner join konyv as k on ko.konyvisbn = k.isbn 
inner join konyv_szerzo as ksz on ksz.konyvisbn = k.isbn 
inner join szerzo as sz on sz.id = ksz.szerzoid 
order by tagnev;


-- 4. feladat - Az egyes tagok hányszor kölcsönöztek eddig!
select concat(t.vezeteknev, ' ', t.keresztnev) as nev, count(kcs.id) as kolcsonzesek_szama 
from tag t 
inner join kolcsonzesek kcs on kcs.tagid = t.id 
group by t.id;

-- 5. feladat
-- Akik eddig legalább 3-szor kölcsönöztek
select concat(t.vezeteknev, ' ', t.keresztnev) as nev, count(kcs.id) as kolcsonzesek_szama 
from tag t 
inner join kolcsonzesek kcs on kcs.tagid = t.id 
group by t.id having kolcsonzesek_szama > 2;

-- 6. feladat
-- Hagyjuk meg csak azokat, akiknek a tagságija érvényes!
select concat(t.vezeteknev, ' ', t.keresztnev) as nev, count(kcs.id) as kolcsonzesek_szama 
from tag t 
inner join kolcsonzesek kcs on kcs.tagid = t.id
where t.tagsagiervenyes > curdate()
group by t.id having kolcsonzesek_szama > 2;

-- 7. feladat
-- Az egyes tagok mikor kölcsönöztek utoljára!
select concat(t.vezeteknev, ' ', t.keresztnev) as nev, max(kcs.kiviteldatum) as utolcso_kolcsonzes
from tag t 
inner join kolcsonzesek kcs on kcs.tagid = t.id
group by t.id;

-- 8. feladat
-- Szerzőkként, hogy összesen hány kölcsönzésük van?
select concat(sz.vezeteknev, ' ', sz.keresztnev), count(ko.id) as kolcsonzesek 
from szerzo sz
inner join konyv_szerzo ksz on sz.id = ksz.szerzoid
inner join konyv k on k.isbn = ksz.konyvisbn
inner join kolcsonzesek ko on ko.konyvisbn = k.isbn
group by sz.id;

-- 9. feladat
-- Listázza ki azon tagok adatait akiknek a tagsága lejárt!
select * from tag where tagsagiervenyes <= curdate();

-- 10. feladat
-- Listázza ki azon tagokat akiknek már lejárt a kölcsönzése, de még nem hozta vissza a kikölcsönzött könyvet!
select * from tag t
inner join kolcsonzesek kcs on kcs.tagid = t.id
where kcs.lejaratdatum < curdate() and kcs.visszahozataldatum is null;

-- 11. feladat
-- Listázza ki azokat a szerzőket, akiknek még egyetlen könyvét sem kölcsönözték ki, de van könyvük a könyvtárban!
select * from szerzo sz 
inner join konyv_szerzo ksz on ksz.szerzoid = sz.id
where sz.id not in (
	select sz.id from szerzo sz
	inner join konyv_szerzo ksz on ksz.szerzoid = sz.id
	inner join kolcsonzesek ko on ko.konyvisbn = ksz.konyvisbn
);
-- left join + is null nem jó, mivel lehet olyan hogy csak egy könyvét nem kölcsönözték ki, de másikat igen
-- külsőben inner join a "van könyve a könyvtárban" miatt

-- 11. feladat
-- Kik írták a legdrágább könyvet?
select sz.* from szerzo sz
inner join konyv_szerzo ksz on ksz.szerzoid = sz.id
inner join konyv k on k.isbn = ksz.konyvisbn
where k.ar = (select max(Ar) from konyv);

-- 12. feladat
-- Listázza ki azokat a könyveket, melyek átlagban legalább 30 napig voltak kikölcsönözve!
-- Az átlagszámítás során csak a visszahozott kölcsönzéseket vegye figyelembe!
select k.*, AVG(datediff(ko.visszahozataldatum, ko.kiviteldatum)) as avgkolcsonzes from konyv k
inner join kolcsonzesek ko on ko.konyvisbn = k.isbn
group by k.isbn having avgkolcsonzes > 30;


-- 12. feladat
-- Listázza ki azokat a könyveket, melyek átlagban legalább 30 napig voltak kikölcsönözve!
-- Az átlagszámítás során csak a visszahozott kölcsönzéseket vegye figyelembe!
select k.*, AVG(datediff(ifnull(ko.visszahozataldatum, curdate()), ko.kiviteldatum)) as avgkolcsonzes from konyv k
inner join kolcsonzesek ko on ko.konyvisbn = k.isbn
group by k.isbn having avgkolcsonzes > 30;

-- @@@@@@@@@@@@@@@@@@@@
-- @ ÖNÁLLÓ FELADATOK @
-- @@@@@@@@@@@@@@@@@@@@


-- 1. feladat
-- Listázza ki azokat könyveket, melyeket még nem kölcsönöztek ki soha!
select k.* from konyv k
left join kolcsonzesek ko on ko.konyvisbn = k.isbn
where ko.id is null;

-- 2. feladat
-- Melyik a legrégebbi könyv?
select * from konyv k
order by k.megjelenes limit 1;