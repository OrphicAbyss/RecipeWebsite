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
    case 'search':
        $return = array();
        $recipes = null;

        if ((isset($_POST['All']) || isset($_POST['Deleted'])) && $_SESSION['isadmin']) {
            //All or Deleted records are returned
            $recipes = isset($_POST['All']) ? Recipe::findAll() : Recipe::findAllDeleted();
        } else if (isset($_POST['Public']) || !isset($_SESSION['userid']) || $_SESSION['userid'] == "") {
            //All public records are returned
            $recipes = Recipe::findAllPublic();
            $return['Type'] = "Public";
        } else {
            //All of the current users records returned
            $recipes = Recipe::findAllWithUser($_SESSION['userid']);
            $return['Type'] = "Private";
        }

        if ($recipes != null) {
            // Remove unneeded data
            foreach ($recipes as $recipe) {
                unset($recipe->Hash);
                unset($recipe->Ingredients);
                unset($recipe->Method);
                unset($recipe->Notes);
                unset($recipe->Source);

                $recipe->populateTags();
                $recipe->populateImages();
                $recipe->populateAuthor();
                $recipe->Deleted = ($recipe->Deleted == 1 ? "True" : "False");
                $recipe->Visibility = RecipeDB::visibilityCodeToString($recipe->Visibility);

                $recipe->Author->cleanForClientSide();
                unset($recipe->AuthorID);
            }

            $return['Recipes'] = $recipes;
            $returnMsg->setMsgType(AjaxMessage::TYPE_LIST_RECIPE);
            $returnMsg->setData($return);
            $returnMsg->setURL(getSearchURL());
        } else {
            $returnMsg->returnError("Search returned no results.");
        }
        break;

    case 'view':
        $ID = mysql_real_escape_string($_POST['ID'], getConnection());
        $Hash = mysql_real_escape_string($_POST['Hash'], getConnection());

        $recipe = Recipe::findByHashOrId($ID, $Hash);
        $rtnUrl = "view/" . Recipe::createHashOrIdString($_POST['ID'], $_POST['Hash']);

        $data = array();
        $data['Error'] = false;
        if ($recipe == null) {
            $data['Error'] = true;
            $data['Message'] = "Unable to find the recipe you wanted.";
        } else {
            //we have a recipe are we allowed to view it
            if ($_SESSION['isadmin'] || // we can if we are an admin
                    ($recipe->Visibility == 0 && $_SESSION['userid'] == $recipe->AuthorID) || // we can if it is our recipe
                    $recipe->Visibility > 0) {             // we can if the recipe is shared
                $Hash = $recipe->Hash;
                $ID = $recipe->ID;

                $recipe->populateAuthor();
                $recipe->populateTags();
                $recipe->populateImages();

                if ($recipe->AuthorID == $_SESSION['userid']) {
                    // If it is the current users recipe, allow them to edit it
                    $recipe->DataCmd = array(array("cmd" => "edit", "text" => "Edit recipe"), array("cmd" => "delete", "text" => "Delete recipe"), array("cmd" => "image", "text" => "Manage recipe images"));
                    $recipe->DataId = $ID;
                }

                $recipe->cleanForClientSide();

                // TODO: Switch to using hashes to identify recipes
                $data['Recipe'] = $recipe;
            } else {
                $data['Error'] = true;
                $data['Message'] = "You are not allowed to view this recipe. It has not been shared.";
            }
        }
        echo json_encode($data);
        $returnMsg->setIgnore(true);
        break;
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
        case 'Login':
            // Setup the response as if we failed to log in, if we login we will replace this message
            $data = array();
            $data['Message'] = "Username or Password incorrect or Account has not been confirmed.";

            $name = mysql_real_escape_string($_POST['user'], getConnection());
            $user = User::findByName($name);
            if ($user != null) {
                if (strcmp($user->Confirmation, "") != 0) {
                    $data['Message'] = "Account has not been confirmed. Check your email inbox and your spam folder for the confirmation email.";
                } else {
                    $hash = sha1($_POST['pass'] . $user->Salt);
                    if (strcmp($user->Pass, $hash) == 0) {
                        // TODO: save the user class instance into the session variables instead
                        $_SESSION['loggedin'] = true;
                        $_SESSION['userid'] = $user->ID;
                        $_SESSION['username'] = $user->Name;
                        $_SESSION['isadmin'] = $user->Admin;
                        
                        $data['Message'] = "";
                    }
                }
            }
            
            $data['loggedIn'] = $_SESSION['loggedin'];
            $data['Error'] = !$_SESSION['loggedin'];
            echo json_encode($data);
            $returnMsg->setIgnore(true);//setData($data);
            break;

        case 'ResetPass':
            $email = mysql_real_escape_string($_POST['Email'], getConnection());
            $user = User::findByEmail($email);

            if ($user == null) {
                $returnMsg->returnText("responseReset", "The email provided is not registered.");
            } else {
                // Reset the choosen user
                $pass = generatePassword(8, 5);
                $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
                $user->Pass = mysql_real_escape_string($user->hashPass($pass), getConnection());

                // Email the user to let them know their new password
                mail($email, "Recipe Note: Password Reset", "Your password has been reset as per your request on Recipe Note.\n\n Your new password is: $pass\n\n Log into Recipe note at: http://www.gluonporridge.net/recipetest/ and change your password after logging in with your new password.", "From: RecipeNote@gluonporridge.net");

                $user->save();

                //message to check their email for new password
                $returnMsg->returnMessage("Your password has been reset and emailed to you at your registered email address.");
            }
            break;

        case 'Register':
            $user = new User();
            $user->Name = mysql_real_escape_string($_POST['user'], getConnection());
            $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
            $user->Hash = mysql_real_escape_string(sha1($_POST['pass'] . $user->Salt), getConnection());
            $user->Email = mysql_real_escape_string($_POST['Email'], getConnection());
            $user->Confirmation = mysql_real_escape_string(uniqid(), getConnection());

            $existingUser = User::findByName($user->Name);
            if ($existingUser != null) {
                //send email for user to confirm email address
                mail($_POST['Email'], "Recipe Note: Registration Confirmation", "Thank you for registering with Recipe Note. To Complete the registration process click on the link below or copy and paste it into a new browser window:\n\n" .
                        "http://www.gluonporridge.net/recipetest/#username=$user->Name&confirm=$user->Confirmation\n\nIf you didn't register with Recipe Note recently please ignore this email.", "From: RecipeNote@gluonporridge.net");

                $user->save();

                //message to check their email for the confirmation email
                $returnMsg->returnMessage("Registration has been successful.\n\n You should receive a confirmation email shortly to compete the registration process.");
            } else {
                $returnMsg->returnError("Username may already be in use.");
            }
            break;

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
        case 'Logout':
            $_SESSION['loggedin'] = false;
            $_SESSION['userid'] = null;
            $_SESSION['username'] = null;
            $_SESSION['isadmin'] = null;

            echo '{"loggedIn": false}';
            $returnMsg->setIgnore(true);
            break;

        case 'UpdatePassword':
            $user = User::find($_SESSION['userid']);
            if ($user == null) {
                $returnMsg->returnError("Error changing password could not find user.");
            } else {
                if ($_POST['newPass'] != $_POST['confNewPass']) {
                    $returnMsg->returnError("New password and Confirmation of new password are not the same. Please retype the passwords and try again.");
                } else {
                    $hash = $user->hashPass($_POST['pass']);
                    if ($hash != $user->Pass) {
                        $returnMsg->returnError("Current password provided is not correct. Please retype the current password and try again.");
                    } else {
                        $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
                        $user->Pass = mysql_real_escape_string($user->hashPass($_POST['newPass']), getConnection());
                        $user->save();
                        $returnMsg->returnMessage("Your password has been updated.");
                    }
                }
            }
            break;

        case 'SaveRecipe':
            $recipeToSave = new Recipe();

            // If we are aren't a new recipe (pre-populate the object)
            if ($_POST['ID'] != "") {
                $recipeToSave = Recipe::find(mysql_real_escape_string($_POST['ID'], getConnection()));
            }

            $recipeToSave->Title = mysql_real_escape_string($_POST['Title'], getConnection());
            $recipeToSave->Description = mysql_real_escape_string($_POST['Description'], getConnection());
            $recipeToSave->Ingredients = mysql_real_escape_string($_POST['Ingredients'], getConnection());
            $recipeToSave->Method = mysql_real_escape_string($_POST['Method'], getConnection());
            $recipeToSave->Notes = mysql_real_escape_string($_POST['Notes'], getConnection());
            $recipeToSave->Source = mysql_real_escape_string($_POST['Source'], getConnection());
            $recipeToSave->Visibility = mysql_real_escape_string($_POST['Visibility'], getConnection());
            $recipeToSave->AuthorID = $_SESSION['userid'];
            $recipeToSave->save();

            $returnMsg->setMsgType(AjaxMessage::TYPE_CALLBACK);
            $returnMsg->setData("Cmd=view&ID=" . $recipeToSave->ID);

            // easiest way to update tags is to delete the current ones in the database for the recipe and re-insert the ones on the form
            $sql = "DELETE FROM RecipeTags WHERE RecipeID = $recipeToSave->ID";
            // Delete all the old tag links before we insert the new ones
            $result = runQuery($sql);

            //deal with tags
            $tags = explode(",", $_POST['Tags']);
            $recipeToSave->replaceTags($tags);

            break;

        case 'edit':
            $ID = mysql_real_escape_string($_POST['ID'], getConnection());

            $recipe = Recipe::find($ID);
            $data = array();
            $data['Error'] = false;
            if ($recipe == null) {
                $data['Error'] = true;
                $data['Message'] = "Unable to find the recipe you wanted.";
            } else {
                $recipe->populateTags();
                $data['Recipe'] = $recipe;
            }
            echo json_encode($data);
            $returnMsg->setIgnore(true);
            break;

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