<?php
if (!defined('ABSPATH')) {
    exit;
}

$queue = new XAutoPoster\Models\Queue();
$items = $queue->getPendingPosts(20); // Son 20 öğeyi göster
?>

<div class="queue-table-container">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-title column-primary">
                    <?php _e('Gönderi', 'xautoposter'); ?>
                </th>
                <th scope="col" class="manage-column column-scheduled">
                    <?php _e('Planlanma Zamanı', 'xautoposter'); ?>
                </th>
                <th scope="col" class="manage-column column-status">
                    <?php _e('Durum', 'xautoposter'); ?>
                </th>
                <th scope="col" class="manage-column column-attempts">
                    <?php _e('Deneme', 'xautoposter'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): 
                    $post = get_post($item->post_id);
                    if (!$post) continue;
                ?>
                    <tr>
                        <td class="title column-title">
                            <strong>
                                <a href="<?php echo get_edit_post_link($item->post_id); ?>">
                                    <?php echo get_the_title($item->post_id); ?>
                                </a>
                            </strong>
                        </td>
                        <td class="scheduled column-scheduled">
                            <?php 
                            if ($item->scheduled_time) {
                                $scheduled = strtotime($item->scheduled_time);
                                $now = current_time('timestamp');
                                
                                if ($scheduled > $now) {
                                    printf(
                                        __('%s sonra', 'xautoposter'),
                                        human_time_diff($now, $scheduled)
                                    );
                                } else {
                                    _e('Şimdi', 'xautoposter');
                                }
                            }
                            ?>
                        </td>
                        <td class="status column-status">
                            <?php
                            $status_labels = [
                                'pending' => __('Bekliyor', 'xautoposter'),
                                'processing' => __('İşleniyor', 'xautoposter'),
                                'completed' => __('Tamamlandı', 'xautoposter'),
                                'failed' => __('Başarısız', 'xautoposter')
                            ];
                            
                            $status_class = [
                                'pending' => 'status-pending',
                                'processing' => 'status-processing',
                                'completed' => 'status-completed',
                                'failed' => 'status-failed'
                            ];
                            ?>
                            <span class="<?php echo esc_attr($status_class[$item->status]); ?>">
                                <?php echo esc_html($status_labels[$item->status]); ?>
                            </span>
                        </td>
                        <td class="attempts column-attempts">
                            <?php 
                            if ($item->attempts > 0) {
                                printf(
                                    __('%d / 3', 'xautoposter'),
                                    $item->attempts
                                );
                            } else {
                                echo '0 / 3';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">
                        <?php _e('Kuyrukta bekleyen gönderi bulunmuyor.', 'xautoposter'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.queue-table-container {
    margin-top: 20px;
}

.status-pending {
    color: #646970;
}

.status-processing {
    color: #2271b1;
}

.status-completed {
    color: #46b450;
}

.status-failed {
    color: #dc3232;
}

.column-scheduled,
.column-status,
.column-attempts {
    width: 15%;
}
</style>