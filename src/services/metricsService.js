import logger from '../utils/logger.js';
import cacheService from './cacheService.js';

class MetricsService {
  constructor() {
    this.metrics = new Map();
    this.startCollecting();
  }

  async collectMetrics(tweetId, twitterService) {
    try {
      const metrics = await twitterService.getTweetMetrics(tweetId);
      if (metrics) {
        this.metrics.set(tweetId, {
          ...metrics,
          collectedAt: new Date()
        });
        return metrics;
      }
      return null;
    } catch (error) {
      logger.error(`Failed to collect metrics for tweet ${tweetId}:`, error);
      return null;
    }
  }

  getMetrics(tweetId) {
    return this.metrics.get(tweetId) || null;
  }

  getAllMetrics() {
    return Array.from(this.metrics.entries()).map(([id, data]) => ({
      id,
      ...data
    }));
  }

  startCollecting(interval = 300000) { // 5 minutes
    setInterval(() => {
      this.collectAllMetrics();
    }, interval);
  }

  async collectAllMetrics() {
    const tweets = Array.from(this.metrics.keys());
    for (const tweetId of tweets) {
      await this.collectMetrics(tweetId);
    }
  }

  // Analytics methods
  getEngagementRate(tweetId) {
    const metrics = this.getMetrics(tweetId);
    if (!metrics || !metrics.public_metrics) return 0;

    const { like_count, retweet_count, reply_count } = metrics.public_metrics;
    return (like_count + retweet_count + reply_count) / 100; // Example calculation
  }

  getTopPerformingTweets(limit = 10) {
    return Array.from(this.metrics.entries())
      .map(([id, data]) => ({
        id,
        engagement: this.getEngagementRate(id),
        ...data
      }))
      .sort((a, b) => b.engagement - a.engagement)
      .slice(0, limit);
  }
}

export default new MetricsService();