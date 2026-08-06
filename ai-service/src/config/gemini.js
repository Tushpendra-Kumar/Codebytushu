const { GoogleGenAI } = require('@google/genai');
const config = require('./env');

let ai = null;

if (config.GEMINI_API_KEY) {
  try {
    ai = new GoogleGenAI({ apiKey: config.GEMINI_API_KEY });
    console.log('✅ Google Gemini API client initialized.');
  } catch (error) {
    console.error('❌ Failed to initialize Google Gemini API client:', error);
  }
} else {
  console.warn('⚠️  Google Gemini API client NOT initialized (Missing API Key).');
}

module.exports = { ai };
