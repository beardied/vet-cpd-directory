<?php
/**
 * Vet CPD Migration Script
 * 
 * One-time migration script to convert The Events Calendar data to Vet CPD Directory
 * 
 * Usage:
 * 1. Place this file in the plugin root folder (wp-content/plugins/vet-cpd-directory/)
 * 2. Visit as admin: https://yoursite.com/wp-content/plugins/vet-cpd-directory/migration.php
 * 3. Or run via WP-CLI: wp eval-file wp-content/plugins/vet-cpd-directory/migration.php
 */

// Bootstrap WordPress
$wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die('ERROR: Could not find wp-load.php. Please ensure this script is in the plugin folder.');
}
require_once $wp_load_path;

// Security check - require admin
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    wp_die('<h1>Admin Access Required</h1><p>You must be logged in as an administrator to run this migration.</p>');
}

// Start output
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Vet CPD Migration</title>';
echo '<style>';
echo 'body{font-family:monospace;padding:20px;line-height:1.6;max-width:1200px;margin:0 auto;}';
echo '.success{color:green;font-weight:bold;}';
echo '.error{color:red;font-weight:bold;}';
echo '.info{color:#0066cc;}';
echo '.log{background:#f5f5f5;padding:10px;margin:5px 0;border-left:3px solid #ccc;}';
echo '.log.success{border-left-color:green;}';
echo '.log.error{border-left-color:red;}';
echo '.log.info{border-left-color:#0066cc;}';
echo 'h1,h2{color:#333;}';
echo '.summary{background:#e8f5e9;padding:20px;margin:20px 0;border-radius:5px;}';
echo '</style>';
echo '</head><body>';
echo '<h1>🚀 Vet CPD Migration Script</h1>';

// Ensure plugin is active
if (!class_exists('VET_CPD_CPD')) {
    echo '<p class="error">❌ ERROR: Vet CPD Directory plugin not active!</p>';
    echo '<p>Please activate the plugin first, then refresh this page.</p>';
    echo '</body></html>';
    exit;
}

/**
 * Helper: Fix post dates after WordPress overrides them
 * WordPress wp_insert_post/wp_update_post always sets post_modified to current time
 * We need to use direct DB query to preserve original dates
 */
function fix_post_dates($post_id, $original_post) {
    global $wpdb;
    
    $wpdb->update(
        $wpdb->posts,
        [
            'post_date'           => $original_post->post_date,
            'post_date_gmt'       => $original_post->post_date_gmt,
            'post_modified'       => $original_post->post_modified,
            'post_modified_gmt'   => $original_post->post_modified_gmt,
        ],
        ['ID' => $post_id],
        ['%s', '%s', '%s', '%s'],
        ['%d']
    );
    
    // Clean cache
    clean_post_cache($post_id);
}

/**
 * Helper: Fix post author after WordPress overrides it
 */
function fix_post_author($post_id, $original_author_id) {
    global $wpdb;
    
    $wpdb->update(
        $wpdb->posts,
        ['post_author' => $original_author_id],
        ['ID' => $post_id],
        ['%d'],
        ['%d']
    );
    
    // Clean cache
    clean_post_cache($post_id);
}

// Check if running via WP-CLI
$is_cli = defined('WP_CLI') && WP_CLI;
$log_messages = [];

function log_msg($message, $type = 'info') {
    global $is_cli, $log_messages;
    $log_messages[] = ['msg' => $message, 'type' => $type];
    if ($is_cli) {
        WP_CLI::log($message);
    } else {
        $class = $type === 'success' ? 'success' : ($type === 'error' ? 'error' : 'info');
        echo '<div class="log ' . $class . '">' . esc_html($message) . '</div>';
        ob_flush(); flush();
    }
}

echo '<div class="log info">✅ WordPress loaded successfully</div>';
echo '<div class="log info">✅ Vet CPD Directory plugin detected</div>';

// Step 1: Create system tags
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 1: Creating system tags...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$system_tags = ['upcoming', 'on-demand', 'online', 'free'];
foreach ($system_tags as $tag) {
    if (!term_exists($tag, 'cpd_tag')) {
        $result = wp_insert_term($tag, 'cpd_tag', ['slug' => $tag]);
        if (!is_wp_error($result)) {
            log_msg("✓ Created tag: {$tag}", 'success');
        } else {
            log_msg("✗ ERROR creating tag {$tag}: " . $result->get_error_message(), 'error');
        }
    } else {
        log_msg("ℹ Tag already exists: {$tag}");
    }
}

// Step 2: Migrate venues
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 2: Migrating venues...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

// Use WP_Query for better control
$venue_query = new WP_Query([
    'post_type'      => 'tribe_venue',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'all',
]);
$venues = $venue_query->posts;
log_msg("Found " . count($venues) . " venues to migrate");

$venue_map = get_option('vet_cpd_venue_map', []);
$venue_count = 0;

foreach ($venues as $venue) {
    if (isset($venue_map[$venue->ID])) {
        log_msg("ℹ Skipping (already migrated): {$venue->post_title}");
        continue;
    }
    
    // Preserve original post status
    $post_status = $venue->post_status;
    
    $new_id = wp_insert_post([
        'post_title'   => $venue->post_title,
        'post_type'    => 'cpd_venue',
        'post_status'  => $post_status,
        'post_content' => $venue->post_content,
        'post_author'  => $venue->post_author,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating venue '{$venue->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Fix dates and author (WordPress overrides them)
    fix_post_dates($new_id, $venue);
    fix_post_author($new_id, $venue->post_author);
    
    $meta_mapping = [
        '_venue_address'     => '_VenueAddress',
        '_venue_city'        => '_VenueCity',
        '_venue_country'     => '_VenueCountry',
        '_venue_state'       => '_VenueStateProvince',
        '_venue_postal_code' => '_VenueZip',
        '_venue_phone'       => '_VenuePhone',
        '_venue_website'     => '_VenueURL',
        '_venue_show_map'    => '_VenueShowMap',
        '_venue_show_map_link' => '_VenueShowMapLink',
    ];
    
    foreach ($meta_mapping as $new_key => $old_key) {
        $value = get_post_meta($venue->ID, $old_key, true);
        if ($value) {
            update_post_meta($new_id, $new_key, $value);
        }
    }
    
    // Preserve featured image
    if (has_post_thumbnail($venue->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($venue->ID));
    }
    
    $venue_map[$venue->ID] = $new_id;
    $venue_count++;
    log_msg("✓ Migrated: {$venue->post_title} ({$venue->ID} → {$new_id}) [{$post_status}]", 'success');
}

update_option('vet_cpd_venue_map', $venue_map);
log_msg("✓ Venues migrated: {$venue_count}", 'success');

// Step 3: Migrate organisers
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 3: Migrating organisers...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$organizer_query = new WP_Query([
    'post_type'      => 'tribe_organizer',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'all',
]);
$organizers = $organizer_query->posts;
log_msg("Found " . count($organizers) . " organisers to migrate");

$organizer_map = get_option('vet_cpd_organizer_map', []);
$organizer_count = 0;

foreach ($organizers as $org) {
    if (isset($organizer_map[$org->ID])) {
        log_msg("ℹ Skipping (already migrated): {$org->post_title}");
        continue;
    }
    
    // Preserve original post status
    $post_status = $org->post_status;
    
    $new_id = wp_insert_post([
        'post_title'   => $org->post_title,
        'post_content' => $org->post_content,
        'post_type'    => 'cpd_organiser',
        'post_status'  => $post_status,
        'post_author'  => $org->post_author,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating organiser '{$org->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Fix dates and author (WordPress overrides them)
    fix_post_dates($new_id, $org);
    fix_post_author($new_id, $org->post_author);
    
    update_post_meta($new_id, '_organiser_phone', get_post_meta($org->ID, '_OrganizerPhone', true));
    update_post_meta($new_id, '_organiser_website', get_post_meta($org->ID, '_OrganizerWebsite', true));
    update_post_meta($new_id, '_organiser_email', get_post_meta($org->ID, '_OrganizerEmail', true));
    update_post_meta($new_id, '_old_organizer_id', $org->ID);
    
    // Preserve featured image
    if (has_post_thumbnail($org->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($org->ID));
    }
    
    $organizer_map[$org->ID] = $new_id;
    $organizer_count++;
    log_msg("✓ Migrated: {$org->post_title} ({$org->ID} → {$new_id}) [{$post_status}]", 'success');
}

update_option('vet_cpd_organizer_map', $organizer_map);
log_msg("✓ Organisers migrated: {$organizer_count}", 'success');

// Step 4: Migrate instructors
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 4: Migrating instructors...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$instructor_query = new WP_Query([
    'post_type'      => 'tribe_ext_instructor',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'all',
]);
$instructors = $instructor_query->posts;
log_msg("Found " . count($instructors) . " instructors to migrate");

$instructor_map = get_option('vet_cpd_instructor_map', []);
$instructor_count = 0;

foreach ($instructors as $inst) {
    if (isset($instructor_map[$inst->ID])) {
        log_msg("ℹ Skipping (already migrated): {$inst->post_title}");
        continue;
    }
    
    // Preserve original post status
    $post_status = $inst->post_status;
    
    $new_id = wp_insert_post([
        'post_title'   => $inst->post_title,
        'post_content' => $inst->post_content,
        'post_type'    => 'cpd_instructor',
        'post_status'  => $post_status,
        'post_author'  => $inst->post_author,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating instructor '{$inst->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Fix dates and author (WordPress overrides them)
    fix_post_dates($new_id, $inst);
    fix_post_author($new_id, $inst->post_author);
    
    update_post_meta($new_id, '_instructor_phone', get_post_meta($inst->ID, '_tribe_ext_instructor_phone', true));
    update_post_meta($new_id, '_instructor_website', get_post_meta($inst->ID, '_tribe_ext_instructor_website', true));
    update_post_meta($new_id, '_instructor_email', get_post_meta($inst->ID, '_tribe_ext_instructor_email_address', true));
    update_post_meta($new_id, '_old_instructor_id', $inst->ID);
    
    // Preserve featured image
    if (has_post_thumbnail($inst->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($inst->ID));
    }
    
    $instructor_map[$inst->ID] = $new_id;
    $instructor_count++;
    log_msg("✓ Migrated: {$inst->post_title} ({$inst->ID} → {$new_id}) [{$post_status}]", 'success');
}

update_option('vet_cpd_instructor_map', $instructor_map);
log_msg("✓ Instructors migrated: {$instructor_count}", 'success');

// Step 5: Migrate series
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 5: Migrating series...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$series_query = new WP_Query([
    'post_type'      => 'tribe_event_series',
    'posts_per_page' => -1,
    'post_status'    => 'any',
    'fields'         => 'all',
]);
$series = $series_query->posts;
log_msg("Found " . count($series) . " series to migrate");

$series_map = get_option('vet_cpd_series_map', []);
$series_count = 0;

foreach ($series as $s) {
    if (isset($series_map[$s->ID])) {
        log_msg("ℹ Skipping (already migrated): {$s->post_title}");
        continue;
    }
    
    // Preserve original post status
    $post_status = $s->post_status;
    
    $new_id = wp_insert_post([
        'post_title'   => $s->post_title,
        'post_content' => $s->post_content,
        'post_type'    => 'cpd_series',
        'post_status'  => $post_status,
        'post_author'  => $s->post_author,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating series '{$s->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Fix dates and author (WordPress overrides them)
    fix_post_dates($new_id, $s);
    fix_post_author($new_id, $s->post_author);
    
    // Preserve featured image
    if (has_post_thumbnail($s->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($s->ID));
    }
    
    $series_map[$s->ID] = $new_id;
    $series_count++;
    log_msg("✓ Migrated: {$s->post_title} ({$s->ID} → {$new_id}) [{$post_status}]", 'success');
}

update_option('vet_cpd_series_map', $series_map);
log_msg("✓ Series migrated: {$series_count}", 'success');

// Step 6: Migrate all categories (even those without posts)
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 6: Migrating all categories with hierarchy...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$all_categories = get_terms([
    'taxonomy'   => 'tribe_events_cat',
    'hide_empty' => false,
    'posts_per_page' => -1,
]);

$system_slugs = ['online', 'on-demand', 'up-coming', 'free', 'physical-event'];
$categories_created = 0;

// First pass: Create all parent categories (parent = 0)
$cat_map = []; // Map old term_id => new term_id

foreach ($all_categories as $cat) {
    // Skip system tags that should be tags, not categories
    if (in_array($cat->slug, $system_slugs)) {
        log_msg("ℹ Skipping system tag: {$cat->name}");
        continue;
    }
    
    // Skip child categories for now (parent != 0)
    if ($cat->parent != 0) {
        continue;
    }
    
    if (!term_exists($cat->slug, 'cpd_category')) {
        $result = wp_insert_term($cat->name, 'cpd_category', [
            'slug'        => $cat->slug,
            'description' => $cat->description,
            'parent'      => 0,
        ]);
        
        if (!is_wp_error($result)) {
            log_msg("✓ Created parent category: {$cat->name}", 'success');
            $categories_created++;
            $cat_map[$cat->term_id] = $result['term_id'];
        } else {
            log_msg("✗ ERROR creating category {$cat->name}: " . $result->get_error_message(), 'error');
        }
    } else {
        log_msg("ℹ Category already exists: {$cat->name}");
        $existing = get_term_by('slug', $cat->slug, 'cpd_category');
        if ($existing) {
            $cat_map[$cat->term_id] = $existing->term_id;
        }
    }
}

// Second pass: Create child categories
foreach ($all_categories as $cat) {
    // Skip system tags
    if (in_array($cat->slug, $system_slugs)) {
        continue;
    }
    
    // Skip parent categories (already created)
    if ($cat->parent == 0) {
        continue;
    }
    
    // Find the new parent term_id
    $new_parent_id = isset($cat_map[$cat->parent]) ? $cat_map[$cat->parent] : 0;
    
    if (!term_exists($cat->slug, 'cpd_category')) {
        $result = wp_insert_term($cat->name, 'cpd_category', [
            'slug'        => $cat->slug,
            'description' => $cat->description,
            'parent'      => $new_parent_id,
        ]);
        
        if (!is_wp_error($result)) {
            log_msg("✓ Created child category: {$cat->name} (parent: {$new_parent_id})", 'success');
            $categories_created++;
            $cat_map[$cat->term_id] = $result['term_id'];
        } else {
            log_msg("✗ ERROR creating category {$cat->name}: " . $result->get_error_message(), 'error');
        }
    } else {
        log_msg("ℹ Category already exists: {$cat->name}");
        $existing = get_term_by('slug', $cat->slug, 'cpd_category');
        if ($existing) {
            $cat_map[$cat->term_id] = $existing->term_id;
        }
    }
}

log_msg("✓ Categories created: {$categories_created}", 'success');

// Step 7: Migrate events
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 7: Migrating events to CPDs...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

// CRITICAL FIX: Use direct DB query to bypass TEC's Custom Tables filtering
// TEC hijacks WP_Query to only return future events from wp_tec_occurrences
// We need ALL events (past and future) from wp_posts
global $wpdb;
$events = $wpdb->get_results(
    "SELECT * FROM {$wpdb->posts} 
     WHERE post_type = 'tribe_events' 
     AND post_status IN ('publish', 'draft', 'pending', 'private')
     ORDER BY ID ASC"
);

log_msg("Found " . count($events) . " events to migrate (direct DB query)");

// Reload mappings
$venue_map = get_option('vet_cpd_venue_map', []);
$organizer_map = get_option('vet_cpd_organizer_map', []);
$instructor_map = get_option('vet_cpd_instructor_map', []);

$event_count = 0;

$tag_assignment_count = 0;
$cat_assignment_count = 0;

foreach ($events as $event) {
    // Check if already migrated
    $existing = get_posts([
        'post_type'      => 'cpd_event',
        'meta_query'     => [['key' => '_old_event_id', 'value' => $event->ID]],
        'posts_per_page' => 1,
        'fields'         => 'ids'
    ]);
    
    if (!empty($existing)) {
        log_msg("ℹ Skipping (already migrated): {$event->post_title}");
        continue;
    }
    
    $start_date = get_post_meta($event->ID, '_EventStartDate', true);
    $end_date = get_post_meta($event->ID, '_EventEndDate', true);
    $cost = get_post_meta($event->ID, '_EventCost', true);
    $currency = get_post_meta($event->ID, '_EventCurrencyCode', true) ?: 'GBP';
    
    // CRITICAL FIX: Ensure dates are preserved even if time portion is missing
    // If _EventStartDate exists but is just a date (no time), ensure it's still migrated
    if (!empty($start_date)) {
        // If date doesn't have time component, add default time
        if (strlen($start_date) <= 10) {
            $start_date .= ' 00:00:00';
        }
    }
    if (!empty($end_date)) {
        // If date doesn't have time component, add default time
        if (strlen($end_date) <= 10) {
            $end_date .= ' 00:00:00';
        }
    }
    
    // Preserve original post status, author, and dates
    $post_status = $event->post_status;
    $post_author = $event->post_author;
    
    $new_id = wp_insert_post([
        'post_title'   => $event->post_title,
        'post_content' => $event->post_content,
        'post_type'    => 'cpd_event',
        'post_status'  => $post_status,
        'post_author'  => $post_author,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating CPD '{$event->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Fix dates (WordPress overrides them)
    fix_post_dates($new_id, $event);
    
    // Meta fields
    update_post_meta($new_id, '_cpd_provider_url', get_post_meta($event->ID, '_EventURL', true));
    update_post_meta($new_id, '_cpd_start_date', $start_date);
    update_post_meta($new_id, '_cpd_end_date', $end_date);
    update_post_meta($new_id, '_cpd_all_day', get_post_meta($event->ID, '_EventAllDay', true) ?: '0');
    update_post_meta($new_id, '_cpd_cost', $cost);
    update_post_meta($new_id, '_cpd_currency', $currency);
    update_post_meta($new_id, '_old_event_id', $event->ID);
    
    // Fix author (WordPress might override it)
    fix_post_author($new_id, $post_author);
    
    // Venue
    $old_venue = get_post_meta($event->ID, '_EventVenueID', true);
    if ($old_venue && isset($venue_map[$old_venue])) {
        update_post_meta($new_id, '_cpd_venues', [$venue_map[$old_venue]]);
        log_msg("    → Venue assigned");
    }
    
    // Organisers
    $old_org = get_post_meta($event->ID, '_EventOrganizerID', true);
    if ($old_org && isset($organizer_map[$old_org])) {
        update_post_meta($new_id, '_cpd_organisers', [$organizer_map[$old_org]]);
        log_msg("    → Organiser assigned");
    }
    
    // Instructors - Get ALL instructor IDs
    $old_instructor_ids = get_post_meta($event->ID, '_tribe_linked_post_tribe_ext_instructor', false);
    if (!empty($old_instructor_ids)) {
        $new_instructors = [];
        foreach ($old_instructor_ids as $old_inst_id) {
            if (isset($instructor_map[$old_inst_id])) {
                $new_instructors[] = $instructor_map[$old_inst_id];
            }
        }
        if (!empty($new_instructors)) {
            update_post_meta($new_id, '_cpd_instructors', $new_instructors);
            log_msg("    → " . count($new_instructors) . " instructor(s) assigned");
        }
    }
    
    // Categories and tags (cpd_tag taxonomy)
    $terms = get_the_terms($event->ID, 'tribe_events_cat');
    if ($terms && !is_wp_error($terms)) {
        $cat_slugs = [];
        $tag_slugs = [];
        
        foreach ($terms as $term) {
            // Status categories become tags
            if (in_array($term->slug, ['online', 'on-demand', 'up-coming', 'free'])) {
                $new_slug = ($term->slug === 'up-coming') ? 'upcoming' : $term->slug;
                $tag_slugs[] = $new_slug;
                log_msg("    → Category '{$term->name}' → Tag '{$new_slug}'");
            } else {
                // Subject categories stay as categories
                $cat_slugs[] = $term->slug;
            }
        }
        
        if (!empty($cat_slugs)) {
            $result = wp_set_object_terms($new_id, $cat_slugs, 'cpd_category');
            if (is_wp_error($result)) {
                log_msg("    ✗ ERROR setting categories: " . $result->get_error_message(), 'error');
            } else {
                log_msg("    ✓ Categories set: " . implode(', ', $cat_slugs));
                $cat_assignment_count++;
            }
        }
        
        if (!empty($tag_slugs)) {
            $result = wp_set_object_terms($new_id, $tag_slugs, 'cpd_tag', true);
            if (is_wp_error($result)) {
                log_msg("    ✗ ERROR setting tags: " . $result->get_error_message(), 'error');
            } else {
                log_msg("    ✓ Tags set: " . implode(', ', $tag_slugs));
                $tag_assignment_count++;
            }
        }
    }
    
    // Featured image
    if (has_post_thumbnail($event->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($event->ID));
    }
    
    $event_count++;
    log_msg("✓ Migrated: {$event->post_title} [{$post_status}]", 'success');
}

log_msg("✓ Events migrated: {$event_count}", 'success');
log_msg("✓ Events with categories assigned: {$cat_assignment_count}", 'success');
log_msg("✓ Events with tags assigned: {$tag_assignment_count}", 'success');

// Summary
echo '<div class="summary">';
echo '<h2>✅ Migration Complete!</h2>';
echo '<table style="width:100%;font-size:16px;">';
echo '<tr><td>Venues migrated:</td><td><strong>' . count($venue_map) . '</strong></td></tr>';
echo '<tr><td>Organisers migrated:</td><td><strong>' . count($organizer_map) . '</strong></td></tr>';
echo '<tr><td>Instructors migrated:</td><td><strong>' . count($instructor_map) . '</strong></td></tr>';
echo '<tr><td>Series migrated:</td><td><strong>' . count($series_map) . '</strong></td></tr>';
echo '<tr><td>Events migrated:</td><td><strong>' . $event_count . '</strong></td></tr>';
echo '<tr><td>Categories imported:</td><td><strong>' . $categories_created . '</strong></td></tr>';
echo '<tr><td>Events with categories:</td><td><strong>' . $cat_assignment_count . '</strong></td></tr>';
echo '<tr><td>Events with tags:</td><td><strong>' . $tag_assignment_count . '</strong></td></tr>';
echo '</table>';
echo '</div>';

echo '<h3>📋 Next steps:</h3>';
echo '<ol>';
echo '<li>Visit <a href="/cpd/" target="_blank">/cpd/</a> to verify events are displaying</li>';
echo '<li>Check a few individual CPD events to verify categories and tags are set</li>';
echo '<li>Test the category and tag archive pages</li>';
echo '<li>Deactivate The Events Calendar plugins once verified</li>';
echo '<li><strong>Delete this migration.php file when done</strong></li>';
echo '</ol>';

echo '</body></html>';

// Store log for reference
update_option('vet_cpd_migration_log', $log_messages);
