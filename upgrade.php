<?php

// this script is used to upgrade the database from prestashop version 1.7.6.5 to version 1.7.8.8

// Sauvegardez les fichiers et la base de données existants
system('mysqldump -u [utilisateur] -p[mot_de_passe] [nom_de_la_base_de_données] > backup.sql');
system('tar -czvf backup.tar.gz /var/www/html/monsite');

// Téléchargez la dernière version de PrestaShop
$url = 'https://www.prestashop.com/download/old/prestashop_1.7.8.8.zip';
$file = 'prestashop_1.7.8.8.zip';
file_put_contents($file, file_get_contents($url));
$zip = new ZipArchive;
if ($zip->open($file) === TRUE) {
    $zip->extractTo('/var/www/html/monsite');
    $zip->close();
    unlink($file);
} else {
    echo 'Erreur: impossible d\'extraire le fichier ZIP.';
}

// Supprimez le répertoire d'installation
system('rm -rf /var/www/html/monsite/install');

// Vérifiez les permissions
system('chown -R www-data:www-data /var/www/html/monsite');
system('chmod -R 755 /var/www/html/monsite');

// Informations de connexion à la base de données
$ancien_db_host = 'localhost';
$ancien_db_user = 'ancien_utilisateur';
$ancien_db_pass = 'ancien_mot_de_passe';
$ancien_db_name = 'ancienne_base_de_données';
$nouvelle_db_host = 'localhost';
$nouvelle_db_user = 'nouvel_utilisateur';
$nouvelle_db_pass = 'nouveau_mot_de_passe';
$nouvelle_db_name = 'nouvelle_base_de_données';

// Connexion à l'ancienne base de données
$ancien_conn = mysqli_connect($ancien_db_host, $ancien_db_user, $ancien_db_pass, $ancien_db_name);
if (!$ancien_conn) {
    die('Erreur de connexion à l\'ancienne base de données: ' . mysqli_connect_error());
}

// Sélectionnez toutes les tables de l'ancienne base de données
$tables = array();
$result = mysqli_query($ancien_conn, 'SHOW TABLES');
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

// Connexion à la nouvelle base de données
$nouvelle_conn = mysqli_connect($nouvelle_db_host, $nouvelle_db_user, $nouvelle_db_pass, $nouvelle_db_name);
if (!$nouvelle_conn) {
    die('Erreur de connexion à la nouvelle base de données: ' . mysqli_connect_error());
}

// Copiez toutes les tables de l'ancienne base de données vers la nouvelle
foreach ($tables as $table) {
    $result = mysqli_query($ancien_conn, 'SELECT * FROM '.$table);
    $rows = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    $columns = array_keys(reset($rows));
    foreach ($rows as &$row) {
        foreach ($row as &$value) {
            $value = '\'' . mysqli_real_escape_string($nouvelle_conn, $value) . '\'';
        }
        $row = '(' . implode(',', $row) . ')';
    }
    unset($value);
    $query = 'INSERT INTO '.$table.' ('.implode(',', $columns).') VALUES '.implode(',', $rows);
    mysqli_query($nouvelle_conn, $query);
}

// Fermer les connexions à la base de données
mysqli_close($ancien_conn);
mysqli_close($nouvelle_conn);

echo 'La copie de la base de données a été effectuée avec succès!';
?>
