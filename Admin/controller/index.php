<?php
require "../model/orm_v2.php";
session_start();
function get_users_count()
{
    global $db;
    $users_result = $db->get('SELECT count(*) as count from users where type = "Author"');
    return $users_result[0];
}
function get_posts_count()
{
    global $db;
    $posts_result = $db->get('SELECT count(*) as count from posts');
    return $posts_result[0];
}
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Admin') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    //
    $posts_count = get_posts_count()['count'];
    $users_count = get_users_count()['count'];
} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}

require "../view/Home.html";
