<?php

class mysqlHelper {

    /**
     * Run an sql query expecting one row and return the object based on the specifed classs
     * 
     * @param String $sql SQL to be run
     * @param String $className Name of the class to pack results into
     */
    public static function get_mysql_obj($sql, $className) {
        $sqlResult = runQuery($sql);
        $num = mysql_num_rows($sqlResult);
        if ($num > 1) {
            die("Expected one row from query, got multiple.");
        }
        return mysql_fetch_object($sqlResult, $className);
    }

    /**
     * Run an sql query and convert the resultset into an array of the specified class
     * 
     * @param String $sql SQL to be run
     * @param String $className Name of the class to pack the results into
     * @return Array 
     */
    public static function get_mysql_obj_array($sql, $className) {
        $sqlResult = runQuery($sql);
        $result = array();
        while ($resultObj = mysql_fetch_object($sqlResult, $className)) {
            $result[] = $resultObj;
        }
        return $result;
    }

    /**
     * Run an sql query and convert the resultset into an array of the specified column
     * 
     * @param String $sql SQL to be run
     * @param String $colName Name of the column to extract and pack into an array
     * @return Array 
     */
    public static function get_mysql_str_array($sql, $colName) {
        $sqlResult = runQuery($sql);
        $result = array();
        while ($resultObj = mysql_fetch_assoc($sqlResult)) {
            $result[] = $resultObj[$colName];
        }
        return $result;
    }

}

abstract class DBRecord {

    public $ID;

    /**
     * Will insert a new record or update an existing record in the database
     */
    public function save() {
        if ($this->ID == null) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    abstract public function insert();

    abstract public function update();
}

/**
 * Represents an image record in the database 
 */
class Image extends DBRecord {

    public $Recipe_ID;
    public $Author_ID;
    public $Filename;
    public $FilenameServer;
    public $Description;
    public $Deleted;

    public function __construct() {
        $args = func_get_args();
        if (count($args) == 5) {
            $this->ID = null;
            $this->Recipe_ID = $args[0];
            $this->Author_ID = $args[1];
            $this->Filename = $args[2];
            $this->FilenameServer = $args[3];
            $this->Description = $args[4];
        }
    }

    /**
     * Find the image record with the provided ID
     * 
     * @param int $ID The id of the image
     * @return Image The image record
     */
    public static function find($ID) {
        $getSql = "SELECT * FROM Images WHERE ID = $ID";
        return mysqlHelper::get_mysql_obj($getSql, 'Image');
    }

    /**
     * Find all the image records for the given recipe id
     * 
     * @param int $ID Recipe ID
     * @return Image[] All the images attached to the given recipe id
     */
    public static function findByRecipeID($ID) {
        $getImageSql = "SELECT * FROM Images WHERE Recipe_ID = $ID AND Deleted = false";
        return mysqlHelper::get_mysql_obj_array($getImageSql, 'Image');
    }

    public function insert() {
        if ($this->ID != null) {
            die("Trying to insert image which already has ID\nID: $this->ID Recipe_ID: $this->Recipe_ID Filename: $this->Filename Server filename: $this->FilenameServer\n");
        }

        $insertImageSql = "INSERT INTO Images(Recipe_ID, Author_ID, Filename, FilenameServer, Description) " .
                "VALUES ($this->Recipe_ID, $this->Author_ID, '$this->Filename', '$this->FilenameServer', '$this->Description')";
        runQuery($insertImageSql);
        $this->ID = mysql_insert_id();
    }

    public function update() {
        if ($this->ID == null) {
            die("Trying to update image which doesn't have an ID\nID: $this->ID Recipe_ID: $this->Recipe_ID Filename: $this->Filename Server filename: $this->FilenameServer\n");
        }

        $updateImageSql = "UPDATE Images " .
                "SET Recipe_ID=$this->Recipe_ID, " .
                "    Author_ID=$this->Author_ID, " .
                "    Filename='$this->Filename', " .
                "    FilenameServer='$this->FilenameServer', " .
                "    Description='$this->Description' " .
                "WHERE ID=$this->ID";
        runQuery($updateImageSql);
    }

    public function delete() {
        if ($this->ID == null) {
            die("Trying to delete image which doesn't have an ID\nID: $this->ID Recipe_ID: $this->Recipe_ID Filename: $this->Filename Server filename: $this->FilenameServer\n");
        }

        $deleteImageSql = "UPDATE Images " .
                "Set Deleted=true " .
                "WHERE ID=$this->ID";

        runQuery($deleteImageSql);
    }

}

/**
 * Represents a row from the Tags table
 */
class Tag extends DBRecord {

    public $Tag;

    public function __construct() {
        $args = func_get_args();
        if (count($args) == 1) {
            $this->ID = null;
            $this->Tag = $args[0];
        }
    }

    /**
     * Find the Tag record for the ID provided
     * 
     * @param int $ID The ID of the record
     * @return Tag The tag for the provided ID
     */
    public static function find($ID) {
        $sql = "SELECT * FROM Tags WHERE Tags.ID = $ID";
        return mysqlHelper::get_mysql_obj($sql, 'Tag');
    }

    /**
     * Find all Tag records in the database
     * 
     * @return Tag[] All tag records
     */
    public static function findAll() {
        $sql = "SELECT * FROM Tags";
        return mysqlHelper::get_mysql_obj_array($sql, 'Tag');
    }

    /**
     * Find a tag record based on the tag string
     * 
     * @param String $tag The tag string
     * @return Tag The tag record for the tag string
     */
    public static function findByTag($tag) {
        $sql = "SELECT * FROM Tags WHERE Tag='$tag'";
        return mysqlHelper::get_mysql_obj($sql, 'Tag');
    }

    /**
     * Will return the matching records for the provided tag strings
     * 
     * @param String[] $tags An array of tag stings to find
     * @return Tag[] The tags that match
     */
    public static function findByTags($tags) {
        $tagArray = explode(",", $tags);
        $tagList = "";
        foreach ($tagArray as $tag) {
            $tagList .= "'" . $tag . "',";
        }
        $tagList = rtrim($tagList, ',');

        $sql = "SELECT * FROM Tags WHERE Tag in ($tagList)";
        return mysqlHelper::get_mysql_obj_array($sql, 'Tag');
    }

    /**
     * Given a recipe ID return all tag records which are linked to it
     * 
     * @param int $ID The id of the recipe
     * @return Tag The tags for the provided recipe
     */
    public static function findByRecipeId($ID) {
        $getTagSql = "SELECT Tags.* " .
                "FROM Tags, RecipeTags " .
                "WHERE RecipeTags.RecipeID = $ID " .
                "AND RecipeTags.TagID = Tags.ID";
        return mysqlHelper::get_mysql_obj_array($getTagSql, 'Tag');
    }

    public function insert() {
        if ($this->ID != null) {
            die("Trying to insert tag which already has ID\nID: $this->ID Tag: $this->Tag\n");
        }

        $insertTagSql = "INSERT INTO Tags (Tag) VALUES ('" . trim($this->Tag) . "')";
        runQuery($insertTagSql);
        $this->ID = mysql_insert_id();
    }

    public function update() {
        die("Trying to update a tag value. Tags should not be updated once inserted into the database.");
    }

}

/**
 * Represents a row from the Recipe table
 */
class Recipe extends DBRecord {

    public $Hash;
    public $Title;
    public $Description;
    public $Ingredients;
    public $Method;
    public $Notes;
    public $AuthorID;
    public $Source;
    public $Deleted;
    public $Visibility;
    public $LastEdited;
    public $Tags;
    public $Images;
    public $Author;

    public function __construct() {
        
    }

    /**
     * Find a recipe record based on the passed in ID
     * 
     * @param int $id The recipe id
     * @return Recipe that matched the ID
     */
    public static function find($id) {
        $getRecipeSql = "SELECT * FROM Recipe WHERE ID='$id'";
        return mysqlHelper::get_mysql_obj($getRecipeSql, "Recipe");
    }

    /**
     * Find a recipe record based on the passed in Hash value
     * 
     * @param string $hash The hash value of a recipe
     * @return Recipe that matched the hash 
     */
    public static function findByHash($hash) {
        $getRecipeSql = "SELECT * FROM Recipe WHERE Hash='$hash'";
        return mysqlHelper::get_mysql_obj($getRecipeSql, "Recipe");
    }

    /**
     * Find a recipe record based on the passed in Id or Hash values
     * 
     * If both an Id and Hash value are passed in the ID value is used in preference.
     * 
     * @param string $id The ID of a recipe record
     * @param string $hash The hash value of a recipe record
     * @return Recipe The recipe record
     */
    public static function findByHashOrId($id, $hash) {
        $sql = "";
        if ($id != "" && $id != null) {
            $sql = "SELECT * FROM Recipe WHERE ID='$id'";
        } else {
            $sql = "SELECT * FROM Recipe WHERE Hash='$hash'";
        }
        return mysqlHelper::get_mysql_obj($sql, "Recipe");
    }

    /**
     * Returns a string with ID=idvalue or Hash=hashvalue where the ID one is returned if ID is
     * populated if the passed in id paramater is populated or the Hash one is returned if the 
     * hash parameter is populated. The ID string is prefered if both are populated.
     * 
     * @param string $id An id value
     * @param string $hash A hash value
     * @return string A string for passing parameters
     */
    public static function createHashOrIdString($id, $hash) {
        return ($id != "" && $id != null) ? "ID=$id" : "HASH=$hash";
    }

    /**
     * Return all recipe records in the database
     * 
     * @return Recipe[] all recipe records.
     */
    public static function findAll() {
        $getRecipeSql = "SELECT * FROM Recipe";
        return mysqlHelper::get_mysql_obj_array($getRecipeSql, 'Recipe');
    }

    /**
     * Return all deleted recipe records in the database
     * 
     * @return Recipe[] all deleted recipe records.
     */
    public static function findAllDeleted() {
        $getRecipeSql = "SELECT * FROM Recipe WHERE Deleted=true";
        return mysqlHelper::get_mysql_obj_array($getRecipeSql, 'Recipe');
    }

    /**
     * Return all public recipe records in the database
     * 
     * @return Recipe[] all public recipe records.
     */
    public static function findAllPublic() {
        $sql = "SELECT * FROM Recipe WHERE Visibility = 2 AND Deleted=false";
        return mysqlHelper::get_mysql_obj_array($sql, 'Recipe');
    }

    /**
     * Find all of the recipes by a perticular author
     * 
     * @param int $UserID The Id of the author to find the recipes of
     * @return Recipe[] all recipes by the provided author id
     */
    public static function findAllWithUser($UserID) {
        $sql = "SELECT * FROM Recipe WHERE Deleted=false AND AuthorID = " . $UserID;
        return mysqlHelper::get_mysql_obj_array($sql, 'Recipe');
    }

    /**
     * Return's all recipe records in the database with the restriction sql statements added to
     * limit the results
     * 
     * @param string[] $restrictions Array of strings which are sql restrictions
     * @return Recipe[] Recipe records found
     */
    public static function findAllWithRestrictions($restrictions) {
        $sql = "SELECT * FROM Recipe WHERE " . join(" AND ", $restrictions);
        return mysqlHelper::get_mysql_obj_array($sql, 'Recipe');
    }

    /**
     * Will load in the tag records for the recipe
     */
    public function populateTags() {
        $this->Tags = Tag::findByRecipeId($this->ID);
    }

    /**
     * Will load in the image records for the recipe
     */
    public function populateImages() {
        $this->Images = Image::findByRecipeID($this->ID);
    }

    /**
     * Will load in the author record for the recipe
     */
    public function populateAuthor() {
        $this->Author = User::find($this->AuthorID);
    }

    public function cleanForClientSide() {
        // Replace author record with name
        if ($this->Author != null) {
            $this->Author = $this->Author->Name;
        }
        // Replace tag records with tag array
        if ($this->Tags != null) {
            $tagList = array();
            foreach ($this->Tags as $tag) {
                array_push($tagList, $tag->Tag);
            }
            $this->Tags = $tagList;
        }

        $this->Visibility = RecipeDB::visibilityCodeToString($this->Visibility);

        unset($this->Deleted);
        unset($this->AuthorID);
        unset($this->LastEdited);
    }
    
    public function cleanForClientSideEdit() {
        // Replace author record with name
        if ($this->Author != null) {
            $this->Author = $this->Author->Name;
        }
        // Replace tag records with tag array
        if ($this->Tags != null) {
            $tagList = array();
            foreach ($this->Tags as $tag) {
                array_push($tagList, $tag->Tag);
            }
            $this->Tags = $tagList;
        }

        unset($this->Deleted);
        unset($this->AuthorID);
        unset($this->LastEdited);
    }

    /**
     * Replace the tags of a recipe with a new list of tags
     * 
     * @param Array $newTags An array of tags to mark against the recipe
     */
    public function replaceTags($newTags) {
        // start of tag insert sql
        $tagInsertSql = "INSERT INTO RecipeTags(RecipeId,TagId) VALUES";

        foreach ($newTags as $tag) {
            $sqlTag = mysql_real_escape_string(trim($tag), getConnection());
            $tagObj = Tag::findByTag($sqlTag);

            if ($tagObj == null) {
                $tagObj = new Tag($sqlTag);
                $tagObj->save();
            }
            $tagId = $tagObj->ID;
            // Run Tag insert sql
            runQuery($tagInsertSql . " ($this->ID,$tagId)");
        }
    }

    /**
     * Will insert the record into the database
     */
    public function insert() {
        if ($this->ID != null) {
            die("Trying to insert recipe which already has ID\nID: $this->ID Tag: $this->Title\n");
        }

        $sql = "INSERT INTO Recipe (Title, Description, Ingredients, Method, Notes, AuthorID, Source, Visibility, LastEdited) " .
                "VALUES ('$this->Title', '$this->Description', '$this->Ingredients', '$this->Method', '$this->Notes', $this->AuthorID, '$this->Source', '$this->Visibility', NOW())";
        $result = runQuery($sql);
        $this->ID = mysql_insert_id();
        $this->Hash = sha1($this->ID);
        // add hash
        $sql = "UPDATE Recipe SET Hash = '$this->Hash' WHERE ID = $this->ID";
        $result = runQuery($sql);

        // save tags
        // TODO: Save tags for recipe (insert)
    }

    /**
     * Will update the values of the record in the database
     */
    public function update() {
        if ($this->ID == null) {
            die("Trying to update recipe which already has ID\nID: $this->ID Tag: $this->Title\n");
        }

        $sql = "UPDATE Recipe " .
                "SET Title='$this->Title', " .
                "    Description='$this->Description', " .
                "    Ingredients='$this->Ingredients', " .
                "    Method='$this->Method', " .
                "    Notes='$this->Notes', " .
                "    Source='$this->Source', " .
                "    Visibility='$this->Visibility', " .
                "    LastEdited=NOW(), " .
                "    Deleted=$this->Deleted " .
                "WHERE ID=$this->ID";
        runQuery($sql);

        // save tags
        // TODO: Save tags for recipe (update)
    }

    /**
     * Will delete the record from the database
     */
    public function delete() {
        // TODO: Delete recipe if record has been marked deleted and deleted linked data in other tables
    }

}

class User extends DBRecord {

    public $Name;
    public $Pass;
    public $Salt;
    public $Confirmation;
    public $Email;
    public $Admin;

    /**
     * Return a record from the database based on the passed in id.
     * 
     * @param int $id The id of the record
     * @return User The user record
     */
    public static function find($id) {
        $sql = "SELECT * FROM User WHERE id=$id";
        return mysqlHelper::get_mysql_obj($sql, "User");
    }

    /**
     * Get all the user records from the database
     * 
     * @return User Array of the user records in the database
     */
    public static function findAll() {
        $sql = "SELECT * FROM User";
        return mysqlHelper::get_mysql_obj_array($sql, "User");
    }

    /**
     * Get the user record for the provided username
     * 
     * @param string $name The username of the user
     * @return User The user with the provided username
     */
    public static function findByName($name) {
        $sql = "SELECT * FROM User WHERE upper(Name) = upper('$name')";
        return mysqlHelper::get_mysql_obj($sql, "User");
    }

    /**
     * Get the user record for the provided email address
     * 
     * @param string $email The email address of the user
     * @return User The user with the provided email address 
     */
    public static function findByEmail($email) {
        $sql = "SELECT * FROM User WHERE upper(Email) = upper('$email')";
        return mysqlHelper::get_mysql_obj($sql, "User");
    }

    public function cleanForClientSide() {
        $this->Admin = ($this->Admin == 1 ? 'true' : 'false');
        $this->Confirmation = ($user->Confirmation == '' ? 'true' : 'false');
        unset($this->Pass);
        unset($this->Salt);
    }

    /**
     * Converts a password into a hash for checking using the users salt
     * 
     * @param String $pass The password to convert to a hash
     * @return String The hash of the password provided based on this users salt
     */
    public function hashPass($pass) {
        return sha1($pass . $this->Salt);
    }

    public function insert() {
        $sql = "INSERT User (Name, Pass, Salt, Confirmation, Email, Admin) " .
                "VALUES('$this->Name', '$this->Pass', '$this->Salt', '$this->Confirmation', '$this->Email', '$this->Admin')";
        $result = runQuery($sql);
        $this->ID = mysql_insert_id();
    }


    
    public function update() {
        $sql = "UPDATE User " .
                "SET Pass='$this->Pass', Salt='$this->Salt', Confirmation='$this->Confirmation' " .
                "WHERE ID=" . $this->ID;
        runQuery($sql);
    }

}

/**
 * Class to handle all recipe database calls for CRUD
 */
class RecipeDB {

    const TYPE_VIS_PRIVATE = 0;
    const TYPE_VIS_LINK_SHARE = 1;
    const TYPE_VIS_PUBLIC = 2;

    /**
     * Convert a visibility code to a string
     * 
     * @param $code the visibility code
     */
    public static function visibilityCodeToString($code) {
        switch ($code) {
            case self::TYPE_VIS_PRIVATE:
                return "Private";
            case self::TYPE_VIS_LINK_SHARE:
                return "Link Share";
            case self::TYPE_VIS_PUBLIC:
                return "Public";
            default:
                return "Unknown";
        }
    }

}

?>
