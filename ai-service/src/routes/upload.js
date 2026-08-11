const express = require('express');
const fs = require('fs/promises');
const path = require('path');
const os = require('os');
const { GoogleGenAI } = require('@google/genai');
const config = require('../config/env');

const router = express.Router();

// Allowed MIME types
const ALLOWED_MIMES = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'text/plain',
  'image/jpeg',
  'image/png',
  'image/webp',
  'application/json',
  'application/xml',
  'text/javascript',
  'text/html',
  'text/css',
  'text/markdown',
  'text/x-python',
  'text/x-java-source',
  'text/x-c',
  'text/x-c++',
  'text/x-csharp',
  'application/x-sql'
];

router.post('/upload', async (req, res) => {
  try {
    const { fileName, mimeType, data } = req.body;

    if (!fileName || !mimeType || !data) {
      return res.status(400).json({ error: 'Missing required file fields.' });
    }

    // Basic MIME validation
    if (!ALLOWED_MIMES.includes(mimeType) && !mimeType.startsWith('text/')) {
      return res.status(400).json({ error: 'Unsupported file type.' });
    }

    if (!config.GEMINI_API_KEY) {
      return res.status(500).json({ error: 'API Key not configured.' });
    }

    const genAI = new GoogleGenAI({ apiKey: config.GEMINI_API_KEY });

    // Ensure safe file name
    const safeName = fileName.replace(/[^a-zA-Z0-9.-]/g, '_');
    const tempPath = path.join(os.tmpdir(), `${Date.now()}-${safeName}`);

    // Decode base64
    const buffer = Buffer.from(data, 'base64');
    
    // Write to tmp dir
    await fs.writeFile(tempPath, buffer);

    try {
      // Upload to Gemini
      console.log(`[Upload] Uploading file to Gemini: ${safeName}`);
      const uploadResult = await genAI.files.upload({ 
        file: tempPath, 
        mimeType: mimeType 
      });

      // Cleanup temp file
      await fs.unlink(tempPath).catch(err => console.error('Error cleaning up temp file:', err));

      console.log(`[Upload] File uploaded successfully: URI = ${uploadResult.uri}`);
      
      return res.json({
        fileUri: uploadResult.uri,
        mimeType: mimeType,
        name: safeName
      });
      
    } catch (apiError) {
      // Cleanup temp file on error
      await fs.unlink(tempPath).catch(() => {});
      console.error('[Upload] Gemini API Error:', apiError);
      return res.status(500).json({ error: 'Failed to process file with AI engine.' });
    }

  } catch (error) {
    console.error('[Upload] Server error:', error);
    return res.status(500).json({ error: 'Internal server error during upload.' });
  }
});

module.exports = router;
