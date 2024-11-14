import logger from '../utils/logger.js';

export const errorHandler = (err, req, res, next) => {
  const errorContext = {
    method: req.method,
    url: req.url,
    ip: req.ip,
    userAgent: req.get('user-agent'),
    body: req.body,
    stack: err.stack
  };

  logger.error(`${err.name}: ${err.message}`, errorContext);
  
  const statusCode = err.status || 500;
  const message = err.message || 'Internal Server Error';
  
  res.status(statusCode).json({
    status: 'error',
    message,
    ...(process.env.NODE_ENV === 'development' && { stack: err.stack })
  });
};

export const notFoundHandler = (req, res) => {
  logger.warn('Route not found', logger.requestContext(req));
  
  res.status(404).json({
    status: 'error',
    message: 'Route not found'
  });
};