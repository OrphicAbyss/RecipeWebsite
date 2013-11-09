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

// Decode json to array
$postData = json_decode(file_get_contents('php://input'), true);

switch ($postData['cmd']) {
    case 'amLoggedIn':
        if ($_SESSION['loggedin']) {
            echo '{"loggedIn": true}';
        } else {
            echo '{"loggedIn": false}';
        }
        return;

    case 'list':
        $return = array();
        $recipes = null;

        if ($_SESSION['isadmin']) { //(isset($postData['All']) || isset($postData['Deleted'])) && $_SESSION['isadmin']) {
            //All or Deleted records are returned
            //$recipes = isset($postData['All']) ? Recipe::findAll() : Recipe::findAllDeleted();
            $recipes = Recipe::findAll();
        } else if (!isset($_SESSION['userid']) || $_SESSION['userid'] == "") {
            //All public records are returned
            $recipes = Recipe::findAllPublic();
        } else {
            //All of the current users records returned
            $recipes = Recipe::findAllWithUserAndPublic($_SESSION['userid']);
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

            $return['recipes'] = $recipes;
        }
        echo json_encode($return);
        return;
        
    case 'view':
        $ID = mysql_real_escape_string($postData['id'], getConnection());
        $Hash = mysql_real_escape_string($postData['hash'], getConnection());

        $recipe = Recipe::findByHashOrId($ID, $Hash);

        $data = array();
        $data['error'] = false;
        if ($recipe == null) {
            $data['error'] = true;
            $data['message'] = "Unable to find the recipe you wanted.";
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
                    $recipe->Cmds = array("edit" => true, "delete" => true, "image" => true);
                }

                $recipe->cleanForClientSide();
                $recipe->recipeId = $ID;
                
                // TODO: Switch to using hashes to identify recipes
                $data['recipe'] = $recipe;
            } else {
                $data['error'] = true;
                $data['message'] = "You are not allowed to view this recipe. It has not been shared.";
            }
        }
        echo json_encode($data);
        return;
}

if ($_SESSION['loggedin']) {
    switch ($postData['cmd']) {
        case 'logout':
            $_SESSION['loggedin'] = false;
            $_SESSION['userid'] = null;
            $_SESSION['username'] = null;
            $_SESSION['isadmin'] = null;

            echo '{"loggedIn": false}';
            return;

        case 'updatePassword':
            $user = User::find($_SESSION['userid']);
            if ($user == null) {
                echo '{"error": true, "message": "Error changing password could not find user."}';
            } else {
                if ($postData['newPassword'] != $postData['confNewPassword']) {
                    echo '{"error": true, "message": "New password and Confirmation of new password are not the same. Please retype the passwords and try again."}';
                } else {
                    $hash = $user->hashPass($postData['password']);
                    if ($hash != $user->Pass) {
                        echo '{"error": true, "message": "Current password provided is not correct. Please retype the current password and try again."}';
                    } else {
                        $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
                        $user->Pass = mysql_real_escape_string($user->hashPass($postData['newPassword']), getConnection());
                        $user->save();
                        echo '{"error": false, "message": "Your password has been updated."}';
                    }
                }
            }
            return;
            
        case 'edit':
            $ID = mysql_real_escape_string($postData['id'], getConnection());

            $recipe = Recipe::find($ID);
            $data = array();
            $data['error'] = false;
            if ($recipe == null) {
                $data['error'] = true;
                $data['message'] = "Unable to find the recipe you wanted.";
            } else {
                $recipe->populateTags();
                $data['recipe'] = $recipe;
            }
            
            $recipe->cleanForClientSideEdit();
            $recipe->recipeId = $ID;
                
            echo json_encode($data);
            return;
            
        case 'delete':
            $ID = mysql_real_escape_string($postData['id'], getConnection());
            
            $recipe = Recipe::find($ID);
            $recipe->delete();
            
            $data = array();
            $data['error'] = false;
            echo json_encode($data);
            return;
            
        case 'save':
            $recipeToSave = new Recipe();

            $recipeData = $postData['recipe'];
            // If we are aren't a new recipe (pre-populate the object)
            if ($recipeData['ID'] != "") {
                $recipeToSave = Recipe::find(mysql_real_escape_string($recipeData['ID'], getConnection()));
            }

            $recipeToSave->Title = mysql_real_escape_string($recipeData['Title'], getConnection());
            $recipeToSave->Description = mysql_real_escape_string($recipeData['Description'], getConnection());
            $recipeToSave->Ingredients = mysql_real_escape_string($recipeData['Ingredients'], getConnection());
            $recipeToSave->Method = mysql_real_escape_string($recipeData['Method'], getConnection());
            $recipeToSave->Notes = mysql_real_escape_string($recipeData['Notes'], getConnection());
            $recipeToSave->Source = mysql_real_escape_string($recipeData['Source'], getConnection());
            $recipeToSave->Visibility = mysql_real_escape_string($recipeData['Visibility'], getConnection());
            $recipeToSave->AuthorID = $_SESSION['userid'];
            $recipeToSave->save();

            //deal with tags
            $tags = $recipeData['Tags'];
            $recipeToSave->replaceTags($tags);
            
            $data = array();
            $data['recipeId'] = $recipeToSave->ID;
            echo json_encode($data);
            return;
    }
} else {
    switch ($postData['cmd']) {
        case 'login':
            // Setup the response as if we failed to log in, if we login we will replace this message
            $data = array();
            $data['message'] = "Username or Password incorrect, please check your username and password.";

            $name = mysql_real_escape_string($postData['username'], getConnection());
            $user = User::findByName($name);
            if ($user != null) {
                if (strcmp($user->Confirmation, "") != 0) {
                    $data['message'] = "Account has not been confirmed. Check your email inbox and your spam folder for the confirmation email.";
                } else {
                    $hash = sha1($postData['password'] . $user->Salt);
                    if (strcmp($user->Pass, $hash) == 0) {
                        // TODO: save the user class instance into the session variables instead
                        $_SESSION['loggedin'] = true;
                        $_SESSION['userid'] = $user->ID;
                        $_SESSION['username'] = $user->Name;
                        $_SESSION['isadmin'] = $user->Admin;

                        $data['message'] = "";
                    } else {
                        if (config::$debug == true)
                            $data['message'] = "Password incorrect";
                    }
                }
            }

            $data['loggedIn'] = $_SESSION['loggedin'];
            $data['error'] = !$_SESSION['loggedin'];
            echo json_encode($data);
            return;
            
        case 'reset':
            $email = mysql_real_escape_string($postData['email'], getConnection());
            $user = User::findByEmail($email);

            $data = array();
            if ($user == null) {
                $data['error'] = true;
                $data['message'] = "The email provided is not registered.";
            } else {
                // Reset the choosen user
                $pass = generatePassword(8, 5);
                $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
                $user->Pass = mysql_real_escape_string($user->hashPass($pass), getConnection());

                // Email the user to let them know their new password
                mail($email, 
                        "Recipe Note: Password Reset",
                        "Your password has been reset as per your request on Recipe Note.\n\n Your new password is: $pass\n\n Log into Recipe note at: " . config::$siteURL . " and change your password after logging in with your new password.", "From: RecipeNote@gluonporridge.net");

                $user->save();

                //message to check their email for new password
                $data['error'] = false;
                $data['message'] = "Your password has been reset and emailed to you at your registered email address.";
            }
            echo json_encode($data);
            return;

        case 'register':
            $user = new User();
            $user->Name = mysql_real_escape_string($postData['username'], getConnection());
            $user->Salt = mysql_real_escape_string(sha1(time()), getConnection());
            $user->Pass = mysql_real_escape_string(sha1($postData['password'] . $user->Salt), getConnection());
            $user->Email = mysql_real_escape_string($postData['email'], getConnection());
            $user->Confirmation = mysql_real_escape_string(uniqid(), getConnection());

            $existingUser = User::findByName($user->Name);
            $data = array();
            if ($existingUser == null) {
                //send email for user to confirm email address
                mail($postData['email'], "Recipe Note: Registration Confirmation", "Thank you for registering with Recipe Note. To Complete the registration process click on the link below or copy and paste it into a new browser window:\n\n" .
                        config::$siteURL . "#confirm?username=$user->Name&confirm=$user->Confirmation\n\nIf you didn't register with Recipe Note recently please ignore this email.", "From: RecipeNote@gluonporridge.net");

                $user->save();

                //message to check their email for the confirmation email
                $data['error'] = false;
                $data['message'] = "Registration has been successful.\n\n You should receive a confirmation email shortly to compete the registration process.";
            } else {
                $data['error'] = true;
                $data['message'] = "Username may already be in use.";
            }
            echo json_encode($data);
            return;
            
        case 'confirm':
            $name = mysql_real_escape_string($postData['username'], getConnection());
            $confirm = mysql_real_escape_string($postData['confirm'], getConnection());

            $data = array();
            $user = User::findByName($name);
            if (strcmp($user->Confirmation, $confirm) == 0) {
                $user->Confirmation = "";
                $user->save();
                
                $data['error'] = false;
                $data['message'] = "Confirmation complete.\n\n You can now log in using the details you registered with.";
            } else {
                $data['error'] = true;
                $data['message'] = "Invalid confirmation details, the Username may already be confirmed or confirmation details incorrect.\n\n If you have copied and pasted the URL ensure that the address is correct and matches the address sent in the email to you.";
            }
            echo json_encode($data);
            return;
    }
}

echo '{"error": true, "message": "Unknown cmd: ' . $postData['cmd'] . '"}';
?>
