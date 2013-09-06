<?php

include "config.php";

//------------------------------------------
//common methods
//------------------------------------------
if (!isset($database)) {
    $database = false;
}

/** Get a database connection, reuse a connection if it is already open */
function getConnection() {
    global $database;

    if ($database)
        return $database;

    $database = mysql_connect(config::$dbServer, config::$dbUsername, config::$dbPassword) or die('Could not connect to database server.');
    mysql_select_db('gluonpor_recipes') or die('Problem with database server.');

    return $database;
}

/** Close our database connection if it is open */
function closeConnection() {
    global $database;

    if ($database)
        mysql_close($database);

    $database = false;
}

function runQuery($query) {
    $errorMsg = config::$debug ? "<br/>\n" . $query : "";
    
    $db = getConnection();
    $result = mysql_query($query, $db) or die(mysql_error() . $errorMsg); //'Error running database query.');
    return $result;
}

//------------------------------------------
//end common methods
//------------------------------------------
?>