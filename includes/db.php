<?php

$dbHost = 'localhost';
$dbName = 'arc_kitchen';
$dbUser = 'root';
$dbPass = '';

function getDbConnection(): ?mysqli
{
    global $dbHost, $dbName, $dbUser, $dbPass;

    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $connection = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);

    if ($connection->connect_error) {
        return null;
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

