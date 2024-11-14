import authService from '../services/authService.js';
import logger from '../utils/logger.js';

export const authMiddleware = (req, res, next) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    if (!token) {
      return res.status(401).json({ 
        status: 'error', 
        message: 'Authentication required' 
      });
    }

    const decoded = authService.verifyToken(token);
    if (!decoded) {
      return res.status(401).json({ 
        status: 'error', 
        message: 'Invalid token' 
      });
    }

    req.user = decoded;
    next();
  } catch (error) {
    logger.error('Auth error:', error);
    res.status(401).json({ 
      status: 'error', 
      message: 'Authentication failed' 
    });
  }
};