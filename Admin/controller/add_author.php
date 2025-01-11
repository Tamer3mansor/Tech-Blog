<?php
require "../model/orm_v2.php";
session_start();
$errors = [];
function validate($name, $email, $psw, $psw_rep)
{
    $errors = [];
    if (empty($name)) {
        $errors['name'] = 'Enter Name';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email';
    }
    if (strlen($psw) < 10) {
        $errors['psw'] = 'Password length must be more than 10 characters';
    }
    if ($psw !== $psw_rep) {
        $errors['psw_rep'] = 'Passwords do not match';
    }
    return $errors;
}
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Admin') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $psw = $_POST['psw'];
        $psw_rep = $_POST['psw-repeat'];
        $type = 'Author';
        $errors = validate($name, $email, $psw, $psw_rep);
        $hash = password_hash($psw, PASSWORD_DEFAULT);
        if (empty($errors)) {
            $result = $db->insert('users', ["name" => $name, "email" => $email, "password" => $hash, "type" => 'Author']);
            if ($result == 'Duplicate email')
                $errors['email'] = 'This email used before';
            if ($result == 1) {
                header('location:index.php');
            }

        }
    }
} else {
    echo "create user error";
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}




require "../view/add_author.html";

