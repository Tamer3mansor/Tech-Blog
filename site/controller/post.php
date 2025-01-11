<?php
require "../model/orm_v2.php";
session_start();
$db = new orm(['localhost', 'root', '', 'blog']);
$db->create_connection();
function select_post()
{
    global $db;
    $select = $db->select('title,description,content,user_id,created_at,post_id');
    $from = $db->from('posts');
    $order_by = $db->orderby("post_id desc");
    $limits = $db->limit('1');
    $query = "";
    if (isset($_REQUEST['post_id']) || isset($_REQUEST['id'])) { // if specified post 
        $post_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : $_REQUEST['post_id'];
        $where = $db->Where("post_id = $post_id");
        $query .= $select . $from . $where;
    } else {
        $query .= $select . $from . $order_by . $limits;
    }
    $post_result = $db->get($query);
    return $post_result[0];
}
function select_comments()
{
    global $post_result;
    global $db;
    $post_id = $post_result['post_id'];
    $select = $db->select('content , created_at');
    $from = $db->from('comments');
    $where = $db->where("post_id = $post_id");
    $comments_result = $db->get($select . $from . $where);
    return $comments_result;

}
function select_user()
{
    global $db;
    global $post_result;
    $user_id = $post_result['user_id'];
    $select = $db->select('*');
    $from = $db->from('users');
    $where = $db->Where("user_id = $user_id", "");
    $query = "";
    $query .= $select . $from . $where;
    $user_result = $db->get($query);
    if (isset($user_result[0])) return $user_result[0]; else return ['name'=>"Unknown User"];
}
function select_Category()
{
    global $db;
    global $post_result;
    $post_id = $post_result['post_id'];
    $select = $db->select('*');
    $join = $db->join('category_has_posts , category', 'chp , c', 'inner join', 'chp.category_id = c.category_id');
    $where = $db->where("chp.post_id = $post_id");
    return $db->get($select . $join . $where);

}
function select_image()
{
    global $post_result;
    global $db;
    $post_id = $post_result['post_id'];
    $select = $db->select('image_path');
    $from = $db->from('images');
    $where = $db->where("post_id = $post_id");
    return $db->get($select . $from . $where);
}

$post_result = select_post();
$comments = select_comments();
$user_result = select_user();
$tags = select_category();
if (isset(select_image()[0]['image_path']))
    $image = select_image()[0]['image_path'];
require "../views/dist/post.html";
?>