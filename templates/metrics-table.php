<?php
if (!defined('ABSPATH')) {
    exit;
}

$posts = get_posts([
    'meta_key' => '_xautoposter_shared',
    'meta_value' => '1',
    'orderby' => 'meta_value_num',
    'order' => 'DESC',
    'posts_per_page' => -1
]);

if (!empty($posts)): ?>
    <h3><?php _e('Tweet Performansı', 'xautoposter'); ?></h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Gönderi', 'xautoposter'); ?></th>
                <th><?php _e('Paylaşım Tarihi', 'xautoposter'); ?></th>
                <th><?php _e('Beğeni', 'xautoposter'); ?></th>
                <th><?php _e('Retweet', 'xautoposter'); ?></th>
                <th><?php _e('Yanıt', 'xautoposter'); ?></th>
                <th><?php _e('Toplam Etkileşim', 'xautoposter'); ?></th>
                <th><?php _e('Tweet', 'xautoposter'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): 
                $metrics = get_post_meta($post->ID, '_xautoposter_tweet_metrics', true);
                $tweet_id = get_post_meta($post->ID, '_xautoposter_tweet_id', true);
                $share_time = get_post_meta($post->ID, '_xautoposter_share_time', true);
                
                $like_count = isset($metrics->public_metrics->like_count) ? 
                    $metrics->public_metrics->like_count : 0;
                $retweet_count = isset($metrics->public_metrics->retweet_count) ? 
                    $metrics->public_metrics->retweet_count : 0;
                $reply_count = isset($metrics->public_metrics->reply_count) ? 
                    $metrics->public_metrics->reply_count : 0;
                $total_engagement = $like_count + $retweet_count + $reply_count;
            ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($post->ID); ?>">
                            <?php echo esc_html(get_the_title($post->ID)); ?>
                        </a>
                    </td>
                    <td>
                        <?php 
                        if ($share_time) {
                            echo wp_date(
                                get_option('date_format') . ' ' . get_option('time_format'),
                                strtotime($share_time)
                            );
                        }
                        ?>
                    </td>
                    <td><?php echo number_format($like_count); ?></td>
                    <td><?php echo number_format($retweet_count); ?></td>
                    <td><?php echo number_format($reply_count); ?></td>
                    <td>
                        <strong><?php echo number_format($total_engagement); ?></strong>
                    </td>
                    <td>
                        <?php if ($tweet_id): ?>
                            <a href="https://twitter.com/i/web/status/<?php echo esc_attr($tweet_id); ?>" 
                               target="_blank" class="button button-small">
                                <span class="dashicons dashicons-twitter"></span>
                                <?php _e('Görüntüle', 'xautoposter'); ?>
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="notice notice-warning">
        <p><?php _e('Henüz paylaşılmış gönderi bulunmuyor.', 'xautoposter'); ?></p>
    </div>
<?php endif; ?>