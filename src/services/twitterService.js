import { TwitterApi } from 'twitter-api-v2';
import logger from '../utils/logger.js';
import cacheService from './cacheService.js';

class TwitterService {
  constructor(config) {
    this.client = new TwitterApi({
      appKey: config.apiKey,
      appSecret: config.apiSecret,
      accessToken: config.accessToken,
      accessSecret: config.accessTokenSecret,
    });
    this.readWrite = this.client.readWrite;
  }

  async verifyCredentials() {
    const cacheKey = 'twitter_credentials';
    const cached = cacheService.get(cacheKey);
    if (cached) return cached;

    try {
      const result = await this.readWrite.v2.me();
      cacheService.set(cacheKey, result.data, 3600); // Cache for 1 hour
      return result.data;
    } catch (error) {
      logger.error('Twitter verification error:', error);
      throw new Error('Failed to verify Twitter credentials');
    }
  }

  async sharePost(post) {
    try {
      const { title, link, hashtags } = this.formatPostData(post);
      const mediaIds = await this.uploadMedia(post.featuredImage);
      
      const tweet = await this.readWrite.v2.tweet({
        text: this.createTweetText(title, link, hashtags),
        media: mediaIds.length ? { media_ids: mediaIds } : undefined
      });

      return tweet;
    } catch (error) {
      logger.error('Twitter share error:', error);
      throw new Error('Failed to share post on Twitter');
    }
  }

  async getTweetMetrics(tweetId) {
    const cacheKey = `tweet_metrics_${tweetId}`;
    const cached = cacheService.get(cacheKey);
    if (cached) return cached;

    try {
      const tweet = await this.readWrite.v2.singleTweet(tweetId, {
        'tweet.fields': ['public_metrics', 'created_at']
      });
      cacheService.set(cacheKey, tweet.data, 300); // Cache for 5 minutes
      return tweet.data;
    } catch (error) {
      logger.error('Twitter metrics error:', error);
      return null;
    }
  }

  private async uploadMedia(imageUrl) {
    if (!imageUrl) return [];
    
    try {
      const response = await fetch(imageUrl);
      const buffer = await response.buffer();
      const mediaId = await this.readWrite.v1.uploadMedia(buffer, {
        mimeType: response.headers.get('content-type')
      });
      return [mediaId];
    } catch (error) {
      logger.error('Media upload error:', error);
      return [];
    }
  }

  private formatPostData(post) {
    return {
      title: post.title,
      link: post.link,
      hashtags: post.categories.map(cat => `#${cat.replace(/\s+/g, '')}`)
    };
  }

  private createTweetText(title, link, hashtags) {
    const maxHashtags = 3;
    const hashtagStr = hashtags.slice(0, maxHashtags).join(' ');
    const text = `${title} ${link} ${hashtagStr}`;
    return text.substring(0, 280);
  }
}

export default TwitterService;