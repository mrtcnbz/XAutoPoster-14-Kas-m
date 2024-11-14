<?php
namespace XAutoPoster\Models;

class Queue {
    private $table_name;
    private const MIN_INTERVAL = 60; // 1 dakika (saniye cinsinden)
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'xautoposter_queue';
    }
    
    public function createTable() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            scheduled_time datetime DEFAULT NULL,
            attempts int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status (status),
            KEY scheduled_time (scheduled_time)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function addToQueue($postId) {
        global $wpdb;
        
        // Son planlanmış tweet zamanını al
        $lastScheduledTime = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT scheduled_time 
                FROM {$this->table_name} 
                WHERE status IN ('pending', 'processing') 
                ORDER BY scheduled_time DESC 
                LIMIT 1"
            )
        );
        
        // Yeni tweet için zamanı hesapla
        $scheduledTime = $this->calculateNextScheduledTime($lastScheduledTime);
        
        return $wpdb->insert(
            $this->table_name,
            [
                'post_id' => $postId,
                'status' => 'pending',
                'scheduled_time' => $scheduledTime
            ],
            ['%d', '%s', '%s']
        );
    }
    
    private function calculateNextScheduledTime($lastScheduledTime) {
        $now = current_time('mysql');
        
        if (empty($lastScheduledTime)) {
            // Kuyrukta hiç tweet yoksa şimdiden 1 dakika sonrasını planla
            return date('Y-m-d H:i:s', strtotime($now) + self::MIN_INTERVAL);
        }
        
        $nextTime = strtotime($lastScheduledTime) + self::MIN_INTERVAL;
        $currentTime = strtotime($now);
        
        // Eğer hesaplanan zaman geçmişte kalıyorsa şimdiden 1 dakika sonrasını planla
        if ($nextTime <= $currentTime) {
            $nextTime = $currentTime + self::MIN_INTERVAL;
        }
        
        return date('Y-m-d H:i:s', $nextTime);
    }
    
    public function getPendingPosts($limit = 10) {
        global $wpdb;
        
        $now = current_time('mysql');
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                AND attempts < 3 
                AND scheduled_time <= %s
                ORDER BY scheduled_time ASC 
                LIMIT %d",
                $now,
                $limit
            )
        );
    }
    
    public function markAsProcessing($postId) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'processing',
                'updated_at' => current_time('mysql')
            ],
            ['post_id' => $postId],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    public function markAsShared($postId) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'completed',
                'updated_at' => current_time('mysql')
            ],
            ['post_id' => $postId],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    public function incrementAttempts($postId) {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} 
                SET attempts = attempts + 1,
                scheduled_time = DATE_ADD(NOW(), INTERVAL %d SECOND)
                WHERE post_id = %d",
                self::MIN_INTERVAL,
                $postId
            )
        );
        
        // Maksimum deneme sayısına ulaşıldıysa durumu failed olarak işaretle
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table_name} 
                SET status = 'failed' 
                WHERE post_id = %d 
                AND attempts >= 3",
                $postId
            )
        );
    }
    
    public function cleanOldRecords($days = 30) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} 
                WHERE (status = 'completed' OR status = 'failed')
                AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
    }
    
    public function getQueueStats() {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
            FROM {$this->table_name}"
        );
        
        return $stats;
    }
    
    public function resetFailedPosts() {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            [
                'status' => 'pending',
                'attempts' => 0,
                'scheduled_time' => $this->calculateNextScheduledTime(null)
            ],
            ['status' => 'failed'],
            ['%s', '%d', '%s'],
            ['%s']
        );
    }
}