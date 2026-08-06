const dotenv = require('dotenv');

// Load environment variables from .env file
dotenv.config();

const config = {
  PORT: process.env.PORT || 3001,
  GEMINI_API_KEY: process.env.GEMINI_API_KEY || '',
  NODE_ENV: process.env.NODE_ENV || 'development',
  FRONTEND_ORIGIN: process.env.FRONTEND_ORIGIN || '*',
  JWT_SECRET: process.env.JWT_SECRET || 'codebytushu_secret_key_change_this_in_production',
  DB_HOST: process.env.DB_HOST || '127.0.0.1',
  DB_USER: process.env.DB_USER || 'root',
  DB_PASS: process.env.DB_PASS || '',
  DB_NAME: process.env.DB_NAME || 'codebytushu',
};

// Validate critical variables
if (!config.GEMINI_API_KEY) {
  console.warn('⚠️  WARNING: GEMINI_API_KEY is not set in .env file!');
}

module.exports = config;
