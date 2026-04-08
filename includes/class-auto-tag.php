<?php
/**
 * Auto-tagging functionality for CPD events
 */

class VET_CPD_Auto_Tag {
    
    const TAG_UPCOMING = 'upcoming';
    const TAG_ON_DEMAND = 'on-demand';
    const TAG_PHYSICAL = 'physical-event';
    const TAG_FREE = 'free';
    
    public static function init() {
        // Apply tags on save (after meta is saved at priority 10)
        add_action('save_post', [__CLASS__, 'apply_tags'], 30, 2);
        
        // Daily cron to update tags
        if (!wp_next_scheduled('vet_cpd_daily_tag_update')) {
            wp_schedule_event(time(), 'daily', 'vet_cpd_daily_tag_update');
        }
        add_action('vet_cpd_daily_tag_update', [__CLASS__, 'daily_update']);
    }
    
    /**
     * Apply appropriate tags based on CPD date and cost
     * Physical events: don't add on-demand tag when past, keep physical-event tag
     * Free events: auto-tag if cost is blank or 0
     */
    public static function apply_tags($post_id) {
        if (get_post_type($post_id) !== VET_CPD_CPD::POST_TYPE) {
            return;
        }
        
        // Avoid infinite loops
        remove_action('save_post', [__CLASS__, 'apply_tags'], 30);
        
        $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
        if (empty($date)) {
            add_action('save_post', [__CLASS__, 'apply_tags'], 30, 2);
            return;
        }
        
        $cpd_timestamp = strtotime($date);
        $now = current_time('timestamp');
        
        // Check if this is a physical event
        $is_physical = has_term(self::TAG_PHYSICAL, VET_CPD_Taxonomies::TAG, $post_id);
        
        // Get current terms to preserve manually checked tags
        $current_terms = wp_get_object_terms($post_id, VET_CPD_Taxonomies::TAG, ['fields' => 'slugs']);
        $current_terms = is_wp_error($current_terms) ? [] : $current_terms;
        
        // Build new terms array
        $new_terms = [];
        
        // Always keep physical-event tag if present
        if ($is_physical) {
            $new_terms[] = self::TAG_PHYSICAL;
        }
        
        // Keep online tag if present
        if (in_array('online', $current_terms)) {
            $new_terms[] = 'online';
        }
        
        // Handle date-based tags
        if ($cpd_timestamp > $now) {
            // Future event - add upcoming tag
            $new_terms[] = self::TAG_UPCOMING;
        } else {
            // Past event
            if (!$is_physical) {
                // Non-physical past events get on-demand tag
                $new_terms[] = self::TAG_ON_DEMAND;
            }
        }
        
        // Handle free tag based on cost
        $cost = VET_CPD_CPD::get_meta($post_id, '_cpd_cost');
        $cost = trim($cost);
        
        // Check if free should be added (blank, 0, or '0')
        if ($cost === '' || $cost === '0' || floatval($cost) == 0) {
            $new_terms[] = self::TAG_FREE;
        }
        
        // Apply all terms at once (replaces existing)
        if (!empty($new_terms)) {
            wp_set_object_terms($post_id, $new_terms, VET_CPD_Taxonomies::TAG, false);
        }
        
        // Restore the action
        add_action('save_post', [__CLASS__, 'apply_tags'], 30, 2);
    }
    
    /**
     * Daily update of all CPD tags
     */
    public static function daily_update() {
        $cpds = get_posts([
            'post_type'      => VET_CPD_CPD::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
        ]);
        
        foreach ($cpds as $cpd_id) {
            self::apply_tags($cpd_id);
        }
    }
}