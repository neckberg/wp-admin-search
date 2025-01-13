<?php
/**
 * Plugin Name: Custom Admin Search
 * Description: A custom admin page to search through all post content and metadata.
 * Version: 1.0
 * Author: Your Name
 */

add_action('admin_menu', 'custom_admin_search_menu');

function custom_admin_search_menu() {
    add_menu_page(
        'Custom Admin Search',
        'Admin Search',
        'manage_options',
        'custom-admin-search',
        'custom_admin_search_page',
        'dashicons-search',
        20
    );
}

function custom_admin_search_page() {
    ?>
    <div class="wrap">
        <h1>Custom Admin Search</h1>
        <form method="GET" action="">
            <input type="hidden" name="page" value="custom-admin-search" />
            <input type="text" name="search_term" placeholder="Enter search term" value="<?php echo esc_attr($_GET['search_term'] ?? ''); ?>" />
            <button type="submit" class="button button-primary">Search</button>
        </form>

        <?php if (!empty($_GET['search_term'])): ?>
            <h2>Search Results for "<?php echo esc_html($_GET['search_term']); ?>"</h2>
            <?php custom_admin_search_results($_GET['search_term']); ?>
        <?php endif; ?>
    </div>
    <?php
}

function custom_admin_search_results($search_term) {
    global $wpdb;

    // Prepare the SQL query
    $query = $wpdb->prepare(
        "
        SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_status
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE 
            (p.post_content LIKE %s OR pm.meta_value LIKE %s)
            AND p.post_status = 'publish'
            AND p.post_type NOT IN ('revision')
        ORDER BY p.post_date DESC
        ",
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%'
    );

    // Execute the query
    $results = $wpdb->get_results($query);

    if ($results) {
        echo '<table class="widefat fixed" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($results as $post) {
            echo '<tr>
                <td>' . esc_html($post->ID) . '</td>
                <td>' . esc_html($post->post_title) . '</td>
                <td>' . esc_html($post->post_type) . '</td>
                <td>' . esc_html($post->post_status) . '</td>
                <td><a href="' . esc_url(get_edit_post_link($post->ID)) . '">Edit</a></td>
            </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No results found for this search term.</p>';
    }
}
