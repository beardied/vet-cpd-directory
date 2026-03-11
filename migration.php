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
 * 
 * This script will:
 * - Create system tags (upcoming, on-demand, online, free)
 * - Migrate venues (tribe_venue → cpd_venue)
 * - Migrate organizers (tribe_organizer → cpd_person with organizer role)
 * - Migrate instructors (tribe_ext_instructor → cpd_person with instructor role)
 * - Migrate series (tribe_event_series → cpd_series)
 * - Migrate events (tribe_events → cpd_event with categories and tags)
 * - Fix shortcodes in posts/pages
 */

// Bootstrap WordPress
$wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
if (!file_exists($wp_load_path)) {
    die('ERROR: Could not find wp-load.php. Please ensure this script is in the plugin folder.');
}
require_once $wp_load_path;

// Security check
if (!current_user_can('manage_options') && !defined('WP_CLI')) {
    wp_die('Admin access required');
}

// Start output
header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Vet CPD Migration</title>';
echo '<style>body{font-family:monospace;padding:20px;line-height:1.6;} .success{color:green;} .error{color:red;} .info{color:blue;} .log{background:#f5f5f5;padding:10px;margin:5px 0;}</style>';
echo '</head><body><h1>Vet CPD Migration Script</h1>';

// Ensure plugin is active
if (!class_exists('VET_CPD_CPD')) {
    echo '<p class="error">ERROR: Vet CPD Directory plugin not active!</p>';
    echo '<p>Please activate the plugin first.</p></body></html>';
    exit;
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

echo '<div class="log">Starting migration...</div>';

// Step 1: Create system tags
log_msg('Step 1: Creating system tags...', 'info');
$system_tags = ['upcoming', 'on-demand', 'online', 'free'];
foreach ($system_tags as $tag) {
    if (!term_exists($tag, 'cpd_tag')) {
        $result = wp_insert_term($tag, 'cpd_tag', ['slug' => $tag]);
        if (!is_wp_error($result)) {
            log_msg("  Created tag: {$tag}", 'success');
        } else {
            log_msg("  ERROR creating tag {$tag}: " . $result->get_error_message(), 'error');
        }
    } else {
        log_msg("  Tag already exists: {$tag}");
    }
}

// Step 2: Migrate venues
log_msg('Step 2: Migrating venues...', 'info');
$venues = get_posts(['post_type' => 'tribe_venue', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($venues) . " venues");

$venue_map = get_option('vet_cpd_venue_map', []);
$venue_count = 0;

foreach ($venues as $venue) {
    // Check if already migrated
    if (isset($venue_map[$venue->ID])) {
        log_msg("  Skipping (already migrated): {$venue->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'  => $venue->post_title,
        'post_type'   => 'cpd_venue',
        'post_status' => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("  ERROR creating venue '{$venue->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Copy meta fields
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
    
    $venue_map[$venue->ID] = $new_id;
    $venue_count++;
    log_msg("  Migrated: {$venue->post_title} ({$venue->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_venue_map', $venue_map);
log_msg("Venues migrated: {$venue_count}", 'success');

// Step 3: Migrate organizers
log_msg('Step 3: Migrating organizers...', 'info');
$organizers = get_posts(['post_type' => 'tribe_organizer', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($organizers) . " organizers");

$organizer_map = get_option('vet_cpd_organizer_map', []);
$organizer_count = 0;

foreach ($organizers as $org) {
    if (isset($organizer_map[$org->ID])) {
        log_msg("  Skipping (already migrated): {$org->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $org->post_title,
        'post_content' => $org->post_content,
        'post_type'    => 'cpd_person',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("  ERROR creating person '{$org->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    update_post_meta($new_id, '_person_role_organizer', '1');
    update_post_meta($new_id, '_person_phone', get_post_meta($org->ID, '_OrganizerPhone', true));
    update_post_meta($new_id, '_person_website', get_post_meta($org->ID, '_OrganizerWebsite', true));
    update_post_meta($new_id, '_person_email', get_post_meta($org->ID, '_OrganizerEmail', true));
    update_post_meta($new_id, '_old_organizer_id', $org->ID);
    
    if (has_post_thumbnail($org->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($org->ID));
    }
    
    $organizer_map[$org->ID] = $new_id;
    $organizer_count++;
    log_msg("  Migrated: {$org->post_title} ({$org->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_organizer_map', $organizer_map);
log_msg("Organizers migrated: {$organizer_count}", 'success');

// Step 4: Migrate instructors
log_msg('Step 4: Migrating instructors...', 'info');
$instructors = get_posts(['post_type' => 'tribe_ext_instructor', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($instructors) . " instructors");

$instructor_map = get_option('vet_cpd_instructor_map', []);
$instructor_count = 0;

foreach ($instructors as $inst) {
    if (isset($instructor_map[$inst->ID])) {
        log_msg("  Skipping (already migrated): {$inst->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $inst->post_title,
        'post_content' => $inst->post_content,
        'post_type'    => 'cpd_person',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("  ERROR creating person '{$inst->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    update_post_meta($new_id, '_person_role_instructor', '1');
    update_post_meta($new_id, '_person_phone', get_post_meta($inst->ID, '_tribe_ext_instructor_phone', true));
    update_post_meta($new_id, '_person_website', get_post_meta($inst->ID, '_tribe_ext_instructor_website', true));
    update_post_meta($new_id, '_person_email', get_post_meta($inst->ID, '_tribe_ext_instructor_email_address', true));
    update_post_meta($new_id, '_old_instructor_id', $inst->ID);
    
    if (has_post_thumbnail($inst->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($inst->ID));
    }
    
    $instructor_map[$inst->ID] = $new_id;
    $instructor_count++;
    log_msg("  Migrated: {$inst->post_title} ({$inst->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_instructor_map', $instructor_map);
log_msg("Instructors migrated: {$instructor_count}", 'success');

// Step 5: Migrate series
log_msg('Step 5: Migrating series...', 'info');
$series = get_posts(['post_type' => 'tribe_event_series', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($series) . " series");

$series_map = get_option('vet_cpd_series_map', []);
$series_count = 0;

foreach ($series as $s) {
    if (isset($series_map[$s->ID])) {
        log_msg("  Skipping (already migrated): {$s->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $s->post_title,
        'post_content' => $s->post_content,
        'post_type'    => 'cpd_series',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("  ERROR creating series '{$s->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    if (has_post_thumbnail($s->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($s->ID));
    }
    
    $series_map[$s->ID] = $new_id;
    $series_count++;
    log_msg("  Migrated: {$s->post_title} ({$s->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_series_map', $series_map);
log_msg("Series migrated: {$series_count}", 'success');

// Step 6: Migrate events
log_msg('Step 6: Migrating events to CPDs...', 'info');
$events = get_posts(['post_type' => 'tribe_events', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($events) . " events");

// Reload mappings
$venue_map = get_option('vet_cpd_venue_map', []);
$organizer_map = get_option('vet_cpd_organizer_map', []);

$event_count = 0;
$category_count = 0;

foreach ($events as $event) {
    // Check if already migrated
    $existing = get_posts([
        'post_type'      => 'cpd_event',
        'meta_query'     => [['key' => '_old_event_id', 'value' => $event->ID]],
        'posts_per_page' => 1,
        'fields'         => 'ids'
    ]);
    
    if (!empty($existing)) {
        log_msg("  Skipping (already migrated): {$event->post_title}");
        continue;
    }
    
    $start_date = get_post_meta($event->ID, '_EventStartDate', true);
    $duration = get_post_meta($event->ID, '_EventDuration', true);
    $cpd_hours = $duration ? round($duration / 3600, 1) : '';
    
    $new_id = wp_insert_post([
        'post_title'   => $event->post_title,
        'post_content' => $event->post_content,
        'post_type'    => 'cpd_event',
        'post_status'  => $event->post_status,
        'post_date'    => $event->post_date,
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("  ERROR creating CPD '{$event->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    // Meta fields
    update_post_meta($new_id, '_cpd_provider_url', get_post_meta($event->ID, '_EventURL', true));
    update_post_meta($new_id, '_cpd_date', $start_date);
    update_post_meta($new_id, '_cpd_all_day', get_post_meta($event->ID, '_EventAllDay', true) ?: '0');
    update_post_meta($new_id, '_cpd_hours', $cpd_hours);
    update_post_meta($new_id, '_old_event_id', $event->ID);
    
    // Venue
    $old_venue = get_post_meta($event->ID, '_EventVenueID', true);
    if ($old_venue && isset($venue_map[$old_venue])) {
        update_post_meta($new_id, '_cpd_venues', [$venue_map[$old_venue]]);
    }
    
    // Organizer
    $old_org = get_post_meta($event->ID, '_EventOrganizerID', true);
    if ($old_org && isset($organizer_map[$old_org])) {
        update_post_meta($new_id, '_cpd_organizer', $organizer_map[$old_org]);
    }
    
    // Categories and tags
    $terms = get_the_terms($event->ID, 'tribe_events_cat');
    if ($terms && !is_wp_error($terms)) {
        $cat_slugs = [];
        $tag_slugs = [];
        
        foreach ($terms as $term) {
            // Status categories become tags
            if (in_array($term->slug, ['online', 'on-demand', 'up-coming', 'free'])) {
                $new_slug = ($term->slug === 'up-coming') ? 'upcoming' : $term->slug;
                $tag_slugs[] = $new_slug;
                log_msg("    Category '{$term->name}' → Tag '{$new_slug}'");
            } else {
                // Subject categories stay as categories
                $cat_slugs[] = $term->slug;
                // Create category if not exists
                if (!term_exists($term->slug, 'cpd_category')) {
                    $new_term = wp_insert_term($term->name, 'cpd_category', ['slug' => $term->slug]);
                    if (!is_wp_error($new_term)) {
                        log_msg("    Created category: {$term->name}");
                        $category_count++;
                    }
                }
            }
        }
        
        if (!empty($cat_slugs)) {
            $result = wp_set_object_terms($new_id, $cat_slugs, 'cpd_category');
            if (is_wp_error($result)) {
                log_msg("    ERROR setting categories: " . $result->get_error_message(), 'error');
            } else {
                log_msg("    Set categories: " . implode(', ', $cat_slugs));
            }
        }
        
        if (!empty($tag_slugs)) {
            $result = wp_set_object_terms($new_id, $tag_slugs, 'cpd_tag', true);
            if (is_wp_error($result)) {
                log_msg("    ERROR setting tags: " . $result->get_error_message(), 'error');
            } else {
                log_msg("    Set tags: " . implode(', ', $tag_slugs));
            }
        }
    }
    
    // Featured image
    if (has_post_thumbnail($event->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($event->ID));
    }
    
    $event_count++;
    log_msg("  Migrated: {$event->post_title}", 'success');
}

log_msg("Events migrated: {$event_count}", 'success');
log_msg("New categories created: {$category_count}", 'success');

// Step 7: Fix shortcodes
log_msg('Step 7: Fixing shortcodes...', 'info');
global $wpdb;
$posts = $wpdb->get_results("SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%tribe_events%' AND post_status = 'publish'");
log_msg("Found " . count($posts) . " posts with tribe_events shortcodes");

$replacements = [
    '/\[tribe_events\s+category=["\']on-demand["\']\s*\]/i' => '[cpd_list tag="on-demand"]',
    '/\[tribe_events\s+category=["\']up-coming["\']\s*\]/i' => '[cpd_list tag="upcoming"]',
    '/\[tribe_events\s+category=["\']upcoming["\']\s*\]/i' => '[cpd_list tag="upcoming"]',
    '/\[tribe_events\s+category=["\']free["\']\s*\]/i' => '[cpd_list tag="free"]',
    '/\[tribe_events\s+category=["\']online["\']\s*\]/i' => '[cpd_list tag="online"]',
    '/\[tribe_events\s*\]/i' => '[cpd_list]',
];

$shortcode_count = 0;
foreach ($posts as $post) {
    $content = $post->post_content;
    $changed = false;
    
    foreach ($replacements as $pattern => $replacement) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $changed = true;
        }
    }
    
    if ($changed) {
        wp_update_post(['ID' => $post->ID, 'post_content' => $content]);
        $shortcode_count++;
        log_msg("  Updated: {$post->post_title}", 'success');
    }
}

log_msg("Shortcodes fixed: {$shortcode_count}", 'success');

// Summary
echo '<h2>Migration Complete!</h2>';
echo '<div class="log">';
echo '<p class="success">Venues migrated: ' . count($venue_map) . '</p>';
echo '<p class="success">Organizers migrated: ' . count($organizer_map) . '</p>';
echo '<p class="success">Instructors migrated: ' . count($instructor_map) . '</p>';
echo '<p class="success">Series migrated: ' . count($series_map) . '</p>';
echo '<p class="success">Events migrated: ' . $event_count . '</p>';
echo '<p class="success">Categories created: ' . $category_count . '</p>';
echo '<p class="success">Shortcodes fixed: ' . $shortcode_count . '</p>';
echo '</div>';

echo '<p><strong>Next steps:</strong></p>';
echo '<ul>';
echo '<li>Check /cpd/ to verify events are displaying</li>';
echo '<li>Verify categories and tags are set on migrated events</li>';
echo '<li>Test shortcodes on Free CPD, On Demand CPD, Upcoming CPD pages</li>';
echo '<li>Delete this migration.php file when done</li>';
echo '</ul>';

echo '</body></html>';

// Store log for reference
update_option('vet_cpd_migration_log', $log_messages);
