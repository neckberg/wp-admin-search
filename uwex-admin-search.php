<?php
/**
 * Plugin Name: UWEX Admin Search
 * Description: A UWEX Admin page to search through all post content and metadata.
 * Version: 1.0
 * Author: Your Name
 */

add_action('admin_menu', 'uwex_admin_search_menu');

function uwex_admin_search_menu() {
    add_menu_page(
        'UWEX Admin Search',
        'Admin Search',
        'manage_options',
        'uwex-admin-search',
        'uwex_admin_search_page',
        'dashicons-search',
        20
    );
}

function uwex_admin_search_page() {
    ?>
    <div class="wrap">
        <h1>UWEX Admin Search</h1>
        <form method="GET" action="">
            <input type="hidden" name="page" value="uwex-admin-search" />
            <input type="text" name="search_term" placeholder="Enter search term" value="<?php echo esc_attr($_GET['search_term'] ?? ''); ?>" />
            <button type="submit" class="button button-primary">Search</button>
        </form>

        <?php if (!empty($_GET['search_term'])): ?>
            <h2>Search Results for "<?php echo esc_html($_GET['search_term']); ?>"</h2>
            <?php uwex_admin_search_results($_GET['search_term']); ?>
        <?php endif; ?>
    </div>
    <?php
}

function uwex_admin_search_results($search_term) {
  global $wpdb;

  // Get all site IDs
  $site_ids = get_sites(['fields' => 'ids']);

  echo '<h3>Search Results</h3>';

  foreach ($site_ids as $site_id) {
      $site_name = get_blog_option($site_id, 'blogname');

      // Get the table prefix for the current site
      $table_prefix = $wpdb->get_blog_prefix($site_id);

      // Prepare the SQL query
      $query = $wpdb->prepare(
          "
          SELECT DISTINCT p.ID, p.post_title, p.post_type, p.post_status, p.post_date
          FROM {$table_prefix}posts p
          LEFT JOIN {$table_prefix}postmeta pm ON p.ID = pm.post_id
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

      // Display results for the current site
      if ($results) {
          ?>
          <h4>Results from Site <?= $site_id ?> - <?= esc_html($site_name) ?></h4>
          <table class="widefat fixed" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              foreach ($results as $post) {
                ?>
                <tr>
                  <td><?= esc_html($post->ID) ?></td>
                  <td><?= esc_html($post->post_title) ?></td>
                  <td><?= esc_html($post->post_type) ?></td>
                  <td><?= esc_html($post->post_status) ?></td>
                  <td><?= esc_html($post->post_date) ?></td>
                  <td><a href="<?= esc_url(get_site_url($site_id) . '/wp-admin/post.php?post=' . $post->ID . '&action=edit') ?>">Edit</a></td>
                </tr>
                <?php
              }
              ?>
            </tbody>
          </table>
          <?php
      } else {
        ?><h4>No results found for Site <?= $site_id ?> - <?= esc_html($site_name) ?></h4><?php
      }
  }
}
