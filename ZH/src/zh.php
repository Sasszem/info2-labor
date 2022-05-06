<head>
    <link rel="stylesheet" href="styles.css">
</head>

<h1>Név: Baráth László, Neptun: Q6KTPF</h1>

<?php 

$db = new mysqli($_ENV['db_host'], $_ENV['db_user'], $_ENV['db_password'], $_ENV['db_db']);

function make_table($data, array $headings, array $keys) {
    echo '<table border=2>';
    echo '<tr>';
    foreach ($headings as $h)
        echo "<th>$h</th>";
    echo '</tr>';

    // ide raknám a CSS-t ha PHP-ból generálnám a class
    // ami nem lenne faca, pl. nem használhatnék sem SASS-t, sem LESS-t, sem hasonlót
    // és az adatbázisnál meg kötelező volt future proofingra gondolni so itt it fogok
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($keys as $k) {
            $v = $row[$k];
            echo "<td>$v</td>";
        }
        echo '</tr>';
    }
    echo '</table>';
}


if (array_key_exists('nev', $_GET)) {
    // olcsó megoldás: u. a. query, 1 darab extra where-el
    // lehetne előbb ID-t keresni, aztán másik queryvel ezt
    // in fact már meg is írtam ezt félig, de inkább a CSS-el foglalkoztam
    $q = $db->prepare('select d.cim cim, t.nev tulaj, ifnull(k.nev, \'(nincs kölcsön adva)\') kolcsonzo from DVD d inner join ember t on t.id = d.tulajdonos left join kolcsonzes kt on kt.dvd_id = d.id left join ember k on kt.kolcsonzo_id = k.id where k.nev = ?;');
    $q->bind_param('s', $_GET['nev']);
    $q->execute();

    make_table($q->get_result(), ['DVD', 'Tulajdonos'], ['cim', 'tulaj']);



} else {
    // nem kérte a feladat, de a debugot segíti
    // kirenderelni minden kölcsönzést
    $data = $db->query('select d.cim cim, t.nev tulaj, ifnull(k.nev, \'(nincs kölcsön adva)\') kolcsonzo from DVD d inner join ember t on t.id = d.tulajdonos left join kolcsonzes kt on kt.dvd_id = d.id left join ember k on kt.kolcsonzo_id = k.id;');
    make_table($data, ['DVD címe', 'Kié eredetileg?', 'Kinek van kölcsön adva?'], ['cim', 'tulaj', 'kolcsonzo']);
}

?>