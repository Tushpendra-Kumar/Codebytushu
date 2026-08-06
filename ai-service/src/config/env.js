const dotenv = require('dotenv');

// Load environment variables from .env file
dotenv.config();

const config = {
  PORT: process.env.PORT || 3001,
  GEMINI_API_KEY: process.env.GEMINI_API_KEY || '',
  NODE_ENV: process.env.NODE_ENV || 'development',
  FRONTEND_ORIGIN: process.env.FRONTEND_ORIGIN || '*',
};

// Validate critical variables
if (!config.GEMINI_API_KEY) {
  console.warn('⚠️  WARNING: GEMINI_API_KEY is not set in .env file!');
}

module.exports = config;
