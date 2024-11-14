import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import morgan from 'morgan';
import { router } from './routes/index.js';
import wordpressRouter from './routes/wordpress.js';
import metricsRouter from './routes/metrics.js';
import { errorHandler } from './middleware/errorHandler.js';
import { requestLogger } from './middleware/requestLogger.js';
import { config } from './config/index.js';
import logger from './utils/logger.js';
import cacheService from './services/cacheService.js';

const app = express();

// Start cache cleanup
cacheService.startCleanup();

// Create logs directory
import { mkdir } from 'fs/promises';
try {
  await mkdir('logs', { recursive: true });
} catch (error) {
  logger.error('Failed to create logs directory:', error);
}

// Security middleware
app.use(helmet());
app.use(cors(config.cors));

// Logging middleware
app.use(morgan(config.morgan.format));
app.use(requestLogger);

// Parse JSON bodies
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// API routes
app.use('/api', router);
app.use('/wordpress', wordpressRouter);
app.use('/metrics', metricsRouter);

// Error handling
app.use(errorHandler);

// Graceful shutdown
process.on('SIGTERM', () => {
  logger.info('SIGTERM received. Shutting down gracefully...');
  server.close(() => {
    logger.info('Process terminated');
    process.exit(0);
  });
});

export default app;