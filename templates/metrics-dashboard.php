<?php
if (!defined('ABSPATH')) {
    exit;
}

// Metrikleri hesapla
$total_shares = count(get_posts([
    'meta_key' => '_xautoposter_shared',
    'meta_value' => '1',
    'posts_per_page' => -1
]));

$total_engagement = 0;
$total_likes = 0;
$total_retweets = 0;
$total_replies = 0;

$posts = get_posts([
    'meta_key' => '_xautoposter_tweet_metrics',
    'posts_per_page' => -1
]);

foreach ($posts as $post) {
    $metrics = get_post_meta($post->ID, '_xautoposter_tweet_metrics', true);
    if ($metrics && isset($metrics->public_metrics)) {
        $total_likes += $metrics->public_metrics->like_count;
        $total_retweets += $metrics->public_metrics->retweet_count;
        $total_replies += $metrics->public_metrics->reply_count;
        $total_engagement += $metrics->public_metrics->like_count + 
                           $metrics->public_metrics->retweet_count + 
                           $metrics->public_metrics->reply_count;
    }
}

$avg_engagement = $total_shares > 0 ? round($total_engagement / $total_shares, 1) : 0;

// Son 30 günlük değişimi hesapla
$last_month_posts = get_posts([
    'meta_key' => '_xautoposter_shared',
    'meta_value' => '1',
    'date_query' => [
        [
            'after' => '30 days ago',
            'inclusive' => true,
        ],
    ],
    'posts_per_page' => -1
]);

$last_month_shares = count($last_month_posts);
$share_trend = $total_shares > 0 ? 
    round(($last_month_shares / $total_shares) * 100, 1) : 0;
?>

<div class="metrics-dashboard">
    <div class="metrics-summary">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-share"></span>
            </div>
            <div class="metric-label"><?php _e('Toplam Paylaşım', 'xautoposter'); ?></div>
            <div class="metric-value"><?php echo number_format($total_shares); ?></div>
            <?php if ($share_trend > 0): ?>
            <div class="metric-trend positive">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <?php printf(__('Son 30 günde %s%%', 'xautoposter'), $share_trend); ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="metric-label"><?php _e('Toplam Beğeni', 'xautoposter'); ?></div>
            <div class="metric-value"><?php echo number_format($total_likes); ?></div>
            <div class="metric-trend">
                <?php printf(__('Tweet başına %s', 'xautoposter'), 
                    number_format($total_shares > 0 ? $total_likes / $total_shares : 0, 1)
                ); ?>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-controls-repeat"></span>
            </div>
            <div class="metric-label"><?php _e('Toplam Retweet', 'xautoposter'); ?></div>
            <div class="metric-value"><?php echo number_format($total_retweets); ?></div>
            <div class="metric-trend">
                <?php printf(__('Tweet başına %s', 'xautoposter'), 
                    number_format($total_shares > 0 ? $total_retweets / $total_shares : 0, 1)
                ); ?>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="metric-label"><?php _e('Toplam Yanıt', 'xautoposter'); ?></div>
            <div class="metric-value"><?php echo number_format($total_replies); ?></div>
            <div class="metric-trend">
                <?php printf(__('Tweet başına %s', 'xautoposter'), 
                    number_format($total_shares > 0 ? $total_replies / $total_shares : 0, 1)
                ); ?>
            </div>
        </div>
    </div>

    <div class="metrics-chart">
        <div class="metrics-chart-header">
            <h3 class="metrics-chart-title"><?php _e('Etkileşim Trendi', 'xautoposter'); ?></h3>
            <div class="metrics-chart-period">
                <button class="active"><?php _e('7 Gün', 'xautoposter'); ?></button>
                <button><?php _e('30 Gün', 'xautoposter'); ?></button>
                <button><?php _e('3 Ay', 'xautoposter'); ?></button>
            </div>
        </div>
        <div id="engagement-chart"></div>
    </div>
</div>