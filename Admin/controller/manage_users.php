<?php
require "../model/orm_v2.php";
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Admin') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    $select = $db->SELECT('name,email,user_id,type');
    $from = $db->from('users');
    $where = $db->where("type = 'Author'");
    $users = $db->get($select . $from . $where);
} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}


require "../view/manage_users.html";
