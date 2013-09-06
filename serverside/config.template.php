<?php

/**
 * Configuration class holding data which needs to be populated for the server
 * the site is running on.
 * 
 * Copy and rename this file to "config.php" in the same folder and fill in
 * the variables. ENSURE THAT YOU DON'T CHECK THIS INTO GIT.
 */
class config {
    //database config
    public static $dbServer = "";
    public static $dbUsername = "";
    public static $dbPassword = "";
    
    //site config
    public static $siteURL = "http://www.example.com/";
    
    //debug
    public static $debug = false;
}

?>
