<?php
/*
Plugin Name: Pages and Posts List with Search and Separate CSV Export
Description: Displays a list of published pages and posts with URLs, provides a unified search option, and allows downloading the lists as separate CSV files.
Version: 1.0
Author: Tech9logy Creators
*/

// Add menu item in admin panel
add_action('admin_menu', 'pages_posts_list_with_csv_export_menu');

function pages_posts_list_with_csv_export_menu() {
    add_menu_page('Pages and Posts List', 'Pages and Posts List', 'manage_options', 'pages-posts-list-with-csv-export', 'pages_posts_list_with_csv_export_page');
}

function pages_posts_list_with_csv_export_page() {
    // Check if user is allowed to export
    if (!current_user_can('manage_options')) {
        return;
    }

    // Display search form
    echo '<div class="wrap">';
    echo '<h2>Published Pages and Posts</h2>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="pages-posts-list-with-csv-export">';
    echo '<input type="text" name="search" placeholder="Search Pages and Posts" value="' . (isset($_GET['search']) ? $_GET['search'] : '') . '">';
    echo '<button type="submit">Search</button>';
    if (isset($_GET['search'])) {
        echo '<a href="' . admin_url('admin.php?page=pages-posts-list-with-csv-export') . '" class="button">Clear</a>';
    }
    echo '</form>';

    // Get search term
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

    // Display published pages
    echo '<div>';
    echo '<h3>Published Pages</h3>';
    display_entity_list(get_pages(array('post_status' => 'publish')), $search_term, 'page');
    echo '</div>';

    // Display published posts
    echo '<div>';
    echo '<h3>Published Posts</h3>';
    display_entity_list(get_posts(array('post_status' => 'publish', 'numberposts' => -1)), $search_term, 'post');
    echo '</div>';

    echo '</div>';
}

function display_entity_list($items, $search_term, $type) {
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>S.no</th>';
    echo '<th>Title</th>';
    echo '<th>URL</th>';
    echo '<th>ID</th>';
    echo '<th>Author</th>';
    echo '<th>Published Date</th>';
    echo '<th>Last Modified Date</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    // Loop through items and display them based on search term
    $i = 1;
    foreach ($items as $item) {
        $title = $item->post_title;
        $link = get_permalink($item->ID);
        $id = $item->ID;
        $date = $item->post_date;
        $author_id = $item->post_author;
        $author_name = get_the_author_meta('display_name', $author_id);
        $last_modified = $item->post_modified;

        if (stripos($title, $search_term) !== false ||
            stripos($link, $search_term) !== false ||
            stripos($id, $search_term) !== false ||
            stripos($author_name, $search_term) !== false) {
            echo '<tr>';
            echo '<td>' . $i++ . '</td>';
            echo '<td>' . $title . '</td>';
            echo '<td><a href="' . $link . '" target="_blank">' . $link . '</a></td>';
            echo '<td>' . $id . '</td>';
            echo '<td>' . $author_name . '</td>';
            echo '<td>' . $date . '</td>';
            echo '<td>' . $last_modified . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';

    // Display download CSV button only if there is no active search term
    if (empty($search_term)) {
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="download_' . $type . '_csv">';
        echo '<input type="hidden" name="search_term" value="' . $search_term . '">';
        echo '<input type="submit" class="button button-primary" value="Download All ' . ucfirst($type) . ' List">';
        echo '</form>';
    }
}

// Handle pages CSV download action
add_action('admin_post_download_page_csv', 'download_page_csv');

function download_page_csv() {
    handle_csv_download(get_pages(array('post_status' => 'publish')), 'page');
}

// Handle posts CSV download action
add_action('admin_post_download_post_csv', 'download_post_csv');

function download_post_csv() {
    handle_csv_download(get_posts(array('post_status' => 'publish', 'numberposts' => -1)), 'post');
}

function handle_csv_download($items, $type) {
    // Check if user is allowed to export
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get search term
    $search_term = isset($_POST['search_term']) ? $_POST['search_term'] : '';

    // Start building CSV content
    $csv_content = "S.no,Title,URL,ID,Author,Published Date,Last Modified Date\n";

    // Loop through items and add to CSV content
    $i = 1;
    foreach ($items as $item) {
        $title = $item->post_title;
        $link = get_permalink($item->ID);
        $id = $item->ID;
        $date = $item->post_date;
        $author_id = $item->post_author;
        $author_name = get_the_author_meta('display_name', $author_id);
        $last_modified = $item->post_modified;

        if (stripos($title, $search_term) !== false ||
            stripos($link, $search_term) !== false ||
            stripos($id, $search_term) !== false ||
            stripos($author_name, $search_term) !== false) {
            $csv_content .= "$i,$title,$link,$id,$author_name,$date,$last_modified\n";
            $i++;
        }
    }

    // Generate CSV file name
    $file_name = $type . '_export_' . date('Y-m-d') . '.csv';

    // Send headers to download CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');

    // Output CSV content
    echo $csv_content;

    exit;
}
?>