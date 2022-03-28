use mydb;


-- Delete previous db - of course this is only done in testing, not in production
-- the where clauses are there to allow deletion in safe mode
-- (delete from w/o any where clauses is disabled for pretty good reasons)
delete from oktat where targy_idtargy > 0;
delete from targy where idtargy > 0;
delete from oktato where oktatoID>0;
delete from hallgato where HallgatoID > 0;
delete from tanul where hallgato_HallgatoID > 0;

-- targy feltoltese
insert into targy values (1, 'Informatika 2', null);

-- oktato feltoltese
insert into oktato values (1, 'Szegetes Luca alteregója', 'AUT');
insert into oktat values (1,1);

-- hallgato feltoltese es targy felvetele
insert into hallgato values (1, 'Kovács Gültem', 'Faluvége', '01189998819991197253', STR_TO_DATE('2001-02-28','%Y-%m-%d'));
insert into tanul values (1,1);

-- targy modositasa (es ellenorzes)
update targy set nev='Info 2' where idtargy = 1;
select * from targy;

-- oktatok lekerdezese 2x
select * from oktato;
select * from oktato where nev like 'Sz%';

-- lejelentkezes csak errol a targyrol
delete from tanul where hallgato_HallgatoID = 1 and targy_idtargy = 1;

-- leiras nelkuli targyak listazasa
select * from targy where leiras is null;