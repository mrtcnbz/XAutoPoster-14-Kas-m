import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

export const config = {
  port: process.env.PORT || 3000,
  nodeEnv: process.env.NODE_ENV || 'development',
  wordpress: {
    apiUrl: process.env.WP_API_URL || 'http://localhost/wp-json/wp/v2',
    username: process.env.WP_USERNAME,
    password: process.env.WP_PASSWORD
  },
  twitter: {
    apiKey: process.env.TWITTER_API_KEY,
    apiSecret: process.env.TWITTER_API_SECRET,
    accessToken: process.env.TWITTER_ACCESS_TOKEN,
    accessTokenSecret: process.env.TWITTER_ACCESS_TOKEN_SECRET
  },
  jwt: {
    secret: process.env.JWT_SECRET || 'your-secret-key'
  },
  cors: {
    origin: process.env.CORS_ORIGIN || '*'
  },
  morgan: {
    format: process.env.NODE_ENV === 'production' ? 'combined' : 'dev'
  }
};