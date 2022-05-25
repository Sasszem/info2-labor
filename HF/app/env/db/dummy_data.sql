insert into ham values (
	default,
	'HA7DINO',
    'barath.laszlo@simonyi.bme.hu',
    '$2y$10$ShZvnfajjLd1UAbjfK021.eUDS5Q3wnOwJAUNcfupkRlvBS0UrgR.', -- abcd1234 hash-e password_hash + bcrypt-el
    0,
    'Baráth László',
    'Koliszoba',
    'HAREC',
    true,
    default,
	true
);

insert into ham values (
	default,
	'HA5XXD',
    'valami@example.com',
    '$2y$10$ShZvnfajjLd1UAbjfK021.eUDS5Q3wnOwJAUNcfupkRlvBS0UrgR.',
    0,
    'XXXD',
    'Otthonról',
    'HAREC',
    true,
    default,
    default
);

insert into ham values (
	null,
	'HA7WEN',
    'dudas.levente@vik.bme.hu',
    '$2y$10$ShZvnfajjLd1UAbjfK021.eUDS5Q3wnOwJAUNcfupkRlvBS0UrgR.',
    0,
    'Dudás Levente',
    'Műholdról nyomja',
    'HAREC',
    true,
	default,
    default
);


insert into ham values (
	null,
	'HA5CSIP',
    'ize@valami.com',
    '$2y$10$ShZvnfajjLd1UAbjfK021.eUDS5Q3wnOwJAUNcfupkRlvBS0UrgR.',
    0,
    'Erős Pista',
    'Üvegből',
    'NNOVICE',
    false,
    default,
    default
);


insert into qso values (
	null,
	'HA7DINO/AM',
    'OE/HA7WEN',
    now(),
    'USB',
    14000000,
    59,
    59,
    DEFAULT,
    DEFAULT,
    DEFAULT
);

use HAM;
SELECT * FROM ham where id = 5;