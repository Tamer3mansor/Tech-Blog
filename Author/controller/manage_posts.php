<?php
require "../model/orm_v2.php";
session_start();
if (isset($_SESSION['user_id'])  && $_SESSION['user_type'] == 'Author') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    $user_id = $_REQUEST['id'];
    $select = $db->select("*");
    $from = $db->from("posts");
    $where = $db->where("user_id = $user_id", "");
    $query = "";
    $query .= $select . $from . $where;
    // echo $query;
    $posts = $db->get($query);
    // $posts_result = $posts_result[0];
    // print_r($posts_result);

} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}

require "../view/manage_posts.html";

?>