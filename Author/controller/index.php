<?php
require "../model/orm_v2.php";
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Author' ) {
    $user_name = $_SESSION['user_name'];
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    //
    $posts_result = $db->get("SELECT count(*) as count from posts WHERE user_id = $user_id");
    $posts_result = $posts_result[0];
    $posts_count = $posts_result['count'];
} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}

require "../view/Home.html";
