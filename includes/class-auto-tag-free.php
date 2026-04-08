<?php
/**
 * Auto-tagging for FREE CPD events - Separate from date-based tagging
 * Adds 'free' tag when cost is blank or 0
 */

class VET_CPD_Auto_Tag_Free {
    
    const TAG_FREE = 'free';
    
    public static function init() {
        // Run AFTER checkbox tags are saved (priority 40)
        add_action('save_post', [__CLASS__, 'apply_free_tag'], 40, 2);
    }
    
    /**
     * Apply free tag based on cost
     * Runs after checkbox tags are saved so it ADDS to existing tags
     */
    public static function apply_free_tag($post_id) {
        // Only for CPD events
        if (get_post_type($post_id) !== VET_CPD_CPD::POST_TYPE) {
            return;
        }
        
        // Don't run on autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Get cost value directly from database (most reliable)
        $cost = get_post_meta($post_id, '_cpd_cost', true);
        
        // Trim and sanitize
        $cost = trim($cost);
        
        // Determine if this is a free event
        $is_free = ($cost === '' || $cost === '0' || $cost === 0 || floatval($cost) == 0);
        
        // Check if free tag already exists
        $has_free_tag = has_term(self::TAG_FREE, VET_CPD_Taxonomies::TAG, $post_id);
        
        if ($is_free && !$has_free_tag) {
            // Add free tag (append mode, don't replace existing)
            wp_set_object_terms($post_id, self::TAG_FREE, VET_CPD_Taxonomies::TAG, true);
        } elseif (!$is_free && $has_free_tag) {
            // Remove free tag if cost has value
            wp_remove_object_terms($post_id, self::TAG_FREE, VET_CPD_Taxonomies::TAG);
        }
    }
}
