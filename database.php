<?php

$connection = mysqli_init();
if (!$connection) {
    die('Database connection failed');
}

if (!mysqli_real_connect($connection, 'localhost', 'root', 'password', 'fr-database', 0, NULL, MYSQLI_CLIENT_FOUND_ROWS)) {
    die('Database connection error');
}
