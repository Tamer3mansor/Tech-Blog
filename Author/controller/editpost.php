<?php
require "../model/orm_v2.php";
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Author') {
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    $post_id = $_REQUEST['id'];
    $post_result = select_post();
    $category_result = select_Category();
    $categories = '';
    if ($category_result) {
        $category_result = array_column($category_result, 'category_name');
        $modifiedArray = array_map(function ($item) {
            return '#' . $item;
        }, $category_result);
        $categories = implode('  ', $modifiedArray);
    }
    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
        update_post();
        update_all_category(category: $_POST['category']); // pass string 
        image_upload();
        header('location:index.php');

    }

} else {
    header('location:login.php');
    $user_name = NULL;
    $user_id = null;
    $user_type = null;
}
function image_upload()
{
    global $db;
    global $insert_post_result;
    $uploads_dir = '../../uploads/';
    $image_name = $_FILES['image']['name'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_size = $_FILES['image']['size'];
    $image_error = $_FILES['image']['error'];
    if ($image_error === 0) {
        if ($image_size <= 2 * 1024 * 1024) {
            $image_extension = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_new_name = uniqid("IMG-", true) . '.' . $image_extension;
            $image_upload_path = $uploads_dir . $image_new_name;
            if (move_uploaded_file($image_tmp_name, $image_upload_path)) {
                $result = $db->insert('images', ["image_path" => "'" . $image_upload_path . "'", "post_id" => $insert_post_result]);
            } else {
                echo "error in moving image";
            }
        } else {
            echo 'file size over';
        }
    } else {
        echo 'error while uploading image';
    }
}

function split_category($categories)
{
    $words = explode(' ', trim($categories));
    // Remove '#' from each word
    $categories_array = array_map(function ($word) {
        return str_replace('#', '', $word);
    }, $words);
    // Filter out empty values (in case of extra spaces)
    return $categories_array;
}

function update_category_post($c_id, $p_id)
{
    global $db;
    $result = $db->insert('category_has_posts', ['category_id' => $c_id, 'post_id' => $p_id]);
    return $result;
}
function update_all_category($category)
{
    global $db, $post_id;
    $categories_array = split_category($category); // pass string  return array
    foreach ($categories_array as $c) {
        $select = $db->select('category_id');
        $from = $db->from('category');
        $where = $db->where("category_name = '$c' ");
        $result = $db->get($select . $from . $where);
        // if ($result[0]['category_id']) {
        //     update_category_post($result[0]['category_id'], $post_id);
        // }
        if (!$result) {
            $result = $db->insert('category', ['category_name' => $c]);
            update_category_post($result, $post_id);
        }
    }

}
function update_post()
{
    $title = $_POST['title'];
    $brief = $_POST['brief'];
    $post = $_POST['post'];
    $user_id = $_SESSION['user_id'];
    global $db, $post_id;
    $post_updated = $db->update('posts', ["title" => $title, "description" => $brief, "content" => $post], ["post_id" => $post_id]);
    return $post_updated;
}
function select_Category()
{
    global $db;
    global $post_result;
    $post_id = $post_result['post_id'];
    $select = $db->select('category_name');
    $join = $db->join('category_has_posts , category', 'chp , c', 'inner join', 'chp.category_id = c.category_id');
    $where = $db->where("chp.post_id = $post_id");
    return $db->get($select . $join . $where);

}
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

require "../view/editpost.html";
?>