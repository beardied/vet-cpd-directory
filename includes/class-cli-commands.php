<?php
/**
 * WP-CLI Migration Command
 * 
 * Usage: wp vet-cpd migrate
 */

if (!defined('WP_CLI')) {
    return;
}

WP_CLI::add_command('vet-cpd', 'VET_CPD_CLI_Command');

class VET_CPD_CLI_Command extends WP_CLI_Command {
    
    /**
     * Run the full migration
     */
    public function migrate($args, $assoc_args) {
        WP_CLI::log('=== Vet CPD Migration Started ===');
        
        // Ensure plugin classes are loaded
        if (!class_exists('VET_CPD_Taxonomies')) {
            WP_CLI::error('Vet CPD Directory plugin not active!');
        }
        
        // Step 1: Create system tags
        WP_CLI::log("\nStep 1: Creating system tags...");
        $this->create_system_tags();
        
        // Step 2: Migrate venues
        WP_CLI::log("\nStep 2: Migrating venues...");
        $venue_map = $this->migrate_venues();
        
        // Step 3: Migrate organizers
        WP_CLI::log("\nStep 3: Migrating organizers...");
        $organizer_map = $this->migrate_organizers();
        
        // Step 4: Migrate instructors
        WP_CLI::log("\nStep 4: Migrating instructors...");
        $instructor_map = $this->migrate_instructors();
        
        // Step 5: Migrate series
        WP_CLI::log("\nStep 5: Migrating series...");
        $series_map = $this->migrate_series();
        
        // Step 6: Migrate events
        WP_CLI::log("\nStep 6: Migrating events...");
        $event_count = $this->migrate_events();
        
        WP_CLI::success("\n=== Migration Complete ===");
        WP_CLI::log("Venues: " . count($venue_map));
        WP_CLI::log("Organizers: " . count($organizer_map));
        WP_CLI::log("Instructors: " . count($instructor_map));
        WP_CLI::log("Series: " . count($series_map));
        WP_CLI::log("Events: {$event_count}");
    }
    
    /**
     * Fix shortcodes in posts
     */
    public function fix_shortcodes($args, $assoc_args) {
        WP_CLI::log('=== Fixing Shortcodes ===');
        
        global $wpdb;
        
        // Find posts with tribe_events
        $posts = $wpdb->get_results(
            "SELECT ID, post_title, post_content FROM {$wpdb->posts} 
             WHERE post_content LIKE '%tribe_events%' 
             AND post_status = 'publish'"
        );
        
        WP_CLI::log("Found " . count($posts) . " posts with tribe_events");
        
        $replacements = [
            '/\[tribe_events\s+category=["\']on-demand["\']\s*\]/i' => '[cpd_list tag="on-demand"]',
            '/\[tribe_events\s+category=["\']up-coming["\']\s*\]/i' => '[cpd_list tag="upcoming"]',
            '/\[tribe_events\s+category=["\']upcoming["\']\s*\]/i' => '[cpd_list tag="upcoming"]',
            '/\[tribe_events\s+category=["\']free["\']\s*\]/i' => '[cpd_list tag="free"]',
            '/\[tribe_events\s+category=["\']online["\']\s*\]/i' => '[cpd_list tag="online"]',
            '/\[tribe_events\s*\]/i' => '[cpd_list]',
        ];
        
        $updated = 0;
        
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
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $content,
                ]);
                $updated++;
                WP_CLI::log("Updated: {$post->post_title}");
            }
        }
        
        WP_CLI::success("Updated {$updated} posts");
    }
    
    private function create_system_tags() {
        $tags = ['upcoming', 'on-demand', 'online', 'free'];
        foreach ($tags as $tag) {
            if (!term_exists($tag, 'cpd_tag')) {
                wp_insert_term($tag, 'cpd_tag', ['slug' => $tag]);
                WP_CLI::log("  Created tag: {$tag}");
            }
        }
    }
    
    private function migrate_venues() {
        $venues = get_posts(['post_type' => 'tribe_venue', 'posts_per_page' => -1, 'post_status' => 'any']);
        $map = [];
        
        foreach ($venues as $venue) {
            $existing = get_posts(['post_type' => 'cpd_venue', 'title' => $venue->post_title, 'posts_per_page' => 1, 'fields' => 'ids']);
            if (!empty($existing)) {
                $map[$venue->ID] = $existing[0];
                continue;
            }
            
            $new_id = wp_insert_post(['post_title' => $venue->post_title, 'post_type' => 'cpd_venue', 'post_status' => 'publish']);
            if ($new_id) {
                update_post_meta($new_id, '_venue_address', get_post_meta($venue->ID, '_VenueAddress', true));
                update_post_meta($new_id, '_venue_city', get_post_meta($venue->ID, '_VenueCity', true));
                update_post_meta($new_id, '_venue_country', get_post_meta($venue->ID, '_VenueCountry', true));
                update_post_meta($new_id, '_venue_state', get_post_meta($venue->ID, '_VenueStateProvince', true));
                update_post_meta($new_id, '_venue_postal_code', get_post_meta($venue->ID, '_VenueZip', true));
                update_post_meta($new_id, '_venue_phone', get_post_meta($venue->ID, '_VenuePhone', true));
                update_post_meta($new_id, '_venue_website', get_post_meta($venue->ID, '_VenueURL', true));
                $map[$venue->ID] = $new_id;
                WP_CLI::log("  Migrated: {$venue->post_title}");
            }
        }
        update_option('vet_cpd_venue_map', $map);
        return $map;
    }
    
    private function migrate_organizers() {
        $organizers = get_posts(['post_type' => 'tribe_organizer', 'posts_per_page' => -1, 'post_status' => 'any']);
        $map = [];
        
        foreach ($organizers as $org) {
            $existing = get_posts(['post_type' => 'cpd_person', 'meta_query' => [['key' => '_old_organizer_id', 'value' => $org->ID]], 'posts_per_page' => 1, 'fields' => 'ids']);
            if (!empty($existing)) {
                $map[$org->ID] = $existing[0];
                continue;
            }
            
            $new_id = wp_insert_post(['post_title' => $org->post_title, 'post_content' => $org->post_content, 'post_type' => 'cpd_person', 'post_status' => 'publish']);
            if ($new_id) {
                update_post_meta($new_id, '_person_role_organizer', '1');
                update_post_meta($new_id, '_person_phone', get_post_meta($org->ID, '_OrganizerPhone', true));
                update_post_meta($new_id, '_person_website', get_post_meta($org->ID, '_OrganizerWebsite', true));
                update_post_meta($new_id, '_person_email', get_post_meta($org->ID, '_OrganizerEmail', true));
                update_post_meta($new_id, '_old_organizer_id', $org->ID);
                $map[$org->ID] = $new_id;
                WP_CLI::log("  Migrated: {$org->post_title}");
            }
        }
        update_option('vet_cpd_organizer_map', $map);
        return $map;
    }
    
    private function migrate_instructors() {
        $instructors = get_posts(['post_type' => 'tribe_ext_instructor', 'posts_per_page' => -1, 'post_status' => 'any']);
        $map = [];
        
        foreach ($instructors as $inst) {
            $existing = get_posts(['post_type' => 'cpd_person', 'meta_query' => [['key' => '_old_instructor_id', 'value' => $inst->ID]], 'posts_per_page' => 1, 'fields' => 'ids']);
            if (!empty($existing)) {
                $map[$inst->ID] = $existing[0];
                continue;
            }
            
            $new_id = wp_insert_post(['post_title' => $inst->post_title, 'post_content' => $inst->post_content, 'post_type' => 'cpd_person', 'post_status' => 'publish']);
            if ($new_id) {
                update_post_meta($new_id, '_person_role_instructor', '1');
                update_post_meta($new_id, '_person_phone', get_post_meta($inst->ID, '_tribe_ext_instructor_phone', true));
                update_post_meta($new_id, '_person_website', get_post_meta($inst->ID, '_tribe_ext_instructor_website', true));
                update_post_meta($new_id, '_person_email', get_post_meta($inst->ID, '_tribe_ext_instructor_email_address', true));
                update_post_meta($new_id, '_old_instructor_id', $inst->ID);
                $map[$inst->ID] = $new_id;
                WP_CLI::log("  Migrated: {$inst->post_title}");
            }
        }
        update_option('vet_cpd_instructor_map', $map);
        return $map;
    }
    
    private function migrate_series() {
        $series = get_posts(['post_type' => 'tribe_event_series', 'posts_per_page' => -1, 'post_status' => 'any']);
        $map = [];
        
        foreach ($series as $s) {
            $existing = get_posts(['post_type' => 'cpd_series', 'title' => $s->post_title, 'posts_per_page' => 1, 'fields' => 'ids']);
            if (!empty($existing)) {
                $map[$s->ID] = $existing[0];
                continue;
            }
            
            $new_id = wp_insert_post(['post_title' => $s->post_title, 'post_content' => $s->post_content, 'post_type' => 'cpd_series', 'post_status' => 'publish']);
            if ($new_id) {
                $map[$s->ID] = $new_id;
                WP_CLI::log("  Migrated: {$s->post_title}");
            }
        }
        update_option('vet_cpd_series_map', $map);
        return $map;
    }
    
    private function migrate_events() {
        $events = get_posts(['post_type' => 'tribe_events', 'posts_per_page' => -1, 'post_status' => 'any']);
        $venue_map = get_option('vet_cpd_venue_map', []);
        $organizer_map = get_option('vet_cpd_organizer_map', []);
        
        $count = 0;
        
        foreach ($events as $event) {
            $existing = get_posts(['post_type' => 'cpd_event', 'meta_query' => [['key' => '_old_event_id', 'value' => $event->ID]], 'posts_per_page' => 1, 'fields' => 'ids']);
            if (!empty($existing)) continue;
            
            $start_date = get_post_meta($event->ID, '_EventStartDate', true);
            $duration = get_post_meta($event->ID, '_EventDuration', true);
            $cpd_hours = $duration ? round($duration / 3600, 1) : '';
            
            $new_id = wp_insert_post([
                'post_title' => $event->post_title,
                'post_content' => $event->post_content,
                'post_type' => 'cpd_event',
                'post_status' => $event->post_status,
            ]);
            
            if ($new_id) {
                update_post_meta($new_id, '_cpd_provider_url', get_post_meta($event->ID, '_EventURL', true));
                update_post_meta($new_id, '_cpd_date', $start_date);
                update_post_meta($new_id, '_cpd_all_day', get_post_meta($event->ID, '_EventAllDay', true) ?: '0');
                update_post_meta($new_id, '_cpd_hours', $cpd_hours);
                update_post_meta($new_id, '_old_event_id', $event->ID);
                
                $old_venue = get_post_meta($event->ID, '_EventVenueID', true);
                if ($old_venue && isset($venue_map[$old_venue])) {
                    update_post_meta($new_id, '_cpd_venues', [$venue_map[$old_venue]]);
                }
                
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
                        if (in_array($term->slug, ['online', 'on-demand', 'up-coming', 'free'])) {
                            $tag_slugs[] = ($term->slug === 'up-coming') ? 'upcoming' : $term->slug;
                        } else {
                            $cat_slugs[] = $term->slug;
                            if (!term_exists($term->slug, 'cpd_category')) {
                                wp_insert_term($term->name, 'cpd_category', ['slug' => $term->slug]);
                            }
                        }
                    }
                    if (!empty($cat_slugs)) wp_set_object_terms($new_id, $cat_slugs, 'cpd_category');
                    if (!empty($tag_slugs)) wp_set_object_terms($new_id, $tag_slugs, 'cpd_tag', true);
                }
                
                if (has_post_thumbnail($event->ID)) {
                    set_post_thumbnail($new_id, get_post_thumbnail_id($event->ID));
                }
                
                $count++;
                WP_CLI::log("  Migrated: {$event->post_title}");
            }
        }
        return $count;
    }
}
