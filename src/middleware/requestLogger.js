import logger from '../utils/logger.js';

export const requestLogger = (req, res, next) => {
  const startTime = Date.now();
  const requestContext = logger.requestContext(req);

  logger.info('Incoming request', {
    ...requestContext,
    body: req.body
  });

  res.on('finish', () => {
    const duration = Date.now() - startTime;
    logger.info('Request completed', {
      ...requestContext,
      statusCode: res.statusCode,
      duration: `${duration}ms`
    });
  });

  next();
};