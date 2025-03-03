<?php
/**
 * Plugin Name: Auto YouTube Thumbnail as Featured Image
 * Description: Automatically sets the embedded YouTube video thumbnail as the featured image for posts.
 * Version: 1.1
 * Author: David Mitchell
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function get_youtube_thumbnail($post_content) {
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/', $post_content, $matches)) {
        $video_id = $matches[1];
        $quality_levels = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($quality_levels as $quality) {
            $thumbnail_url = "https://img.youtube.com/vi/$video_id/$quality.jpg";
            $headers = get_headers($thumbnail_url, 1);

            if (strpos($headers[0], '200') !== false) {
                return $thumbnail_url;
            }
        }
    }
    return false;
}

function set_youtube_thumbnail_as_featured($post_id) {
    if (get_post_type($post_id) !== 'post') {
        return;
    }
    
    if (has_post_thumbnail($post_id)) {
        return;
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return;
    }
    
    $thumbnail_url = get_youtube_thumbnail($post->post_content);
    if (!$thumbnail_url) {
        return;
    }
    
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($thumbnail_url);
    if (!$image_data) {
        return;
    }
    
    $filename = 'youtube-thumb-' . $post_id . '.jpg';
    $file_path = $upload_dir['path'] . '/' . $filename;
    file_put_contents($file_path, $image_data);
    
    $file_type = wp_check_filetype($file_path);
    
    $attachment = [
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];
    
    $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    set_post_thumbnail($post_id, $attach_id);
}

add_action('save_post', 'set_youtube_thumbnail_as_featured');
