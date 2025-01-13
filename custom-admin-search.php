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

add_action( 'pre_get_posts', function( $q )
{
    if( $title = $q->get( '_meta_or_title' ) )
    {
        add_filter( 'get_meta_sql', function( $sql ) use ( $title )
        {
            global $wpdb;

            // Only run once:
            static $nr = 0; 
            if( 0 != $nr++ ) return $sql;

            // Modified WHERE
            $sql['where'] = sprintf(
                " AND ( %s OR %s ) ",
                $wpdb->prepare( "{$wpdb->posts}.post_title like '%%%s%%'", $title),
                mb_substr( $sql['where'], 5, mb_strlen( $sql['where'] ) )
            );

            return $sql;
        });
    }
});

function custom_admin_search_results($search_term) {
  // Get all site IDs
  $site_ids = get_sites(['fields' => 'ids']);

  echo '<h3>Search Results</h3>';

  foreach ($site_ids as $site_id) {
      // Switch to the site's context
      switch_to_blog($site_id);

      // Perform the WP_Query
      $query_args = [
          'post_type'   => 'any', // Search all post types
          'post_status' => 'publish', // Exclude unpublished posts
          // 's'           => $search_term, // Search term for content
          'meta_query'  => [
              // 'relation' => 'OR', // Combine with the content search
              [
                  // 'key'     => '', // Search all meta keys
                  'value'   => $search_term,
                  'compare' => 'LIKE',
              ],
          ],
      ];

      $query = new WP_Query($query_args);

      // Display results for the current site
      if ($query->have_posts()) {
          echo '<h4>Results from Site: ' . esc_html(get_bloginfo('name')) . ' (Site ID: ' . $site_id . ')</h4>';
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

          while ($query->have_posts()) {
              $query->the_post();
              echo '<tr>
                  <td>' . get_the_ID() . '</td>
                  <td>' . get_the_title() . '</td>
                  <td>' . get_post_type() . '</td>
                  <td>' . get_post_status() . '</td>
                  <td><a href="' . get_edit_post_link() . '">Edit</a></td>
              </tr>';
          }

          echo '</tbody></table>';
      } else {
          echo '<p>No results found for site ' . $site_id . '.</p>';
      }

      // Restore the original site context
      restore_current_blog();
  }

  // Reset the main query
  wp_reset_postdata();
}
