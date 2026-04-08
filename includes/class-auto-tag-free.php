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
        
        // Output console log in admin
        add_action('admin_footer', [__CLASS__, 'output_console_log']);
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
            $result = wp_set_object_terms($post_id, self::TAG_FREE, VET_CPD_Taxonomies::TAG, true);
            
            // Store message for console output
            set_transient('cpd_free_tag_log_' . $post_id, [
                'action' => 'ADDED',
                'cost' => var_export($cost, true),
                'result' => is_wp_error($result) ? 'ERROR' : 'SUCCESS'
            ], 30);
            
        } elseif (!$is_free && $has_free_tag) {
            // Remove free tag if cost has value
            wp_remove_object_terms($post_id, self::TAG_FREE, VET_CPD_Taxonomies::TAG);
            
            // Store message for console output
            set_transient('cpd_free_tag_log_' . $post_id, [
                'action' => 'REMOVED',
                'cost' => var_export($cost, true),
                'result' => 'SUCCESS'
            ], 30);
        } else {
            // Store message for console output - no change needed
            set_transient('cpd_free_tag_log_' . $post_id, [
                'action' => 'NO_CHANGE',
                'is_free' => $is_free,
                'has_tag' => $has_free_tag,
                'cost' => var_export($cost, true)
            ], 30);
        }
    }
    
    /**
     * Output console log JavaScript in admin
     */
    public static function output_console_log() {
        // Only on CPD edit screen
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'cpd_event') {
            return;
        }
        
        // Get post ID
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if (!$post_id) {
            return;
        }
        
        // Get log message
        $log = get_transient('cpd_free_tag_log_' . $post_id);
        if (!$log) {
            return;
        }
        
        // Delete transient
        delete_transient('cpd_free_tag_log_' . $post_id);
        
        // Output JavaScript console log
        ?>
        <script type="text/javascript">
            console.log('%c[CPD Free Tag Auto-Tagger]', 'color: #0d8f4f; font-weight: bold; font-size: 14px;');
            console.log('Action: <?php echo esc_js($log['action']); ?>');
            console.log('Cost Value: <?php echo esc_js($log['cost']); ?>');
            <?php if (isset($log['result'])) : ?>
            console.log('Result: <?php echo esc_js($log['result']); ?>');
            <?php endif; ?>
            <?php if (isset($log['is_free'])) : ?>
            console.log('Is Free: <?php echo $log['is_free'] ? 'true' : 'false'; ?>');
            console.log('Has Free Tag: <?php echo $log['has_tag'] ? 'true' : 'false'; ?>');
            <?php endif; ?>
            console.log('---');
        </script>
        <?php
    }
}
