<?php

// setup session details
session_start();
// include the password generation function
include('password.php');
// include the database functions
include('common.php');
// include AjaxMessage helper class
include('AjaxMessage.php');
// include RecipeSQL
include('RecipeSQL.php');

$returnMsg = new AjaxMessage();

function getSearchURL() {
    $urlParts = array();
    if (isset($_POST['Public']))
        $urlParts[] = "Public=true";
    if (isset($_POST['Deleted']))
        $urlParts[] = "Deleted=true";
    if (isset($_POST['All']))
        $urlParts[] = "All=true";

    return "search/" . implode("&", $urlParts);
}

// Handle searching and viewing - These aren't dependant on if we are logged in or not
switch ($_POST['Cmd']) {

    case 'amLoggedIn':
        if ($_SESSION['loggedin'])
            echo '{"loggedIn": true}';
        else
            echo '{"loggedIn": false}';
        
        $returnMsg->setIgnore(true);
        break;
}

// If we aren't logged in we can: Login, Register, Reset Password or Confirm an account
if ($_SESSION['loggedin'] != true) {
    switch ($_POST['Cmd']) {
        case 'Confirm':
            $name = mysql_real_escape_string($_POST['username'], getConnection());
            $confirm = mysql_real_escape_string($_POST['confirm'], getConnection());

            $user = User::findByName($name);
            if (strcmp($user->Confirmation, $confirm) == 0) {
                $user->Confirmation = "";
                $user->save();
                $returnMsg->returnMessage("Confirmation complete.\n\n You can now log in using the details you registered with.");
            } else {
                $returnMsg->returnError("Invalid confirmation details, the Username may already be confirmed or confirmation details incorrect.\n\n If you have copied and pasted the URL ensure that the address is correct and matches the address sent in the email to you.");
            }
            break;

        default:
            //Users session may have timed out refresh their browser page
            // Only want to refresh if the user has been on a page and timed out before doing something else
            if ($returnMsg->msgType == TYPE_NONE)
                $returnMsg->returnMessage("Either does not exist or requires you to be logged in. Your session may have timed out.");

            break;
    }
    // If we are logged in we can: Log out, Update our password, Save a recipe, edit a recipe, delete a recipe, upload an image, delete an image (not yet done), Or do some admin functions if we have permissions
} else {
    switch ($_POST['Cmd']) {

        case 'GetTags':
            $tags = Tag::findAll();
            $returnMsg->setMsgType(AjaxMessage::TYPE_DATA);
            $returnMsg->setData($tags);
            break;

        case 'delete':
            $returnMsg->returnDialog("Cmd=DeleteConfirmed&ID=" . $_POST['ID'], "This action can not be reversed. Are you sure you want to delete this recipe?");
            break;

        case 'DeleteConfirmed':
            $ID = mysql_real_escape_string($_POST['ID'], getConnection());
            $AuthorID = $_SESSION['userid'];

            $recipe = Recipe::find($ID);
            if ($recipe->AuthorID == $AuthorID) {
                $recipe->Deleted = "true";
                $recipe->save();
            }

            $returnMsg->setMsgType(AjaxMessage::TYPE_CALLBACK);
            $returnMsg->setData("Cmd=search");
            break;

        case 'image':
            $ID = mysql_real_escape_string($_POST['ID'], getConnection());
            $Hash = mysql_real_escape_string($_POST['Hash'], getConnection());

            $recipe = Recipe::findByHashOrId($ID, $Hash);
            $rtnUrl = "image/" . Recipe::createHashOrIdString($_POST['ID'], $_POST['Hash']);

            if ($recipe == null) {
                $returnMsg->returnError("Could not find recipe to upload images for...");
            } else {
                $images = Image::findByRecipeID($recipe->ID);

                $returnMsg->setMsgType(AjaxMessage::TYPE_IMAGE);
                $returnMsg->setData(array("DataId" => $recipe->ID, "Title" => $recipe->Title, "Images" => $images));
                $returnMsg->setURL($rtnUrl);
            }

            break;

        case 'deleteImage':
            $returnMsg->returnDialog("Cmd=deleteImageConfirmed&ID=" . $_POST['ID'], "This action can not be reversed. Are you sure you want to delete this image?");

            break;

        case 'deleteImageConfirmed':
            $ID = mysql_real_escape_string($_POST['ID'], getConnection());

            $image = Image::find($ID);
            $image->delete();

            $returnMsg->setMsgType(AjaxMessage::TYPE_CALLBACK);
            $returnMsg->setData("Cmd=image&ID=$image->Recipe_ID");

            break;

        case 'admin':
            if (!$_SESSION['isadmin']) {
                $returnMsg->returnError("You are not an administrator.");
            } else {
                $returnMsg->setMsgType(AjaxMessage::TYPE_LIST);
                $data = array();
                $data[] = array("DataCmd" => "GetUsers", "DataParam" => "", "Item" => "View registered users");
                $data[] = array("DataCmd" => "search", "DataParam" => "All=true", "Item" => "View all recipes");
                $data[] = array("DataCmd" => "search", "DataParam" => "Deleted=true", "Item" => "View all deleted recipes");
                $data[] = array("DataCmd" => "HashAllRecipes", "DataParam" => "", "Item" => "Add hash to recipes");
                $data[] = array("DataCmd" => "SetRecipesToPrivate", "DataParam" => "", "Item" => "Update recipes to be private");
                $returnMsg->setData($data);
                $returnMsg->setURL("admin/");
            }
            break;

        case 'HashAllRecipes':
            if (!$_SESSION['isadmin']) {
                $returnMsg->returnError("You are not an administrator.");
            } else {
                $sql = "UPDATE Recipe SET Hash = sha1(ID) WHERE Hash is null OR Hash = ''";
                //run sql
                $result = runQuery($sql);
                $returnMsg->returnMessage("Updated the Hash on " . mysql_affected_rows() . " records.");
            }
            break;

        case 'SetRecipesToPrivate':
            if (!$_SESSION['isadmin']) {
                $returnMsg->returnError("You are not an administrator.");
            } else {
                $sql = "UPDATE Recipe SET Visibility = 0 WHERE Visibility is null OR Visibility = ''";
                //run sql
                $result = runQuery($sql);
                $returnMsg->returnMessage("Updated the Visibility on " . mysql_affected_rows() . " records.");
            }
            break;

        case 'GetUsers':
            if (!$_SESSION['isadmin']) {
                $returnMsg->returnError("You are not an administrator.");
            } else {
                $users = User::findAll();
                if (count($users) == 0) {
                    $returnMsg->returnError("No users in the system.");
                } else {
                    $returnMsg->setMsgType(AjaxMessage::TYPE_LIST);
                    // Clean data for displaying to the admin
                    foreach ($users as $user) {
                        $user->cleanForClientSide();
                    }
                    $returnMsg->setData($users);
                }
            }
            break;

        default:
            if ($returnMsg->msgType == TYPE_NONE)
                $returnMsg->returnError("Unknown command: " . $_POST['Cmd']);;
            break;
    }
}

closeConnection();

$returnMsg->echoMessage();
?>