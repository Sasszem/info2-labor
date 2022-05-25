# Q6K - rádióamatőr naplózó webalkalmazás

Informatika 2 nagyházi

Név: Baráth László
Neptun: Q6KTPF

[Link a bemutatóvideóhoz](https://youtu.be/nKC-Dv7rHwM)

## Env setup

`docker-compose up`
MySQL workbench-el (vagy egyéb adatbáziskezelővel) csatlakozni kell a `localhost:33060`-ra, root password: `let_me_in`-el, és lefuttatni a `env/db/create_db.sql` és (opcionálisan) `dummy_data.sql` fájlokat. A webalkalmazás ezután helyesen működik.

## Dev dokumentáció nagy vonalakban

Főként a forráskód kommentjeivel van dokumentálva a projekt, de nagy vonalakban összefoglalva a felépítés
- PHP + mysql + nginx alapú webalkalmazás, bootstrap 5 UI
- felépítés: MVC alap, Router osztály (index.php front controller) dep. injekteli a modelleket a controllerek public static methodjába, amelyek visszatérnek az eredménnyel (controller hívja a view-t), és végül a front view (MainView) megjeleníti az oldalt.
- OOP
- session alapú user cache (LoggedInUser)
- modellek felelnek a db-vel való kapcslatért, a controllerek dolga a requestek fogadása, ellenőrzése és feldolgozása
- view-ek feladata az eredmény HTML-é renderelése, de ez a felelősség részben a controllerekbe került
- custom routing regex alapon amely megkerüli az nginx routerét
- 3 jogosultsági szint: Guest, User, Dev
- naplózó alkalmazás révén nme lehet adatkat módosítani vagy törölni (vagy nagyon limitáltan), de a DEV felhasználónak van hozzáférése a DBhez közvetlenül (constraint-ek még mindig korlátozzák!)
- sql injection elleni védelem (kiv. dev controller bizonyos részei, de ezek eleve dev jogot igényelnek), prepared statementek használata
- témaváltás: bootstwath-os téma link cserélés

## Követelmények / pontok

### A megvalósítandó feladat egy webes alkalmazás PHP nyelven írva, a HTML, CSS technológiákat használva. Az alkalmazásnak relációs adatbázist kell használnia. A funkcionális elvárások ennek a dokumentumnak a végén találhatók.
CSS helyett bootstrap-et használtam, de a példaházi is ezt csinálta

### A videó maximum 5 perces lehet (szigorú formai követelmény) és hangalámondásos vagy feliratozott screen cepture videó formájában be kell mutatnia a házi feladat működését (minden előírt „mit tartalmazzon” elemet forráskód vagy képernyőkép szintjén).
[Link a bemutatóvideóhoz](https://youtu.be/nKC-Dv7rHwM)

### Az adatbázis séma legalább 2 adatbázistáblából kell álljon. Mindegyik táblában legalább 3 oszlop szerepeljen, az adatbázis táblák között legalább 1 külső kulcs hivatkozás kell legyen.
`create_db.sql`-ben található az adatbázis-definíció, amely ennek megfelel.

### Készüljön stíluslap az egyes oldalak egységes megjelenítésének támogatására
Bootstrap miatt nem volt rá szükség, de ezt csinálta a példaházi is.

### Az alkalmazásnak legyen egységes fejléce, lábléce és menüje, amelyek minden oldalon megjelennek. A menüből legyenek elérhetők a főbb funkciók.
A videóban látható volt, hogy így van. A fejlécet a MainView rendereli

### Az alkalmazásban kell legyen mód minden adatbázisban tárolt adat kiolvasására az adatbázisból, azok megjelenítésére, új adatok bevitelére és a meglévő adatok módosítására. (Tehát nem elegendő, ha csak írni, vagy csak olvasni tudjuk az adatot, szerkeszteni is tudni kell azokat.)

Mivel ez egy naplózó alkalmazás, így nincs minden adatnál ezekre lehetőség, de több helyen szerepel ez, illetve DEV jogosultásg mellett az adatbázistáblák közvetlen szerkesztehtők (a külső kulcs kapcsolatok betartása mellett)

### Legyen lehetőség az adatbázistáblák közötti külső kulcs kapcsolatok megjelenítésére, szerkesztésére és törlésére. Ha például egy könyv adatbázisban könyvek és szerzők adatait tároljuk, akkor legyen lehetőség szerkeszteni, hogy melyik könyv melyik szerzőhöz tartozik és ezt a kapcsolatot tudjuk változtatni is. A szerkesztés nem azt jelenti, hogy kitöröljük a kapcsolatot, majd egy újat létrehozunk, hanem a meglévőt szerkesztjük.
Látható, hogy a QSL lap küldés így működik. A QSO-nál nem lehet átírni a kapcsolatot, mivel az egy naplóbejegyzésnek számít.

### Fontos, hogy a felületen az adatok elérése a felhasználó számára kényelmes módon történjen.
Látható, hogy készült egységes és könnyen kezelhető ellület

### A felhasználó által beírt bemenetet ellenőrizni kell mielőtt adatbázisba írjuk. SQL injection elleni védelmet biztosítani kell. Az adatmódosításkor, felvitelnél figyelni kell a hibás értékek kiszűrésére, például üresen hagyott mezők, értelmetlen értékek (szöveg beírása szám helyett stb.). Ezeket jelezni kell a felhasználónak. 
A controllerekben ezek az ellenőrzések megtalálhatók, az SQL injection elleni védelmet prepared statementekkel oldottam meg (kivételt képez ez alól a DEV néhány része, de ezeket amúgy is csak privilégizált felhasználó érheti el)

### Legyen lehetőség az adatbázis legalább egyik táblájában keresni (pl. könyveket kilistázni címeik alapján).
Mind HAM-re, mind QSO-ra lehet fuzzy keresni;

### Nem elfogadható megoldás, ha az adatokat nem lehet módosítani, csak törölni és újakat beszúrni. Az adatok módosítása azt jelenti, hogy ha például egy könyv címét meg akarjuk változtatni, akkor nem kell kitörölni a könyvet és egy újat létrehozni, hanem tudjuk módosítani a meglévő adatbázis bejegyzésben az adott oszlopot.
Naplózó alkalmazás miatt ezen műveletekből nem lehetséges mindegyik, de a nem naplózótt részek szabadon szerkeszthetők, a többi DEV joggal.

### A kényelmes felhasználói felület azt jelenti, hogy ha például szerzőket és könyveket kell összerendeljünk, akkor nem egy szövegdobozban kell megadnunk a szerző és a könyv azonosítóját, hanem a megfelelő szerzőt és könyvet legördülő menüből ki tudjuk választani.
A QSO hívójelet nem érdemes így megoldani, mivel pre és postfixelhető, de az enum / bool típusok esetében legördülő menüből lehet választani

### Pontot érő részletek (ahol részleges megoldásért részpontszámok is adhatóak):
#### Az adatbázisban összetett kulcs használata: 5p
Nem használtam ilyet
#### Az adatbázisban NOT NULL constraint használata (indokolható helyen): 3p
Több helyen is használtam, pl. qso táblánál törvény írja elő hogy minek kell egy bejegyzésben lenni

#### Az adatbázisban auto_increment használata: 2p
Minden tábla rendelkezik auto_increment id mezővel

#### CSS váltás (skin cserélése) az alkalmazásból: 10p
Bootstrap link cserélvel megoldva

#### Kiválasztott CSS (vagy egyéb, megjelenésre vonatkozó beállítás) felhasználónkénti tárolása: 5p
Választott téma az UserModel-ben és LoggedInUser-ben

#### Legalább két, nem triviális reguláris kifejezés használata: 5p
Controllrt validation-ben és DB-ben is szerepel


#### Felhasználó kezelés jelszóval (nem plain textben tárolva): 10p
Felhasználó jelszóval lép be, a jelszó password_hash-el van ellenőrizve, így szerveroldalon kell haszhelni, a szerver és kliens között TSL-nek kéne védeie.

#### Kettőnél több jogosultsági kör támogatása: 5p
Guest, User és DEV szinztk vanntak

#### Esztétikus megjelenés: max. 10p
Bootstrap témákkal oldottam meg