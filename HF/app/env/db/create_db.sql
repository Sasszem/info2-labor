-- @@@@@@@@@@@@@@@@@@@@@@@@
-- @ CREATE DB AND TABLES @
-- @@@@@@@@@@@@@@@@@@@@@@@@

-- cleanup
drop database if exists HAM;

create database HAM
	DEFAULT CHARACTER SET utf8mb3
	DEFAULT COLLATE utf8_general_ci; -- case-insensitive collation
use HAM;

-- DB user setup so we don't have to use root from php
GRANT ALL PRIVILEGES ON HAM. * TO 'access_denied';
FLUSH PRIVILEGES;

-- ham = user registered in app
create table ham (
	id int primary key auto_increment,
	callsign nvarchar(10) not null unique,
    email nvarchar(100) not null,
    passwd nvarchar(100) not null, -- password, but that's a reserved keyword in sql
    -- these are all mandatory

    theme int default 0, -- frontend theme, not mandatory

    uname nvarchar(100), -- user (real) name, but 'name' is a reserved keyword in sql
    qth nvarchar(255),
    exam_level enum ('-', 'NNOVICE', 'CEPT NOVICE', 'HAREC') not null default '-',
    morse_exam bool not null default false,
    -- all of the above is optional, but will be public if filled in

    email_visible bool not null default false,
    -- having email is mandatory, but users might choose if they want it to be public

    dev bool not null default false
);

create table qso (
	id int primary key auto_increment,
    -- a QSO is only valid if everything is filled in, thus everything is 'not null'
	ham_1 nvarchar(20) not null,
    ham_2 nvarchar(20) not null,
    dtime datetime not null, -- date & time combined
    mode enum ('CW', 'DSB', 'USB', 'LSB', 'NFM', 'RTTY') not null,
    freq double not null,
    report_1 int not null,
    report_2 int not null,
    -- Used callsigns can have pre/postfixes, so we extract the base callsign using regex
    -- using (non-trivial) regex is also worth extra points
	ham_1_cs nvarchar(10) generated always as (REGEXP_REPLACE(ham_1, '([A-Za-z0-9]{1,3}/)?([A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5})(/[A-Za-z0-9]{1,3})?', '$2')) stored,
    ham_2_cs nvarchar(10) generated always as (REGEXP_REPLACE(ham_2, '([A-Za-z0-9]{1,3}/)?([A-Za-z0-9]{1,3}[0-9][A-Za-z]{1,5})(/[A-Za-z0-9]{1,3})?', '$2')) stored,
    -- a combined column is also stored so searching in both ham_1_cs and ham_2_cs is easier.
    participants nvarchar(20) generated always as (CONCAT(ham_1_cs, '|', ham_2_cs)) stored,
	foreign key (ham_1_cs) references ham(callsign) -- can have constraint on only one of the two sides to allows QSOs with non-registered HAMs
);

-- possibly want to search for this
create index hcs1 on qso(ham_1_cs);
create index hcs2 on qso(ham_2_cs);

create index dt on qso(dtime);
create index f on qso(freq);


create table qsl (
	-- only valid if everything is filled in
	id int primary key auto_increment,
    sender int not null,
    recipient int not null,
	image_file nvarchar(100) not null,
    accepted bool not null,
	qsoid int not null,
    -- if we want to allow user deletion, this has to be changed
    foreign key (sender) references ham(id),
    foreign key (recipient) references ham(id),
    foreign key (qsoid) references qso(id)
);

create index sender on qsl(sender);
create index recipient on qsl(recipient);