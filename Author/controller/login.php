<?php
require "../model/orm_v2.php";
session_start();
$db = new orm(['localhost', 'root', '', 'blog']);
$db->create_connection();

function validate($email, $psw)
{
    $errors = [];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email';
    }
    if (empty($psw)) {
        $errors['psw'] = 'Password Required';
    }

    return $errors;
}
function select_users($email){
    global $db;
    $select = $db->SELECT('*');
    $from = $db->from('users');
    $where = $db->where("email = '$email' ");
    $result = $db->get($select . $from . $where);
    return @$result[0];
}
function set_session($result){
    $_SESSION['user_name'] = $result['name'];
    $_SESSION['user_id'] = $result['user_id'];
    $_SESSION['user_email'] = $result['email'];
    $_SESSION['user_type'] = $result['type'];
}

$errors = [];
if ($_SERVER["REQUEST_METHOD"] == 'POST') {
    $email = $_POST['email'];
    $psw = $_POST['psw'];
    $errors = validate($email, $psw);
    if (empty($errors)) {
        $result = select_users($email);
        if ($result) {
            $db_psw = $result['password'];
            $db_type = $result['type'];
            if (password_verify($psw, $db_psw) && $db_type == 'Author') {
                set_session($result);
                header('Location:index.php');
            } else {
                $errors['login'] = "Incorrect email or password.";
            }
        } else {
            $errors['login'] = "Incorrect email or password.";
        }
    }
}

require "../view/login.html";
