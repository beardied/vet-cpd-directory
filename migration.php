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

$venues = get_posts(['post_type' => 'tribe_venue', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($venues) . " venues to migrate");

$venue_map = get_option('vet_cpd_venue_map', []);
$venue_count = 0;

foreach ($venues as $venue) {
    // Check if already migrated
    if (isset($venue_map[$venue->ID])) {
        log_msg("ℹ Skipping (already migrated): {$venue->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'  => $venue->post_title,
        'post_type'   => 'cpd_venue',
        'post_status' => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating venue '{$venue->post_title}': " . $new_id->get_error_message(), 'error');
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
    log_msg("✓ Migrated: {$venue->post_title} ({$venue->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_venue_map', $venue_map);
log_msg("✓ Venues migrated: {$venue_count}", 'success');

// Step 3: Migrate organizers
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 3: Migrating organisers...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$organizers = get_posts(['post_type' => 'tribe_organizer', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($organizers) . " organisers to migrate");

$organizer_map = get_option('vet_cpd_organizer_map', []);
$organizer_count = 0;

foreach ($organizers as $org) {
    if (isset($organizer_map[$org->ID])) {
        log_msg("ℹ Skipping (already migrated): {$org->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $org->post_title,
        'post_content' => $org->post_content,
        'post_type'    => 'cpd_person',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating person '{$org->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    update_post_meta($new_id, '_person_role_organizer', '1');
    update_post_meta($new_id, '_person_role_instructor', '0');
    update_post_meta($new_id, '_person_phone', get_post_meta($org->ID, '_OrganizerPhone', true));
    update_post_meta($new_id, '_person_website', get_post_meta($org->ID, '_OrganizerWebsite', true));
    update_post_meta($new_id, '_person_email', get_post_meta($org->ID, '_OrganizerEmail', true));
    update_post_meta($new_id, '_old_organizer_id', $org->ID);
    
    if (has_post_thumbnail($org->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($org->ID));
    }
    
    $organizer_map[$org->ID] = $new_id;
    $organizer_count++;
    log_msg("✓ Migrated: {$org->post_title} ({$org->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_organizer_map', $organizer_map);
log_msg("✓ Organisers migrated: {$organizer_count}", 'success');

// Step 4: Migrate instructors
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 4: Migrating instructors...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$instructors = get_posts(['post_type' => 'tribe_ext_instructor', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($instructors) . " instructors to migrate");

$instructor_map = get_option('vet_cpd_instructor_map', []);
$instructor_count = 0;

foreach ($instructors as $inst) {
    if (isset($instructor_map[$inst->ID])) {
        log_msg("ℹ Skipping (already migrated): {$inst->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $inst->post_title,
        'post_content' => $inst->post_content,
        'post_type'    => 'cpd_person',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating person '{$inst->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    update_post_meta($new_id, '_person_role_organizer', '0');
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
    log_msg("✓ Migrated: {$inst->post_title} ({$inst->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_instructor_map', $instructor_map);
log_msg("✓ Instructors migrated: {$instructor_count}", 'success');

// Step 5: Migrate series
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 5: Migrating series...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$series = get_posts(['post_type' => 'tribe_event_series', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($series) . " series to migrate");

$series_map = get_option('vet_cpd_series_map', []);
$series_count = 0;

foreach ($series as $s) {
    if (isset($series_map[$s->ID])) {
        log_msg("ℹ Skipping (already migrated): {$s->post_title}");
        continue;
    }
    
    $new_id = wp_insert_post([
        'post_title'   => $s->post_title,
        'post_content' => $s->post_content,
        'post_type'    => 'cpd_series',
        'post_status'  => 'publish',
    ]);
    
    if (is_wp_error($new_id)) {
        log_msg("✗ ERROR creating series '{$s->post_title}': " . $new_id->get_error_message(), 'error');
        continue;
    }
    
    if (has_post_thumbnail($s->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($s->ID));
    }
    
    $series_map[$s->ID] = $new_id;
    $series_count++;
    log_msg("✓ Migrated: {$s->post_title} ({$s->ID} → {$new_id})", 'success');
}

update_option('vet_cpd_series_map', $series_map);
log_msg("✓ Series migrated: {$series_count}", 'success');

// Step 6: Migrate events
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 6: Migrating events to CPDs...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

$events = get_posts(['post_type' => 'tribe_events', 'posts_per_page' => -1, 'post_status' => 'any']);
log_msg("Found " . count($events) . " events to migrate");

// Reload mappings
$venue_map = get_option('vet_cpd_venue_map', []);
$organizer_map = get_option('vet_cpd_organizer_map', []);
$instructor_map = get_option('vet_cpd_instructor_map', []);

$event_count = 0;
$category_count = 0;
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
        log_msg("✗ ERROR creating CPD '{$event->post_title}': " . $new_id->get_error_message(), 'error');
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
        log_msg("    → Venue assigned");
    }
    
    // Organizer
    $old_org = get_post_meta($event->ID, '_EventOrganizerID', true);
    if ($old_org && isset($organizer_map[$old_org])) {
        update_post_meta($new_id, '_cpd_organizer', $organizer_map[$old_org]);
        log_msg("    → Organiser assigned");
    }
    
    // Instructors - CRITICAL FIX: Get ALL instructor IDs
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
                log_msg("    → Category '{$term->name}' → Tag '{$new_slug}'");
            } else {
                // Subject categories stay as categories
                $cat_slugs[] = $term->slug;
                // Create category if not exists
                if (!term_exists($term->slug, 'cpd_category')) {
                    $new_term = wp_insert_term($term->name, 'cpd_category', ['slug' => $term->slug]);
                    if (!is_wp_error($new_term)) {
                        log_msg("    → Created category: {$term->name}");
                        $category_count++;
                    }
                }
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
    } else {
        log_msg("    ⚠ No categories/tags found for this event");
    }
    
    // Featured image
    if (has_post_thumbnail($event->ID)) {
        set_post_thumbnail($new_id, get_post_thumbnail_id($event->ID));
    }
    
    $event_count++;
    log_msg("✓ Migrated: {$event->post_title}", 'success');
}

log_msg("✓ Events migrated: {$event_count}", 'success');
log_msg("✓ New categories created: {$category_count}", 'success');
log_msg("✓ Events with categories assigned: {$cat_assignment_count}", 'success');
log_msg("✓ Events with tags assigned: {$tag_assignment_count}", 'success');

// Step 7: Fix shortcodes
log_msg('', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');
log_msg('STEP 7: Fixing shortcodes...', 'info');
log_msg('═══════════════════════════════════════════════════════════════', 'info');

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
        log_msg("✓ Updated: {$post->post_title}", 'success');
    }
}

log_msg("✓ Shortcodes fixed: {$shortcode_count}", 'success');

// Summary
echo '<div class="summary">';
echo '<h2>✅ Migration Complete!</h2>';
echo '<table style="width:100%;font-size:16px;">';
echo '<tr><td>Venues migrated:</td><td><strong>' . count($venue_map) . '</strong></td></tr>';
echo '<tr><td>Organisers migrated:</td><td><strong>' . count($organizer_map) . '</strong></td></tr>';
echo '<tr><td>Instructors migrated:</td><td><strong>' . count($instructor_map) . '</strong></td></tr>';
echo '<tr><td>Series migrated:</td><td><strong>' . count($series_map) . '</strong></td></tr>';
echo '<tr><td>Events migrated:</td><td><strong>' . $event_count . '</strong></td></tr>';
echo '<tr><td>Categories created:</td><td><strong>' . $category_count . '</strong></td></tr>';
echo '<tr><td>Events with categories:</td><td><strong>' . $cat_assignment_count . '</strong></td></tr>';
echo '<tr><td>Events with tags:</td><td><strong>' . $tag_assignment_count . '</strong></td></tr>';
echo '<tr><td>Shortcodes fixed:</td><td><strong>' . $shortcode_count . '</strong></td></tr>';
echo '</table>';
echo '</div>';

echo '<h3>📋 Next steps:</h3>';
echo '<ol>';
echo '<li>Visit <a href="/cpd/" target="_blank">/cpd/</a> to verify events are displaying</li>';
echo '<li>Check a few individual CPD events to verify categories and tags are set</li>';
echo '<li>Test the shortcodes on Free CPD, On Demand CPD, Upcoming CPD pages</li>';
echo '<li>Deactivate The Events Calendar plugins once verified</li>';
echo '<li><strong>Delete this migration.php file when done</strong></li>';
echo '</ol>';

echo '</body></html>';

// Store log for reference
update_option('vet_cpd_migration_log', $log_messages);
