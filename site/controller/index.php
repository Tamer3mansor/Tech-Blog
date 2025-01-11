<?php
require "../model/orm_v2.php";
session_start();
$db = new orm(['localhost', 'root', '', 'blog']);
$db->create_connection();
//search 
if (!empty($_REQUEST['search'])) {
    $search = $_REQUEST['search'];
    $select = $db->select("*");
    $from = $db->from("posts");
    $where = $db->Where("", "title like '%$search'");
    $order_by = $db->orderby('created_at desc');
    $query = "";
    $query .= $select . $from . $where . $order_by;
    $posts_result = $db->get($query);

}
//without search 
else
    $select = $db->select("*");
$from = $db->from("posts");
$order_by = $db->orderby('created_at desc');
$query = "";
$query .= $select . $from . $order_by;
$posts_result = $db->get($query);

require "../views/dist/index.html";

?>