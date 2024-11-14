<?php
namespace XAutoPoster;

class Plugin {
    private static $instance = null;
    private $settings;
    private $twitter;
    private $queue;
    private $metrics;
    private $rateLimiter;
    private $logger;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->initHooks();
    }

    private function initHooks() {
        // Admin hooks
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'initAdmin']);
        
        // AJAX handlers
        add_action('wp_ajax_xautoposter_share_posts', [$this, 'handleManualShare']);
        add_action('wp_ajax_xautoposter_reset_api_verification', [$this, 'resetApiVerification']);
        
        // Post hooks
        add_action('publish_post', [$this, 'handlePostPublish'], 10, 2);
        
        // Cron hooks
        add_action('xautoposter_cron_hook', [$this, 'processQueue']);
        add_action('xautoposter_update_metrics', [$this, 'updateMetrics']);
        
        // Plugin hooks
        register_activation_hook(XAUTOPOSTER_FILE, [$this, 'activate']);
        register_deactivation_hook(XAUTOPOSTER_FILE, [$this, 'deactivate']);
    }

    public function initAdmin() {
        $this->settings = new Admin\Settings();
        $this->settings->init();
    }

    public function init() {
        try {
            $this->loadTextdomain();
            $this->initComponents();
        } catch (\Exception $e) {
            add_action('admin_notices', function() use ($e) {
                echo '<div class="error"><p>' . 
                     esc_html__('XAutoPoster Error: ', 'xautoposter') . 
                     esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    private function loadTextdomain() {
        load_plugin_textdomain(
            'xautoposter',
            false,
            dirname(XAUTOPOSTER_BASENAME) . '/languages'
        );
    }

    private function initComponents() {
        $cache = new Services\CacheService();
        $this->rateLimiter = new Services\RateLimiter($cache);
        $this->logger = new Services\Logger();
        
        $options = get_option('xautoposter_options', []);
        
        if (!empty($options['api_key']) && !empty($options['api_secret']) && 
            !empty($options['access_token']) && !empty($options['access_token_secret'])) {
            try {
                $this->twitter = new Services\ApiService();
                $this->metrics = new Services\MetricsService($this->twitter);
            } catch (\Exception $e) {
                $this->logger->error('XAutoPoster Service Init Error: ' . $e->getMessage());
            }
        }
        
        $this->queue = new Models\Queue();
    }

    public function addAdminMenu() {
        add_menu_page(
            __('XAutoPoster', 'xautoposter'),
            __('XAutoPoster', 'xautoposter'),
            'manage_options',
            'xautoposter',
            [$this, 'renderAdminPage'],
            'dashicons-twitter',
            30
        );

        add_submenu_page(
            'xautoposter',
            __('Ayarlar', 'xautoposter'),
            __('Ayarlar', 'xautoposter'),
            'manage_options',
            'xautoposter-settings',
            [$this, 'renderSettingsPage']
        );

        add_submenu_page(
            'xautoposter',
            __('Metrikler', 'xautoposter'),
            __('Metrikler', 'xautoposter'),
            'manage_options',
            'xautoposter-metrics',
            [$this, 'renderMetricsPage']
        );
    }

    public function renderAdminPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz bulunmuyor.', 'xautoposter'));
        }
        
        require_once XAUTOPOSTER_PATH . 'templates/admin-page.php';
    }

    public function renderSettingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz bulunmuyor.', 'xautoposter'));
        }
        
        require_once XAUTOPOSTER_PATH . 'templates/settings-page.php';
    }

    public function renderMetricsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Bu sayfaya erişim yetkiniz bulunmuyor.', 'xautoposter'));
        }
        
        require_once XAUTOPOSTER_PATH . 'templates/metrics-page.php';
    }

    public function handleManualShare() {
        check_ajax_referer('xautoposter_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Bu işlem için yetkiniz bulunmuyor.', 'xautoposter')]);
            return;
        }
        
        if (!$this->twitter) {
            wp_send_json_error(['message' => __('Twitter API bağlantısı kurulamadı.', 'xautoposter')]);
            return;
        }
        
        $postIds = isset($_POST['posts']) ? array_map('intval', $_POST['posts']) : [];
        
        if (empty($postIds)) {
            wp_send_json_error(['message' => __('Lütfen paylaşılacak gönderileri seçin.', 'xautoposter')]);
            return;
        }
        
        $results = [];
        $success = 0;
        $failed = 0;
        
        foreach ($postIds as $postId) {
            if (!$this->rateLimiter->check('share_post')) {
                $results[] = [
                    'id' => $postId,
                    'status' => 'error',
                    'message' => __('Rate limit aşıldı. Lütfen biraz bekleyin.', 'xautoposter')
                ];
                $failed++;
                continue;
            }

            try {
                $post = get_post($postId);
                if (!$post) {
                    throw new \Exception(__('Gönderi bulunamadı.', 'xautoposter'));
                }
                
                $result = $this->twitter->sharePost($post);
                
                if ($result && isset($result->data->id)) {
                    update_post_meta($postId, '_xautoposter_shared', '1');
                    update_post_meta($postId, '_xautoposter_share_time', current_time('mysql'));
                    update_post_meta($postId, '_xautoposter_tweet_id', $result->data->id);
                    
                    if ($this->metrics) {
                        $this->metrics->updateMetrics($postId);
                    }
                    
                    $success++;
                    $results[] = [
                        'id' => $postId,
                        'status' => 'success',
                        'message' => sprintf(__('Gönderi #%d başarıyla paylaşıldı', 'xautoposter'), $postId)
                    ];
                } else {
                    throw new \Exception(__('Tweet paylaşılamadı.', 'xautoposter'));
                }
            } catch (\Exception $e) {
                $this->logger->error('Share Error: ' . $e->getMessage(), ['post_id' => $postId]);
                $failed++;
                $results[] = [
                    'id' => $postId,
                    'status' => 'error',
                    'message' => $e->getMessage()
                ];
            }
        }
        
        if ($success > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    __('%d gönderi başarıyla paylaşıldı, %d başarısız', 'xautoposter'),
                    $success,
                    $failed
                ),
                'results' => $results
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Gönderiler paylaşılamadı', 'xautoposter'),
                'results' => $results
            ]);
        }
    }

    public function handlePostPublish($postId, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($postId)) return;
        if (get_post_meta($postId, '_xautoposter_shared', true)) return;

        $options = get_option('xautoposter_auto_share_options', []);
        $selectedCategories = isset($options['categories']) ? (array)$options['categories'] : [];
        
        if (!empty($selectedCategories)) {
            $postCategories = wp_get_post_categories($postId);
            $hasSelectedCategory = array_intersect($selectedCategories, $postCategories);
            
            if (empty($hasSelectedCategory)) {
                return;
            }
        }

        $this->queue->addToQueue($postId);
        
        if (!empty($options['auto_share']) && $options['auto_share'] === '1') {
            try {
                $this->sharePost($postId);
            } catch (\Exception $e) {
                $this->logger->error('Auto Share Error: ' . $e->getMessage(), ['post_id' => $postId]);
            }
        }
    }

    private function sharePost($postId) {
        if (!$this->rateLimiter->check('share_post')) {
            throw new \Exception(__('Rate limit aşıldı. Lütfen biraz bekleyin.', 'xautoposter'));
        }
        
        if (!$this->twitter) {
            throw new \Exception(__('Twitter API bağlantısı kurulamadı.', 'xautoposter'));
        }
        
        $post = get_post($postId);
        if (!$post) {
            throw new \Exception(__('Gönderi bulunamadı.', 'xautoposter'));
        }
        
        try {
            $result = $this->twitter->sharePost($post);
            
            if ($result && isset($result->data->id)) {
                $this->logger->info('Post shared successfully', [
                    'post_id' => $postId,
                    'tweet_id' => $result->data->id
                ]);
                
                update_post_meta($postId, '_xautoposter_shared', '1');
                update_post_meta($postId, '_xautoposter_share_time', current_time('mysql'));
                update_post_meta($postId, '_xautoposter_tweet_id', $result->data->id);
                
                if ($this->metrics) {
                    $this->metrics->updateMetrics($postId);
                }
                
                $this->queue->markAsShared($postId);
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Share Error: ' . $e->getMessage(), ['post_id' => $postId]);
            throw $e;
        }
    }

    public function processQueue() {
        if (!$this->twitter) {
            return;
        }
        
        $pendingPosts = $this->queue->getPendingPosts();
        
        foreach ($pendingPosts as $post) {
            if (!$this->rateLimiter->check('share_post')) {
                $this->logger->warning('Rate limit reached, skipping queue processing');
                continue;
            }

            try {
                $this->sharePost($post->post_id);
            } catch (\Exception $e) {
                $this->logger->error('Queue Error: ' . $e->getMessage(), [
                    'post_id' => $post->post_id
                ]);
                $this->queue->incrementAttempts($post->post_id);
            }
        }
    }

    public function updateMetrics() {
        if ($this->metrics) {
            $this->metrics->updateAllMetrics();
        }
    }

    public function resetApiVerification() {
        check_ajax_referer('xautoposter_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Bu işlem için yetkiniz bulunmuyor.', 'xautoposter')]);
            return;
        }
        
        delete_option('xautoposter_api_verified');
        delete_option('xautoposter_api_error');
        
        $this->logger->info('API verification reset by user');
        
        wp_send_json_success([
            'message' => __('API doğrulaması sıfırlandı. Ayarları güncelleyebilirsiniz.', 'xautoposter')
        ]);
    }

    public function activate() {
        if (!class_exists('XAutoPoster\\Models\\Queue')) {
            require_once XAUTOPOSTER_PATH . 'src/Models/Queue.php';
        }
        
        $queue = new Models\Queue();
        $queue->createTable();
        
        $options = get_option('xautoposter_auto_share_options', []);
        $interval = isset($options['interval']) ? $options['interval'] : '30min';
        
        if (!wp_next_scheduled('xautoposter_cron_hook')) {
            wp_schedule_event(time(), $interval, 'xautoposter_cron_hook');
        }
        
        if (!wp_next_scheduled('xautoposter_update_metrics')) {
            wp_schedule_event(time(), 'hourly', 'xautoposter_update_metrics');
        }
        
        $this->logger->info('Plugin activated');
    }

    public function deactivate() {
        wp_clear_scheduled_hook('xautoposter_cron_hook');
        wp_clear_scheduled_hook('xautoposter_update_metrics');
        $this->logger->info('Plugin deactivated');
    }
}