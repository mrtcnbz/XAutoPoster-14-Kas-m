import express from 'express';
import TwitterService from '../services/twitterService.js';
import { config } from '../config/index.js';
import logger from '../utils/logger.js';
import { authMiddleware } from '../middleware/auth.js';

const router = express.Router();
const twitter = new TwitterService(config.twitter);

// Apply auth middleware to all routes
router.use(authMiddleware);

router.post('/verify', async (req, res) => {
  try {
    const result = await twitter.verifyCredentials();
    res.json({ success: true, data: result });
  } catch (error) {
    logger.error('Verification error:', error);
    res.status(400).json({ success: false, error: error.message });
  }
});

router.post('/share', async (req, res) => {
  try {
    const { post } = req.body;
    const result = await twitter.sharePost(post);
    res.json({ success: true, data: result });
  } catch (error) {
    logger.error('Share error:', error);
    res.status(400).json({ success: false, error: error.message });
  }
});

router.get('/metrics/:tweetId', async (req, res) => {
  try {
    const { tweetId } = req.params;
    const metrics = await twitter.getTweetMetrics(tweetId);
    res.json({ success: true, data: metrics });
  } catch (error) {
    logger.error('Metrics error:', error);
    res.status(400).json({ success: false, error: error.message });
  }
});

export default router;