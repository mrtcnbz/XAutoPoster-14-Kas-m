import express from 'express';
import metricsService from '../services/metricsService.js';
import { authMiddleware } from '../middleware/auth.js';
import logger from '../utils/logger.js';

const router = express.Router();

router.use(authMiddleware);

// Get metrics for a specific tweet
router.get('/tweet/:id', (req, res) => {
  try {
    const metrics = metricsService.getMetrics(req.params.id);
    if (!metrics) {
      return res.status(404).json({
        status: 'error',
        message: 'Metrics not found'
      });
    }
    res.json({
      status: 'success',
      data: metrics
    });
  } catch (error) {
    logger.error('Error fetching tweet metrics:', error);
    res.status(500).json({
      status: 'error',
      message: 'Failed to fetch metrics'
    });
  }
});

// Get all metrics
router.get('/all', (req, res) => {
  try {
    const metrics = metricsService.getAllMetrics();
    res.json({
      status: 'success',
      data: metrics
    });
  } catch (error) {
    logger.error('Error fetching all metrics:', error);
    res.status(500).json({
      status: 'error',
      message: 'Failed to fetch metrics'
    });
  }
});

// Get top performing tweets
router.get('/top', (req, res) => {
  try {
    const { limit = 10 } = req.query;
    const topTweets = metricsService.getTopPerformingTweets(parseInt(limit));
    res.json({
      status: 'success',
      data: topTweets
    });
  } catch (error) {
    logger.error('Error fetching top tweets:', error);
    res.status(500).json({
      status: 'error',
      message: 'Failed to fetch top tweets'
    });
  }
});

// Get engagement rate for a tweet
router.get('/engagement/:id', (req, res) => {
  try {
    const rate = metricsService.getEngagementRate(req.params.id);
    res.json({
      status: 'success',
      data: { rate }
    });
  } catch (error) {
    logger.error('Error calculating engagement rate:', error);
    res.status(500).json({
      status: 'error',
      message: 'Failed to calculate engagement rate'
    });
  }
});

export default router;