<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';
$queue_stats = (new XAutoPoster\Models\Queue())->getQueueStats();

// Manuel paylaşım için gönderi listesini al
if ($current_tab === 'manual') {
    $posts = get_posts([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => [
            [
                'key' => '_xautoposter_shared',
                'compare' => 'NOT EXISTS'
            ]
        ]
    ]);
}
?>

<div class="wrap xautoposter-wrap">
    <h1><?php _e('XAutoPoster', 'xautoposter'); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=xautoposter&tab=settings" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Ayarlar', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=manual" 
           class="nav-tab <?php echo $current_tab === 'manual' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Manuel Paylaşım', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=metrics" 
           class="nav-tab <?php echo $current_tab === 'metrics' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Metrikler', 'xautoposter'); ?>
        </a>
        <a href="?page=xautoposter&tab=queue" 
           class="nav-tab <?php echo $current_tab === 'queue' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Paylaşım Kuyruğu', 'xautoposter'); ?>
            <?php if ($queue_stats->pending_count > 0): ?>
                <span class="queue-count"><?php echo $queue_stats->pending_count; ?></span>
            <?php endif; ?>
        </a>
    </h2>

    <?php if ($current_tab === 'settings'): ?>
        <div class="settings-container">
            <form method="post" action="options.php">
                <?php
                settings_fields('xautoposter_options_group');
                do_settings_sections('xautoposter-settings');
                ?>

                <div class="api-status-bar">
                    <?php 
                    $api_verified = get_option('xautoposter_api_verified', false);
                    $api_error = get_option('xautoposter_api_error', '');
                    
                    if ($api_verified): ?>
                        <div class="notice notice-success inline">
                            <p>
                                <?php _e('Twitter API bağlantısı başarılı.', 'xautoposter'); ?>
                                <button type="button" id="unlock-api-settings" class="button button-small">
                                    <?php _e('API Ayarlarını Düzenle', 'xautoposter'); ?>
                                    <span class="spinner"></span>
                                </button>
                            </p>
                        </div>
                    <?php elseif ($api_error): ?>
                        <div class="notice notice-error inline">
                            <p><?php echo esc_html($api_error); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <h3><?php _e('Otomatik Paylaşım Ayarları', 'xautoposter'); ?></h3>
                <?php
                settings_fields('xautoposter_auto_share_options_group');
                do_settings_sections('xautoposter-auto-share');
                
                submit_button();
                ?>
            </form>
        </div>
    <?php elseif ($current_tab === 'manual'): ?>
        <div class="manual-share-container">
            <div class="tablenav top">
                <div class="alignleft actions">
                    <button type="button" id="share-selected" class="button button-primary">
                        <?php _e('Seçili Gönderileri Paylaş', 'xautoposter'); ?>
                        <span class="count"></span>
                        <span class="spinner"></span>
                    </button>
                </div>
                <div class="tablenav-pages">
                    <!-- Sayfalama buraya eklenebilir -->
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped posts-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="select-all-posts">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e('Başlık', 'xautoposter'); ?>
                        </th>
                        <th scope="col" class="manage-column column-date">
                            <?php _e('Tarih', 'xautoposter'); ?>
                        </th>
                        <th scope="col" class="manage-column column-categories">
                            <?php _e('Kategoriler', 'xautoposter'); ?>
                        </th>
                        <th scope="col" class="manage-column column-status">
                            <?php _e('Durum', 'xautoposter'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($posts)): ?>
                        <?php foreach ($posts as $post): 
                            $is_shared = get_post_meta($post->ID, '_xautoposter_shared', true);
                            $share_time = get_post_meta($post->ID, '_xautoposter_share_time', true);
                        ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="posts[]" value="<?php echo $post->ID; ?>"
                                           <?php echo $is_shared ? 'disabled' : ''; ?>>
                                </th>
                                <td class="title column-title">
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                            <?php echo get_the_title($post->ID); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td class="date column-date">
                                    <?php echo get_the_date('', $post->ID); ?>
                                </td>
                                <td class="categories column-categories">
                                    <?php echo get_the_category_list(', ', '', $post->ID); ?>
                                </td>
                                <td class="status column-status">
                                    <?php if ($is_shared): ?>
                                        <span class="status-shared">
                                            <?php 
                                            printf(
                                                __('Paylaşıldı (%s)', 'xautoposter'),
                                                human_time_diff(strtotime($share_time), current_time('timestamp')) . ' önce'
                                            );
                                            ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-not-shared">
                                            <?php _e('Paylaşılmadı', 'xautoposter'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">
                                <?php _e('Paylaşılacak gönderi bulunamadı.', 'xautoposter'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php elseif ($current_tab === 'metrics'): ?>
        <?php include(XAUTOPOSTER_PATH . 'templates/metrics-dashboard.php'); ?>
        <?php include(XAUTOPOSTER_PATH . 'templates/metrics-table.php'); ?>
    <?php elseif ($current_tab === 'queue'): ?>
        <div class="queue-container">
            <div class="queue-stats">
                <div class="stat-box">
                    <h4><?php _e('Bekleyen', 'xautoposter'); ?></h4>
                    <span class="stat-value"><?php echo $queue_stats->pending_count; ?></span>
                </div>
                <div class="stat-box">
                    <h4><?php _e('İşleniyor', 'xautoposter'); ?></h4>
                    <span class="stat-value"><?php echo $queue_stats->processing_count; ?></span>
                </div>
                <div class="stat-box">
                    <h4><?php _e('Tamamlanan', 'xautoposter'); ?></h4>
                    <span class="stat-value"><?php echo $queue_stats->completed_count; ?></span>
                </div>
                <div class="stat-box">
                    <h4><?php _e('Başarısız', 'xautoposter'); ?></h4>
                    <span class="stat-value"><?php echo $queue_stats->failed_count; ?></span>
                </div>
            </div>
            <?php include(XAUTOPOSTER_PATH . 'templates/queue-table.php'); ?>
        </div>
    <?php endif; ?>
</div>

<style>
.xautoposter-wrap {
    max-width: 1200px;
    margin: 20px auto;
}

.xautoposter-wrap .nav-tab-wrapper {
    margin-bottom: 20px;
}

.xautoposter-wrap .nav-tab {
    position: relative;
}

.xautoposter-wrap .queue-count {
    display: inline-block;
    background: #ca4a1f;
    color: #fff;
    border-radius: 10px;
    padding: 0 6px;
    font-size: 11px;
    line-height: 16px;
    position: absolute;
    top: -8px;
    right: -8px;
}

.xautoposter-wrap .form-table {
    background: #fff;
    padding: 20px;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.xautoposter-wrap .form-table th {
    padding: 20px;
}

.xautoposter-wrap .form-table td {
    padding: 15px 20px;
}

.xautoposter-wrap .button-primary {
    margin-top: 20px;
}

.xautoposter-wrap .notice {
    margin: 20px 0;
}

.xautoposter-wrap .api-status-bar {
    margin: 20px 0;
}

.xautoposter-wrap #unlock-api-settings {
    margin-left: 10px;
}

.xautoposter-wrap #unlock-api-settings .spinner {
    float: none;
    margin: 0 0 0 5px;
}

.manual-share-container {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.manual-share-container .tablenav {
    margin: 15px 0;
}

.manual-share-container .spinner {
    float: none;
    margin: 0 0 0 5px;
}

.manual-share-container .status-shared {
    color: #46b450;
}

.manual-share-container .status-not-shared {
    color: #dc3232;
}

.queue-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    text-align: center;
}

.stat-box h4 {
    margin: 0 0 10px;
    color: #646970;
}

.stat-box .stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.settings-container, .queue-container {
    background: #fff;
    padding: 30px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

@media screen and (max-width: 782px) {
    .xautoposter-wrap .form-table th {
        padding: 15px;
    }
    
    .xautoposter-wrap .form-table td {
        padding: 10px 15px;
    }
    
    .queue-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>