<?php
/**
 * Template helper functions
 */

class VET_CPD_Template {
    
    /**
     * Format date display with start and end dates
     */
    public static function get_formatted_date($start_date, $end_date, $all_day = false) {
        if (empty($start_date)) {
            return '';
        }
        
        $date_format = get_option('date_format');
        $time_format = get_option('time_format');
        
        if ($all_day) {
            if (!empty($end_date) && $end_date !== $start_date) {
                // Multi-day all-day event
                $start = date_i18n($date_format, strtotime($start_date));
                $end = date_i18n($date_format, strtotime($end_date));
                return sprintf(__('%s - %s', 'vet-cpd-directory'), $start, $end);
            } else {
                // Single all-day event
                return date_i18n($date_format, strtotime($start_date));
            }
        } else {
            if (!empty($end_date) && date('Y-m-d', strtotime($start_date)) !== date('Y-m-d', strtotime($end_date))) {
                // Multi-day event with times
                $start = date_i18n($date_format . ' ' . $time_format, strtotime($start_date));
                $end = date_i18n($date_format . ' ' . $time_format, strtotime($end_date));
                return sprintf(__('%s - %s', 'vet-cpd-directory'), $start, $end);
            } else {
                // Single day event
                $start_time = date_i18n($time_format, strtotime($start_date));
                $end_time = !empty($end_date) ? date_i18n($time_format, strtotime($end_date)) : '';
                
                $date = date_i18n($date_format, strtotime($start_date));
                
                if ($end_time && $end_time !== $start_time) {
                    return sprintf(__('%s, %s - %s', 'vet-cpd-directory'), $date, $start_time, $end_time);
                } else {
                    return sprintf(__('%s, %s', 'vet-cpd-directory'), $date, $start_time);
                }
            }
        }
    }
    
    /**
     * Format cost display
     */
    public static function format_cost($cost, $currency = 'GBP') {
        if ($cost === '' || $cost === null) {
            return __('Free', 'vet-cpd-directory');
        }
        
        $symbols = [
            'GBP' => '£',
            'EUR' => '€',
            'USD' => '$',
        ];
        
        $symbol = $symbols[$currency] ?? $currency . ' ';
        
        return $symbol . $cost;
    }
    
    /**
     * Get CPD type badges
     */
    public static function get_type_badges($post_id) {
        $types = get_the_terms($post_id, 'cpd_type');
        if (empty($types) || is_wp_error($types)) {
            return '';
        }
        
        $badges = [];
        foreach ($types as $type) {
            $badges[] = sprintf(
                '<span class="cpd-type-badge cpd-type-%s">%s</span>',
                esc_attr($type->slug),
                esc_html($type->name)
            );
        }
        
        return implode(' ', $badges);
    }
}
