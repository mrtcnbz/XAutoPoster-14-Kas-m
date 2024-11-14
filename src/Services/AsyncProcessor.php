<?php
namespace XAutoPoster\Services;

class AsyncProcessor {
    private $logger;
    private $queue;

    public function __construct(Logger $logger, Models\Queue $queue) {
        $this->logger = $logger;
        $this->queue = $queue;
    }

    public function schedulePost($postId) {
        if (!wp_next_scheduled('xautoposter_process_post', [$postId])) {
            wp_schedule_single_event(time() + 10, 'xautoposter_process_post', [$postId]);
        }
    }

    public function processPost($postId) {
        try {
            $this->logger->info("Processing post ID: {$postId}");
            
            // İşlem başladı olarak işaretle
            $this->queue->markAsProcessing($postId);
            
            // Twitter API servisini başlat
            $api = new ApiService();
            
            // Gönderiyi paylaş
            $post = get_post($postId);
            $result = $api->sharePost($post);
            
            if ($result && isset($result->data->id)) {
                update_post_meta($postId, '_xautoposter_shared', '1');
                update_post_meta($postId, '_xautoposter_share_time', current_time('mysql'));
                update_post_meta($postId, '_xautoposter_tweet_id', $result->data->id);
                
                $this->queue->markAsShared($postId);
                $this->logger->info("Post {$postId} shared successfully", [
                    'tweet_id' => $result->data->id
                ]);
            } else {
                throw new \Exception('Tweet paylaşılamadı');
            }
        } catch (\Exception $e) {
            $this->logger->error("Error processing post {$postId}: " . $e->getMessage());
            $this->queue->incrementAttempts($postId);
        }
    }
}