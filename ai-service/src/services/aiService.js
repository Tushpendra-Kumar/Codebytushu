const { GoogleGenAI } = require('@google/genai');
const config = require('../config/env');

// ============================================================
// CodeByTushu AI Service — Basic Flow
// SDK: @google/genai v2.x
// ============================================================

/**
 * System prompt builder for basic conversational flow.
 */
function buildSystemInstruction() {
  return `You are a helpful and simple AI assistant for CodeByTushu.
1. If the user greets you (e.g., Hello, Hi, Hey), you MUST respond exactly with: "Welcome to CodeByTushu! How can I help you?"
2. For any other topic or technical question (e.g., "What is Java?", "Explain React"), provide a simple, easy-to-understand, and concise answer.
3. Keep the conversation natural and simple. Do not overcomplicate answers.`;
}

/**
 * Core AI response generator.
 * Uses @google/genai v2.x streaming API.
 */
async function streamAIResponse(prompt, contextHistory = [], onChunk) {
  if (!config.GEMINI_API_KEY) {
    throw new Error('GEMINI_API_KEY is not configured in environment variables.');
  }

  const genAI = new GoogleGenAI({ apiKey: config.GEMINI_API_KEY });

  const contents = [];
  
  // Add previous history
  for (const msg of contextHistory) {
    if (msg.role === 'user' || msg.role === 'ai') {
      contents.push({
        role: msg.role === 'ai' ? 'model' : 'user',
        parts: [{ text: msg.text || '' }]
      });
    }
  }
  
  // Add current user message
  contents.push({
    role: 'user',
    parts: [{ text: prompt }]
  });

  const systemInstruction = buildSystemInstruction();

  try {
    const result = await genAI.models.generateContentStream({
      model: 'gemini-3.5-flash',
      contents: contents,
      config: {
        systemInstruction: systemInstruction,
        temperature: 0.7,
        maxOutputTokens: 1024,
      }
    });

    for await (const chunk of result) {
      let text = '';
      if (typeof chunk.text === 'function') {
        text = chunk.text();
      } else if (typeof chunk.text === 'string') {
        text = chunk.text;
      } else if (chunk.candidates?.[0]?.content?.parts?.[0]?.text) {
        text = chunk.candidates[0].content.parts[0].text;
      }
      
      if (text) {
        onChunk(text);
      }
    }
  } catch (error) {
    console.error('❌ Gemini API Error:', error.message);
    throw error;
  }
}

module.exports = { streamAIResponse };
