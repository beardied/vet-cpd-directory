<?php
/**
 * Admin functionality
 */

class VET_CPD_Admin {
    
    public static function init() {
        // Add admin menu
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        
        // Register settings
        add_action('admin_init', [__CLASS__, 'register_settings']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
        
        // Add plugin action links
        add_filter('plugin_action_links_' . VET_CPD_PLUGIN_BASENAME, [__CLASS__, 'action_links']);
        
        // Custom admin columns
        add_filter('manage_cpd_event_posts_columns', [__CLASS__, 'cpd_columns']);
        add_action('manage_cpd_event_posts_custom_column', [__CLASS__, 'cpd_column_content'], 10, 2);
        add_filter('manage_edit-cpd_event_sortable_columns', [__CLASS__, 'cpd_sortable_columns']);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        // Only load on CPD settings page
        if ($hook !== 'cpd_event_page_cpd_settings') {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }
    
    /**
     * Admin menu
     */
    public static function admin_menu() {
        // Main menu is handled by CPD post type, add submenus
        add_submenu_page(
            'edit.php?post_type=cpd_event',
            __('Settings', 'vet-cpd-directory'),
            __('Settings', 'vet-cpd-directory'),
            'manage_options',
            'cpd_settings',
            [__CLASS__, 'settings_page']
        );
    }
    
    /**
     * Register settings
     */
    public static function register_settings() {
        // Social Media Settings
        register_setting('cpd_social_settings', 'cpd_social_facebook', 'esc_url_raw');
        register_setting('cpd_social_settings', 'cpd_social_instagram', 'esc_url_raw');
        register_setting('cpd_social_settings', 'cpd_social_twitter', 'esc_url_raw');
        register_setting('cpd_social_settings', 'cpd_social_youtube', 'esc_url_raw');
        register_setting('cpd_social_settings', 'cpd_social_tiktok', 'esc_url_raw');
        register_setting('cpd_social_settings', 'cpd_social_linkedin', 'esc_url_raw');
        
        // Hero Settings
        register_setting('cpd_hero_settings', 'cpd_hero_image', 'intval');
        register_setting('cpd_hero_settings', 'cpd_hero_overlay_opacity', 'intval');
        register_setting('cpd_hero_settings', 'cpd_hero_overlay_color', 'sanitize_hex_color');
        
        // Contact Settings
        register_setting('cpd_contact_settings', 'cpd_contact_email', 'sanitize_email');
        register_setting('cpd_contact_settings', 'cpd_footer_email', 'sanitize_email');
        register_setting('cpd_contact_settings', 'cpd_review_notification_email', 'sanitize_email');
        
        // Header Settings
        register_setting('cpd_header_settings', 'cpd_header_title_size', 'intval');
        register_setting('cpd_header_settings', 'cpd_header_title_color', 'sanitize_hex_color');
        register_setting('cpd_header_settings', 'cpd_header_tagline_size', 'intval');
        register_setting('cpd_header_settings', 'cpd_header_tagline_color', 'sanitize_hex_color');
    }
    
    /**
     * Settings page
     */
    public static function settings_page() {
        // Handle hero image upload
        $hero_image_url = '';
        $hero_image_id = get_option('cpd_hero_image', 0);
        if ($hero_image_id) {
            $hero_image_url = wp_get_attachment_url($hero_image_id);
        }
        ?>
        <div class="wrap">
            <h1><?php _e('CPD Directory Settings', 'vet-cpd-directory'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active" data-tab="general"><?php _e('General', 'vet-cpd-directory'); ?></a>
                <a href="#social" class="nav-tab" data-tab="social"><?php _e('Social Media', 'vet-cpd-directory'); ?></a>
                <a href="#hero" class="nav-tab" data-tab="hero"><?php _e('Hero Section', 'vet-cpd-directory'); ?></a>
                <a href="#header" class="nav-tab" data-tab="header"><?php _e('Header', 'vet-cpd-directory'); ?></a>
                <a href="#contact" class="nav-tab" data-tab="contact"><?php _e('Contact', 'vet-cpd-directory'); ?></a>
            </h2>
            
            <!-- General Tab -->
            <div id="tab-general" class="cpd-tab-content active">
                <p><?php _e('Use the menu items to manage CPD Events, Venues, Organisers, Instructors, and Series.', 'vet-cpd-directory'); ?></p>
                
                <h2><?php _e('Permalink Settings', 'vet-cpd-directory'); ?></h2>
                <p><?php _e('Archive pages are automatically available at:', 'vet-cpd-directory'); ?></p>
                <ul style="list-style:disc;margin-left:20px;">
                    <li><code>/cpd/</code> - <?php _e('All CPD Events', 'vet-cpd-directory'); ?></li>
                    <li><code>/cpd-category/{category-name}/</code> - <?php _e('Events by category', 'vet-cpd-directory'); ?></li>
                    <li><code>/cpd-type/{tag-name}/</code> - <?php _e('Events by tag (upcoming, on-demand, online, free)', 'vet-cpd-directory'); ?></li>
                    <li><code>/cpd-venue/{venue-name}/</code> - <?php _e('Events by venue', 'vet-cpd-directory'); ?></li>
                </ul>
                <p><?php _e('Make sure to visit Settings > Permalinks and click "Save Changes" after activating this plugin.', 'vet-cpd-directory'); ?></p>
                
                <h2><?php _e('Shortcodes', 'vet-cpd-directory'); ?></h2>
                <p><?php _e('Use these shortcodes to display CPD events anywhere on your site:', 'vet-cpd-directory'); ?></p>
                
                <table class="widefat" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th style="width: 30%;"><?php _e('Shortcode', 'vet-cpd-directory'); ?></th>
                            <th style="width: 40%;"><?php _e('Description', 'vet-cpd-directory'); ?></th>
                            <th style="width: 30%;"><?php _e('Parameters', 'vet-cpd-directory'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[cpd_venue_events]</code></td>
                            <td><?php _e('Display events at a specific venue. Perfect for venue pages.', 'vet-cpd-directory'); ?></td>
                            <td>
                                <code>venue_id</code> - <?php _e('ID of the venue', 'vet-cpd-directory'); ?><br>
                                <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>[cpd_instructor_events]</code></td>
                            <td><?php _e('Display events by a specific instructor. Perfect for instructor pages.', 'vet-cpd-directory'); ?></td>
                            <td>
                                <code>instructor_id</code> - <?php _e('ID of the instructor', 'vet-cpd-directory'); ?><br>
                                <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                            </td>
                        </tr>
                        <tr>
                            <td><code>[cpd_organiser_events]</code></td>
                            <td><?php _e('Display events by a specific organiser. Perfect for organiser pages.', 'vet-cpd-directory'); ?></td>
                            <td>
                                <code>organiser_id</code> - <?php _e('ID of the organiser', 'vet-cpd-directory'); ?><br>
                                <code>limit</code> - <?php _e('Number of events (default: 5)', 'vet-cpd-directory'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php _e('Shortcode Examples', 'vet-cpd-directory'); ?></h3>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-width: 900px;">
&lt;!-- Show events at venue ID 123 --&gt;
[cpd_venue_events venue_id="123" limit="10"]

&lt;!-- Show events by instructor ID 456 --&gt;
[cpd_instructor_events instructor_id="456" limit="3"]

&lt;!-- Show events by organiser ID 789 --&gt;
[cpd_organiser_events organiser_id="789" limit="5"]</pre>
            </div>
            
            <!-- Social Media Tab -->
            <div id="tab-social" class="cpd-tab-content" style="display:none;">
                <form method="post" action="options.php">
                    <?php settings_fields('cpd_social_settings'); ?>
                    <h2><?php _e('Social Media Links', 'vet-cpd-directory'); ?></h2>
                    <p><?php _e('Enter your social media profile URLs. Only filled fields will be displayed in the footer.', 'vet-cpd-directory'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cpd_social_facebook"><?php _e('Facebook', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_facebook" name="cpd_social_facebook" value="<?php echo esc_url(get_option('cpd_social_facebook', '')); ?>" class="regular-text" placeholder="https://facebook.com/yourpage">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_social_instagram"><?php _e('Instagram', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_instagram" name="cpd_social_instagram" value="<?php echo esc_url(get_option('cpd_social_instagram', '')); ?>" class="regular-text" placeholder="https://instagram.com/yourusername">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_social_twitter"><?php _e('Twitter / X', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_twitter" name="cpd_social_twitter" value="<?php echo esc_url(get_option('cpd_social_twitter', '')); ?>" class="regular-text" placeholder="https://twitter.com/yourusername">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_social_youtube"><?php _e('YouTube', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_youtube" name="cpd_social_youtube" value="<?php echo esc_url(get_option('cpd_social_youtube', '')); ?>" class="regular-text" placeholder="https://youtube.com/yourchannel">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_social_tiktok"><?php _e('TikTok', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_tiktok" name="cpd_social_tiktok" value="<?php echo esc_url(get_option('cpd_social_tiktok', '')); ?>" class="regular-text" placeholder="https://tiktok.com/@yourusername">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_social_linkedin"><?php _e('LinkedIn', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="url" id="cpd_social_linkedin" name="cpd_social_linkedin" value="<?php echo esc_url(get_option('cpd_social_linkedin', '')); ?>" class="regular-text" placeholder="https://linkedin.com/in/yourusername">
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <!-- Hero Tab -->
            <div id="tab-hero" class="cpd-tab-content" style="display:none;">
                <form method="post" action="options.php">
                    <?php settings_fields('cpd_hero_settings'); ?>
                    <h2><?php _e('Homepage Hero Section', 'vet-cpd-directory'); ?></h2>
                    <p><?php _e('Customize the hero section on your homepage.', 'vet-cpd-directory'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cpd_hero_image"><?php _e('Hero Background Image', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="hidden" id="cpd_hero_image" name="cpd_hero_image" value="<?php echo esc_attr($hero_image_id); ?>">
                                <div id="cpd_hero_image_preview" style="margin-bottom: 10px;">
                                    <?php if ($hero_image_url) : ?>
                                        <img src="<?php echo esc_url($hero_image_url); ?>" style="max-width: 400px; max-height: 200px; object-fit: cover;">
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button" id="cpd_upload_hero_image"><?php _e('Select Image', 'vet-cpd-directory'); ?></button>
                                <button type="button" class="button" id="cpd_remove_hero_image" <?php echo $hero_image_id ? '' : 'style="display:none;"'; ?>><?php _e('Remove Image', 'vet-cpd-directory'); ?></button>
                                <p class="description"><?php _e('Recommended size: 1920x800 pixels', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_hero_overlay_opacity"><?php _e('Overlay Transparency', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="range" id="cpd_hero_overlay_opacity" name="cpd_hero_overlay_opacity" min="0" max="100" value="<?php echo esc_attr(get_option('cpd_hero_overlay_opacity', '85')); ?>" style="width: 300px; vertical-align: middle;">
                                <span id="cpd_overlay_opacity_value" style="margin-left: 10px; font-weight: bold;"><?php echo esc_attr(get_option('cpd_hero_overlay_opacity', '85')); ?>%</span>
                                <p class="description"><?php _e('Adjust the transparency of the color overlay (0 = fully transparent, 100 = fully opaque)', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_hero_overlay_color"><?php _e('Overlay Color', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="color" id="cpd_hero_overlay_color" name="cpd_hero_overlay_color" value="<?php echo esc_attr(get_option('cpd_hero_overlay_color', '#0d8f4f')); ?>" style="width: 100px; height: 40px;">
                                <p class="description"><?php _e('Choose the overlay color. Default is the theme green.', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <!-- Header Tab -->
            <div id="tab-header" class="cpd-tab-content" style="display:none;">
                <form method="post" action="options.php">
                    <?php settings_fields('cpd_header_settings'); ?>
                    <h2><?php _e('Header Styling', 'vet-cpd-directory'); ?></h2>
                    <p><?php _e('Customize the header logo title and tagline appearance.', 'vet-cpd-directory'); ?></p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cpd_header_title_size"><?php _e('Title Font Size', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="number" id="cpd_header_title_size" name="cpd_header_title_size" value="<?php echo esc_attr(get_option('cpd_header_title_size', '30')); ?>" min="12" max="72" class="small-text"> px
                                <p class="description"><?php _e('Default: 30px', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_header_title_color"><?php _e('Title Color', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="color" id="cpd_header_title_color" name="cpd_header_title_color" value="<?php echo esc_attr(get_option('cpd_header_title_color', '#ffffff')); ?>" style="width: 100px; height: 40px;">
                                <p class="description"><?php _e('Default: white (#ffffff)', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_header_tagline_size"><?php _e('Tagline Font Size', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="number" id="cpd_header_tagline_size" name="cpd_header_tagline_size" value="<?php echo esc_attr(get_option('cpd_header_tagline_size', '12')); ?>" min="8" max="24" class="small-text"> px
                                <p class="description"><?php _e('Default: 12px', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_header_tagline_color"><?php _e('Tagline Color', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="color" id="cpd_header_tagline_color" name="cpd_header_tagline_color" value="<?php echo esc_attr(get_option('cpd_header_tagline_color', 'rgba(255,255,255,0.9)')); ?>" style="width: 100px; height: 40px;">
                                <p class="description"><?php _e('Default: white with opacity (rgba(255,255,255,0.9))', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <!-- Contact Tab -->
            <div id="tab-contact" class="cpd-tab-content" style="display:none;">
                <form method="post" action="options.php">
                    <?php settings_fields('cpd_contact_settings'); ?>
                    <h2><?php _e('Contact Settings', 'vet-cpd-directory'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="cpd_contact_email"><?php _e('Contact Form Recipient', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="email" id="cpd_contact_email" name="cpd_contact_email" value="<?php echo esc_attr(get_option('cpd_contact_email', get_option('admin_email'))); ?>" class="regular-text">
                                <p class="description"><?php _e('Email address where contact form submissions will be sent.', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_footer_email"><?php _e('Footer Display Email', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="email" id="cpd_footer_email" name="cpd_footer_email" value="<?php echo esc_attr(get_option('cpd_footer_email', '')); ?>" class="regular-text">
                                <p class="description"><?php _e('Email address displayed in the footer. Leave blank to hide.', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="cpd_review_notification_email"><?php _e('Review Notification Email', 'vet-cpd-directory'); ?></label></th>
                            <td>
                                <input type="email" id="cpd_review_notification_email" name="cpd_review_notification_email" value="<?php echo esc_attr(get_option('cpd_review_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                                <p class="description"><?php _e('Email address where new review notifications will be sent. Defaults to admin email.', 'vet-cpd-directory'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                // Tab switching
                $('.nav-tab').on('click', function(e) {
                    e.preventDefault();
                    var tab = $(this).data('tab');
                    
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    
                    $('.cpd-tab-content').hide();
                    $('#tab-' + tab).show();
                });
                
                // Hero image upload
                var mediaUploader;
                $('#cpd_upload_hero_image').on('click', function(e) {
                    e.preventDefault();
                    
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: '<?php _e('Select Hero Image', 'vet-cpd-directory'); ?>',
                        button: {
                            text: '<?php _e('Use this image', 'vet-cpd-directory'); ?>'
                        },
                        multiple: false
                    });
                    
                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#cpd_hero_image').val(attachment.id);
                        $('#cpd_hero_image_preview').html('<img src="' + attachment.url + '" style="max-width: 400px; max-height: 200px; object-fit: cover;">');
                        $('#cpd_remove_hero_image').show();
                    });
                    
                    mediaUploader.open();
                });
                
                $('#cpd_remove_hero_image').on('click', function(e) {
                    e.preventDefault();
                    $('#cpd_hero_image').val('');
                    $('#cpd_hero_image_preview').html('');
                    $(this).hide();
                });
                
                // Opacity slider
                $('#cpd_hero_overlay_opacity').on('input', function() {
                    $('#cpd_overlay_opacity_value').text($(this).val() + '%');
                });
            });
            </script>
        </div>
        <?php
    }
    
    /**
     * Plugin action links
     */
    public static function action_links($links) {
        $settings_link = '<a href="' . admin_url('edit.php?post_type=cpd_event&page=cpd_settings') . '">' . __('Settings', 'vet-cpd-directory') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Custom columns for CPD list
     */
    public static function cpd_columns($columns) {
        // Remove default taxonomy columns to avoid duplication
        if (isset($columns['taxonomy-cpd_tag'])) {
            unset($columns['taxonomy-cpd_tag']);
        }
        if (isset($columns['taxonomy-cpd_category'])) {
            unset($columns['taxonomy-cpd_category']);
        }
        
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['cpd_start_date'] = __('Start Date', 'vet-cpd-directory');
                $new_columns['cpd_start_time'] = __('Start Time', 'vet-cpd-directory');
                $new_columns['cpd_end_date'] = __('End Date', 'vet-cpd-directory');
                $new_columns['cpd_end_time'] = __('End Time', 'vet-cpd-directory');
                $new_columns['cpd_cost'] = __('Cost', 'vet-cpd-directory');
                $new_columns['cpd_venues'] = __('Venues', 'vet-cpd-directory');
                $new_columns['cpd_instructors'] = __('Instructors', 'vet-cpd-directory');
                $new_columns['cpd_organisers'] = __('Organisers', 'vet-cpd-directory');
                $new_columns['cpd_categories'] = __('Categories', 'vet-cpd-directory');
                $new_columns['cpd_tags'] = __('Tags', 'vet-cpd-directory');
            }
        }
        return $new_columns;
    }
    
    /**
     * Column content
     */
    public static function cpd_column_content($column, $post_id) {
        switch ($column) {
            case 'cpd_start_date':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_start_time':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_start_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_end_date':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_end_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('date_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_end_time':
                $date = VET_CPD_CPD::get_meta($post_id, '_cpd_end_date');
                if ($date) {
                    echo esc_html(date_i18n(get_option('time_format'), strtotime($date)));
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_cost':
                $cost = VET_CPD_CPD::get_meta($post_id, '_cpd_cost');
                $currency = VET_CPD_CPD::get_meta($post_id, '_cpd_currency') ?: 'GBP';
                if ($cost && $cost !== '0') {
                    $symbol = $currency === 'GBP' ? '£' : ($currency === 'EUR' ? '€' : '$');
                    echo esc_html($symbol . $cost);
                } else {
                    echo '<em>' . __('Free', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_venues':
                $venue_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_venues');
                if (is_array($venue_ids) && !empty($venue_ids)) {
                    $links = [];
                    foreach ($venue_ids as $venue_id) {
                        $venue = get_post($venue_id);
                        if ($venue) {
                            $links[] = '<a href="' . get_edit_post_link($venue_id) . '">' . esc_html($venue->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_instructors':
                $instructor_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_instructors');
                if (is_array($instructor_ids) && !empty($instructor_ids)) {
                    $links = [];
                    foreach ($instructor_ids as $instructor_id) {
                        $instructor = get_post($instructor_id);
                        if ($instructor) {
                            $links[] = '<a href="' . get_edit_post_link($instructor_id) . '">' . esc_html($instructor->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_organisers':
                $organiser_ids = VET_CPD_CPD::get_meta($post_id, '_cpd_organisers');
                if (is_array($organiser_ids) && !empty($organiser_ids)) {
                    $links = [];
                    foreach ($organiser_ids as $organiser_id) {
                        $organiser = get_post($organiser_id);
                        if ($organiser) {
                            $links[] = '<a href="' . get_edit_post_link($organiser_id) . '">' . esc_html($organiser->post_title) . '</a>';
                        }
                    }
                    echo implode(', ', $links);
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_categories':
                $cats = get_the_term_list($post_id, 'cpd_category', '', ', ', '');
                if ($cats) {
                    echo $cats;
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
                
            case 'cpd_tags':
                $tags = get_the_term_list($post_id, 'cpd_tag', '', ', ', '');
                if ($tags) {
                    echo $tags;
                } else {
                    echo '<em>' . __('—', 'vet-cpd-directory') . '</em>';
                }
                break;
        }
    }
    
    /**
     * Sortable columns
     */
    public static function cpd_sortable_columns($columns) {
        $columns['cpd_start_date'] = '_cpd_start_date';
        $columns['cpd_end_date'] = '_cpd_end_date';
        $columns['cpd_cost'] = '_cpd_cost';
        return $columns;
    }
    
}
