<?php
require "../model/orm_v2.php";
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'Author') {
    $user_id = $_SESSION['user_id'];
    $db = new orm(['localhost', 'root', '', 'blog']);
    $db->create_connection();
    if ($_SERVER["REQUEST_METHOD"] == 'POST') {
        $title = $_POST['title'];
        $brief = $_POST['brief'];
        $categories = $_POST['category'];
        $post = $_POST['post'];
        $user_id = $_SESSION['user_id'];
        $insert_post_result = $db->insert('posts', ["title" => $title, "description" => $brief, "content" => $post, "user_id" => $user_id]);
        image_upload();
        get_category($categories, $insert_post_result);
        if ($insert_post_result) {
            echo "Done";
            // header('location:manage_posts.php?id=' . $user_id);
        } else {
            echo "error";
        }
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
function insert_category_post($c_id, $p_id)
{
    global $db;
    $result = $db->insert('category_has_posts', ['category_id' => $c_id, 'post_id' => $p_id]);
    return $result;
}

function split_category($categories)
{

    // Explode the string by spaces
    $words = explode(' ', trim($categories));
    // Remove '#' from each word
    $cleaned_words = array_map(function ($word) {
        return str_replace('#', '', $word);
    }, $words);

    // Filter out empty values (in case of extra spaces)
    $categories_array = array_filter($cleaned_words);


    return $categories_array;

}
// if category exit no thing else insert it 
function get_category($category, $post_id)
{
    $categories_array = split_category($category);
    global $db;
    foreach ($categories_array as $c) {
        $select = $db->select('category_id');
        $from = $db->from('category');
        $where = $db->where("category_name = '$c' ");
        $result = $db->get($select . $from . $where);
        if ($result) {
            insert_category_post($result[0]['category_id'], $post_id);
        }
        if (!$result) {
            $result = $db->insert('category', ['category_name' => $c]);
            insert_category_post($result, $post_id);
        }
    }

}





require "../view/addpost.html";
?>