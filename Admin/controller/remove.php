<?php
require "../model/orm_v2.php";
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Admin') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    $user_id = $_POST['id'];

    $result = $db->update('users',['type'=>"Reader"],["user_id"=>$user_id]);
    if ($result) {
        // $db->execute("SET FOREIGN_KEY_CHECKS=1");
        echo "Remove done !";
        header('location:index.php');
        } else
        die("error while removing");
} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}


// require "index.php";
