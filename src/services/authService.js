import jwt from 'jsonwebtoken';
import { config } from '../config/index.js';

class AuthService {
  constructor() {
    this.secret = config.jwt.secret;
    this.expiresIn = '1h';
  }

  generateToken(payload) {
    return jwt.sign(payload, this.secret, { expiresIn: this.expiresIn });
  }

  verifyToken(token) {
    try {
      return jwt.verify(token, this.secret);
    } catch (error) {
      return null;
    }
  }
}

export default new AuthService();