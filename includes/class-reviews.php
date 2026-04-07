<?php
/**
 * CPD Reviews Class
 * Handles review storage, retrieval, and management
 */

if (!defined('ABSPATH')) {
    exit;
}

class VET_CPD_Reviews {
    
    private static $table_name;
    
    /**
     * Initialize the reviews system
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'cpd_reviews';
        
        // Register hooks
        add_action('init', [__CLASS__, 'check_table_exists']);
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_cpd_submit_review', [__CLASS__, 'ajax_submit_review']);
        add_action('wp_ajax_nopriv_cpd_submit_review', [__CLASS__, 'ajax_submit_review']);
        add_action('wp_ajax_cpd_approve_review', [__CLASS__, 'ajax_approve_review']);
        add_action('wp_ajax_cpd_trash_review', [__CLASS__, 'ajax_trash_review']);
        add_action('admin_post_cpd_email_approve_review', [__CLASS__, 'email_approve_review']);
        add_action('admin_post_cpd_email_trash_review', [__CLASS__, 'email_trash_review']);
        
        // Register shortcodes
        add_shortcode('cpd_recent_reviews', [__CLASS__, 'shortcode_recent_reviews']);
        add_shortcode('cpd_reviews_page', [__CLASS__, 'shortcode_reviews_page']);
    }
    
    /**
     * Create the reviews database table
     */
    public static function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = self::$table_name;
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cpd_event_id bigint(20) NOT NULL,
            reviewer_name varchar(100) NOT NULL,
            reviewer_email varchar(100) DEFAULT NULL,
            review_comment text NOT NULL,
            star_rating tinyint(1) NOT NULL DEFAULT 5,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY cpd_event_id (cpd_event_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if table exists, create if not
     */
    public static function check_table_exists() {
        global $wpdb;
        $table_name = self::$table_name;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            self::create_table();
        }
    }
    
    /**
     * Add admin menu for reviews
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=cpd_event',
            'CPD Reviews',
            'Reviews',
            'manage_options',
            'cpd-reviews',
            [__CLASS__, 'render_admin_page']
        );
    }
    
    /**
     * Submit a new review
     */
    public static function submit_review($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            self::$table_name,
            [
                'cpd_event_id' => intval($data['cpd_event_id']),
                'reviewer_name' => sanitize_text_field($data['reviewer_name']),
                'reviewer_email' => sanitize_email($data['reviewer_email']),
                'review_comment' => sanitize_textarea_field($data['review_comment']),
                'star_rating' => intval($data['star_rating']),
                'status' => 'pending',
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s']
        );
        
        if ($result === false) {
            return false;
        }
        
        $review_id = $wpdb->insert_id;
        
        // Send notification email to staff
        self::send_staff_notification($review_id);
        
        return $review_id;
    }
    
    /**
     * Get a single review by ID
     */
    public static function get_review($review_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            $review_id
        ));
    }
    
    /**
     * Get reviews for a specific CPD event
     */
    public static function get_reviews_for_event($event_id, $status = 'approved', $limit = null) {
        global $wpdb;
        
        $sql = "SELECT * FROM " . self::$table_name . " WHERE cpd_event_id = %d AND status = %s ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        return $wpdb->get_results($wpdb->prepare($sql, $event_id, $status));
    }
    
    /**
     * Get recent reviews across all events
     */
    public static function get_recent_reviews($limit = 3, $status = 'approved') {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.post_title as cpd_title 
            FROM " . self::$table_name . " r 
            LEFT JOIN {$wpdb->posts} p ON r.cpd_event_id = p.ID 
            WHERE r.status = %s AND p.post_status = 'publish'
            ORDER BY r.created_at DESC 
            LIMIT %d",
            $status,
            $limit
        ));
    }
    
    /**
     * Get all reviews for admin
     */
    public static function get_all_reviews($status = null, $per_page = 20, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($status) {
            $where = $wpdb->prepare(" WHERE r.status = %s", $status);
        }
        
        $sql = "SELECT r.*, p.post_title as cpd_title 
            FROM " . self::$table_name . " r 
            LEFT JOIN {$wpdb->posts} p ON r.cpd_event_id = p.ID 
            $where
            ORDER BY r.created_at DESC 
            LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
    }
    
    /**
     * Count reviews by status
     */
    public static function count_reviews($status = null) {
        global $wpdb;
        
        $sql = "SELECT COUNT(*) FROM " . self::$table_name;
        
        if ($status) {
            $sql .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Approve a review
     */
    public static function approve_review($review_id) {
        global $wpdb;
        
        return $wpdb->update(
            self::$table_name,
            ['status' => 'approved'],
            ['id' => $review_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Trash a review
     */
    public static function trash_review($review_id) {
        global $wpdb;
        
        return $wpdb->update(
            self::$table_name,
            ['status' => 'trashed'],
            ['id' => $review_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Delete a review permanently
     */
    public static function delete_review($review_id) {
        global $wpdb;
        
        return $wpdb->delete(
            self::$table_name,
            ['id' => $review_id],
            ['%d']
        );
    }
    
    /**
     * Get average rating for a CPD event
     */
    public static function get_average_rating($event_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(star_rating) FROM " . self::$table_name . " WHERE cpd_event_id = %d AND status = 'approved'",
            $event_id
        ));
    }
    
    /**
     * Get review count for a CPD event
     */
    public static function get_review_count($event_id, $status = 'approved') {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . self::$table_name . " WHERE cpd_event_id = %d AND status = %s",
            $event_id,
            $status
        ));
    }
    
    /**
     * Send notification email to staff
     */
    private static function send_staff_notification($review_id) {
        $review = self::get_review($review_id);
        if (!$review) {
            return false;
        }
        
        $cpd_event = get_post($review->cpd_event_id);
        if (!$cpd_event) {
            return false;
        }
        
        $to = get_option('cpd_review_notification_email', get_option('admin_email'));
        
        $subject = sprintf(__('New CPD Review Submitted - %s', 'vet-cpd-directory'), $cpd_event->post_title);
        
        // Build styled email
        $message = self::build_staff_email($review, $cpd_event);
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Build staff notification email HTML
     */
    private static function build_staff_email($review, $cpd_event) {
        $approve_url = admin_url('admin-post.php?action=cpd_email_approve_review&review_id=' . $review->id . '&nonce=' . wp_create_nonce('cpd_review_' . $review->id));
        $trash_url = admin_url('admin-post.php?action=cpd_email_trash_review&review_id=' . $review->id . '&nonce=' . wp_create_nonce('cpd_review_' . $review->id));
        $admin_url = admin_url('edit.php?post_type=cpd_event&page=cpd-reviews');
        
        $stars = str_repeat('★', $review->star_rating) . str_repeat('☆', 5 - $review->star_rating);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d8f4f; padding: 30px; text-align: center; border-radius: 12px 12px 0 0; }
                .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
                .content { background: #f7fafc; padding: 30px; border-radius: 0 0 12px 12px; }
                .review-box { background: #ffffff; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #0d8f4f; }
                .stars { color: #ffc107; font-size: 20px; margin: 10px 0; }
                .reviewer { font-weight: 600; color: #0d8f4f; }
                .comment { font-style: italic; margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 6px; }
                .cpd-title { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
                .buttons { margin-top: 30px; text-align: center; }
                .btn { display: inline-block; padding: 15px 30px; margin: 0 10px; text-decoration: none; border-radius: 8px; font-weight: 600; }
                .btn-approve { background: #0d8f4f; color: #ffffff; }
                .btn-trash { background: #dc3545; color: #ffffff; }
                .btn-admin { background: #6c757d; color: #ffffff; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>New CPD Review Submitted</h1>
                </div>
                <div class="content">
                    <p>A new review has been submitted for the following CPD event and is awaiting your approval.</p>
                    
                    <div class="cpd-title"><?php echo esc_html($cpd_event->post_title); ?></div>
                    
                    <div class="review-box">
                        <div class="reviewer"><?php echo esc_html($review->reviewer_name); ?></div>
                        <div class="stars"><?php echo $stars; ?></div>
                        <div class="comment">"<?php echo nl2br(esc_html($review->review_comment)); ?>"</div>
                        <small style="color: #666;">Submitted: <?php echo date_i18n('j M Y g:i a', strtotime($review->created_at)); ?></small>
                    </div>
                    
                    <div class="buttons">
                        <a href="<?php echo esc_url($approve_url); ?>" class="btn btn-approve">✓ Approve Review</a>
                        <a href="<?php echo esc_url($trash_url); ?>" class="btn btn-trash">✗ Bin Review</a>
                        <a href="<?php echo esc_url($admin_url); ?>" class="btn btn-admin">View All Reviews</a>
                    </div>
                    
                    <div class="footer">
                        <p>This is an automated email from Wendy Nevins CPD Directory.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle email approve action
     */
    public static function email_approve_review() {
        $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        
        if (!$review_id || !wp_verify_nonce($nonce, 'cpd_review_' . $review_id)) {
            wp_die(__('Invalid request', 'vet-cpd-directory'));
        }
        
        self::approve_review($review_id);
        
        wp_redirect(admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&message=approved'));
        exit;
    }
    
    /**
     * Handle email trash action
     */
    public static function email_trash_review() {
        $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
        
        if (!$review_id || !wp_verify_nonce($nonce, 'cpd_review_' . $review_id)) {
            wp_die(__('Invalid request', 'vet-cpd-directory'));
        }
        
        self::trash_review($review_id);
        
        wp_redirect(admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&message=trashed'));
        exit;
    }
    
    /**
     * AJAX handler for submitting review
     */
    public static function ajax_submit_review() {
        check_ajax_referer('cpd_review_nonce', 'nonce');
        
        // Verify CAPTCHA
        $captcha = isset($_POST['captcha_answer']) ? intval($_POST['captcha_answer']) : 0;
        $expected = isset($_POST['captcha_expected']) ? intval($_POST['captcha_expected']) : 0;
        
        if ($captcha !== $expected) {
            wp_send_json_error(['message' => __('CAPTCHA verification failed. Please try again.', 'vet-cpd-directory')]);
        }
        
        $data = [
            'cpd_event_id' => intval($_POST['cpd_event_id']),
            'reviewer_name' => sanitize_text_field($_POST['reviewer_name']),
            'reviewer_email' => sanitize_email($_POST['reviewer_email']),
            'review_comment' => sanitize_textarea_field($_POST['review_comment']),
            'star_rating' => intval($_POST['star_rating']),
        ];
        
        $review_id = self::submit_review($data);
        
        if ($review_id) {
            wp_send_json_success(['message' => __('Thank you for your review! It will be displayed after moderation.', 'vet-cpd-directory')]);
        } else {
            wp_send_json_error(['message' => __('Failed to submit review. Please try again.', 'vet-cpd-directory')]);
        }
    }
    
    /**
     * AJAX handler for approving review
     */
    public static function ajax_approve_review() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'vet-cpd-directory')]);
        }
        
        $review_id = intval($_POST['review_id']);
        
        if (self::approve_review($review_id)) {
            wp_send_json_success(['message' => __('Review approved', 'vet-cpd-directory')]);
        } else {
            wp_send_json_error(['message' => __('Failed to approve review', 'vet-cpd-directory')]);
        }
    }
    
    /**
     * AJAX handler for trashing review
     */
    public static function ajax_trash_review() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Unauthorized', 'vet-cpd-directory')]);
        }
        
        $review_id = intval($_POST['review_id']);
        
        if (self::trash_review($review_id)) {
            wp_send_json_success(['message' => __('Review moved to trash', 'vet-cpd-directory')]);
        } else {
            wp_send_json_error(['message' => __('Failed to trash review', 'vet-cpd-directory')]);
        }
    }
    
    /**
     * Render review form
     */
    public static function render_review_form($event_id) {
        // Generate simple math CAPTCHA
        $num1 = wp_rand(1, 10);
        $num2 = wp_rand(1, 10);
        $expected = $num1 + $num2;
        
        ob_start();
        ?>
        <div class="cpd-review-form-section" id="cpd-review-form">
            <h3 class="cpd-review-title">What did you think of this CPD?</h3>
            <p class="cpd-review-subtitle">Leave a review to help others choose the right course</p>
            
            <form class="cpd-review-form" method="post">
                <input type="hidden" name="cpd_event_id" value="<?php echo esc_attr($event_id); ?>">
                <input type="hidden" name="captcha_expected" value="<?php echo esc_attr($expected); ?>">
                <?php wp_nonce_field('cpd_review_nonce', 'cpd_review_nonce'); ?>
                
                <div class="cpd-form-group">
                    <label for="reviewer_name">Your Name *</label>
                    <input type="text" id="reviewer_name" name="reviewer_name" required>
                </div>
                
                <div class="cpd-form-group">
                    <label for="reviewer_email">Your Email (not published) *</label>
                    <input type="email" id="reviewer_email" name="reviewer_email" required>
                </div>
                
                <div class="cpd-form-group">
                    <label>Your Rating *</label>
                    <div class="cpd-star-rating-input">
                        <?php for ($i = 5; $i >= 1; $i--) : ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="star_rating" value="<?php echo $i; ?>" <?php echo $i === 5 ? 'required' : ''; ?>>
                            <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="cpd-form-group">
                    <label for="review_comment">Your Review *</label>
                    <textarea id="review_comment" name="review_comment" rows="4" required placeholder="Share your experience with this CPD course..."></textarea>
                </div>
                
                <div class="cpd-form-group cpd-captcha">
                    <label for="captcha_answer">Security Check: What is <?php echo $num1; ?> + <?php echo $num2; ?>? *</label>
                    <input type="number" id="captcha_answer" name="captcha_answer" required>
                </div>
                
                <button type="submit" class="cpd-submit-review-btn">Submit Review</button>
            </form>
            
            <div class="cpd-review-message" style="display: none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render reviews list for a CPD event
     */
    public static function render_reviews_list($event_id) {
        $reviews = self::get_reviews_for_event($event_id, 'approved');
        
        if (empty($reviews)) {
            return '<p class="cpd-no-reviews">No reviews yet. Be the first to leave a review!</p>';
        }
        
        $average = self::get_average_rating($event_id);
        $count = count($reviews);
        
        ob_start();
        ?>
        <div class="cpd-reviews-section">
            <div class="cpd-reviews-header">
                <h3>Reviews</h3>
                <div class="cpd-reviews-summary">
                    <div class="cpd-average-rating">
                        <span class="cpd-average-number"><?php echo number_format($average, 1); ?></span>
                        <span class="cpd-stars"><?php echo str_repeat('★', round($average)); ?></span>
                        <span class="cpd-review-count"><?php echo $count; ?> review<?php echo $count !== 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="cpd-reviews-list">
                <?php foreach ($reviews as $review) : ?>
                    <div class="cpd-review-item">
                        <div class="cpd-review-header">
                            <span class="cpd-reviewer-name"><?php echo esc_html($review->reviewer_name); ?></span>
                            <span class="cpd-review-date"><?php echo date_i18n('j M Y', strtotime($review->created_at)); ?></span>
                        </div>
                        <div class="cpd-review-stars"><?php echo str_repeat('★', $review->star_rating); ?></div>
                        <div class="cpd-review-comment"><?php echo nl2br(esc_html($review->review_comment)); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_recent_reviews]
     */
    public static function shortcode_recent_reviews($atts) {
        $atts = shortcode_atts([
            'count' => 3,
        ], $atts, 'cpd_recent_reviews');
        
        $reviews = self::get_recent_reviews(intval($atts['count']));
        
        if (empty($reviews)) {
            return ''; // Return empty if no reviews
        }
        
        ob_start();
        ?>
        <div class="cpd-recent-reviews">
            <h3 class="cpd-recent-reviews-title">Recent Reviews</h3>
            <div class="cpd-recent-reviews-grid">
                <?php foreach ($reviews as $review) : ?>
                    <div class="cpd-recent-review-card">
                        <div class="cpd-recent-review-stars"><?php echo str_repeat('★', $review->star_rating); ?></div>
                        <div class="cpd-recent-review-text">"<?php echo esc_html($review->review_comment); ?>"</div>
                        <div class="cpd-recent-review-meta">
                            <span class="cpd-recent-reviewer">— <?php echo esc_html($review->reviewer_name); ?></span>
                            <a href="<?php echo esc_url(get_permalink($review->cpd_event_id)); ?>" class="cpd-recent-cpd-link">
                                <?php echo esc_html($review->cpd_title); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cpd-view-all-reviews">
                <a href="<?php echo esc_url(home_url('/reviews')); ?>" class="cpd-view-all-link">View All Reviews</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode: [cpd_reviews_page]
     */
    public static function shortcode_reviews_page($atts) {
        $per_page = 10;
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        $offset = ($paged - 1) * $per_page;
        
        global $wpdb;
        $table_name = self::$table_name;
        
        $reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.post_title as cpd_title 
            FROM $table_name r 
            LEFT JOIN {$wpdb->posts} p ON r.cpd_event_id = p.ID 
            WHERE r.status = 'approved' AND p.post_status = 'publish'
            ORDER BY r.created_at DESC 
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'approved'");
        $total_pages = ceil($total / $per_page);
        
        ob_start();
        ?>
        <div class="cpd-reviews-page">
            <h1>All CPD Reviews</h1>
            
            <?php if (empty($reviews)) : ?>
                <p>No reviews yet.</p>
            <?php else : ?>
                <div class="cpd-all-reviews-list">
                    <?php foreach ($reviews as $review) : ?>
                        <div class="cpd-review-page-item">
                            <div class="cpd-review-page-header">
                                <span class="cpd-review-page-stars"><?php echo str_repeat('★', $review->star_rating); ?></span>
                                <span class="cpd-review-page-date"><?php echo date_i18n('j M Y', strtotime($review->created_at)); ?></span>
                            </div>
                            <div class="cpd-review-page-comment"><?php echo nl2br(esc_html($review->review_comment)); ?></div>
                            <div class="cpd-review-page-meta">
                                <span class="cpd-review-page-reviewer"><?php echo esc_html($review->reviewer_name); ?></span>
                                <span class="cpd-review-page-cpd">
                                    reviewed <a href="<?php echo esc_url(get_permalink($review->cpd_event_id)); ?>"><?php echo esc_html($review->cpd_title); ?></a>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1) : ?>
                    <div class="cpd-reviews-pagination">
                        <?php
                        echo paginate_links([
                            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format' => '?paged=%#%',
                            'current' => $paged,
                            'total' => $total_pages,
                            'prev_text' => '&laquo; Previous',
                            'next_text' => 'Next &raquo;',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render admin page
     */
    public static function render_admin_page() {
        global $wpdb;
        
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
        
        // Handle actions
        if ($action && $review_id) {
            check_admin_referer('cpd_review_action');
            
            if ($action === 'approve') {
                self::approve_review($review_id);
                echo '<div class="notice notice-success"><p>Review approved.</p></div>';
            } elseif ($action === 'trash') {
                self::trash_review($review_id);
                echo '<div class="notice notice-success"><p>Review moved to trash.</p></div>';
            } elseif ($action === 'delete') {
                self::delete_review($review_id);
                echo '<div class="notice notice-success"><p>Review deleted permanently.</p></div>';
            }
        }
        
        // Get counts
        $pending_count = self::count_reviews('pending');
        $approved_count = self::count_reviews('approved');
        $trashed_count = self::count_reviews('trashed');
        
        // Get reviews for current status
        $reviews = self::get_all_reviews($status !== 'all' ? $status : null, 50, 0);
        ?>
        <div class="wrap">
            <h1>CPD Reviews</h1>
            
            <ul class="subsubsub">
                <li><a href="<?php echo admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=pending'); ?>" class="<?php echo $status === 'pending' ? 'current' : ''; ?>">Pending <span class="count">(<?php echo $pending_count; ?>)</span></a> |</li>
                <li><a href="<?php echo admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=approved'); ?>" class="<?php echo $status === 'approved' ? 'current' : ''; ?>">Approved <span class="count">(<?php echo $approved_count; ?>)</span></a> |</li>
                <li><a href="<?php echo admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=trashed'); ?>" class="<?php echo $status === 'trashed' ? 'current' : ''; ?>">Trash <span class="count">(<?php echo $trashed_count; ?>)</span></a></li>
            </ul>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Reviewer</th>
                        <th>CPD Event</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reviews)) : ?>
                        <tr><td colspan="6">No reviews found.</td></tr>
                    <?php else : ?>
                        <?php foreach ($reviews as $review) : ?>
                            <tr>
                                <td><?php echo esc_html($review->reviewer_name); ?><br><small><?php echo esc_html($review->reviewer_email); ?></small></td>
                                <td><a href="<?php echo esc_url(get_edit_post_link($review->cpd_event_id)); ?>"><?php echo esc_html($review->cpd_title); ?></a></td>
                                <td><?php echo str_repeat('★', $review->star_rating); ?></td>
                                <td><?php echo esc_html(wp_trim_words($review->review_comment, 20)); ?></td>
                                <td><?php echo date_i18n('j M Y', strtotime($review->created_at)); ?></td>
                                <td>
                                    <?php if ($status !== 'approved') : ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=' . $status . '&action=approve&review_id=' . $review->id), 'cpd_review_action'); ?>" class="button button-small">Approve</a>
                                    <?php endif; ?>
                                    <?php if ($status !== 'trashed') : ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=' . $status . '&action=trash&review_id=' . $review->id), 'cpd_review_action'); ?>" class="button button-small">Trash</a>
                                    <?php endif; ?>
                                    <?php if ($status === 'trashed') : ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('edit.php?post_type=cpd_event&page=cpd-reviews&status=trashed&action=delete&review_id=' . $review->id), 'cpd_review_action'); ?>" class="button button-small button-link-delete" onclick="return confirm('Delete permanently?');">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Initialize
VET_CPD_Reviews::init();
