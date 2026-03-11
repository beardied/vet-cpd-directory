<?php
/**
 * Auto-tagging functionality for CPD events
 */

class VET_CPD_Auto_Tag {
    
    const TAG_UPCOMING = 'upcoming';
    const TAG_ON_DEMAND = 'on-demand';
    
    public static function init() {
        // Apply tags on save
        add_action('save_post', [__CLASS__, 'apply_tags'], 20, 2);
        
        // Daily cron to update tags
        if (!wp_next_scheduled('vet_cpd_daily_tag_update')) {
            wp_schedule_event(time(), 'daily', 'vet_cpd_daily_tag_update');
        }
        add_action('vet_cpd_daily_tag_update', [__CLASS__, 'daily_update']);
    }
    
    /**
     * Apply appropriate tags based on CPD date
     */
    public static function apply_tags($post_id) {
        if (get_post_type($post_id) !== VET_CPD_CPD::POST_TYPE) {
            return;
        }
        
        $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
        if (empty($date)) {
            return;
        }
        
        $cpd_timestamp = strtotime($date);
        $now = current_time('timestamp');
        
        // Remove old status tags
        wp_remove_object_terms($post_id, [self::TAG_UPCOMING, self::TAG_ON_DEMAND], VET_CPD_Taxonomies::TAG);
        
        // Apply appropriate tag
        if ($cpd_timestamp > $now) {
            // Future event
            wp_set_object_terms($post_id, self::TAG_UPCOMING, VET_CPD_Taxonomies::TAG, true);
        } else {
            // Past event
            wp_set_object_terms($post_id, self::TAG_ON_DEMAND, VET_CPD_Taxonomies::TAG, true);
        }
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