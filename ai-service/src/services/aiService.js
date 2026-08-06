const { ai } = require('../config/gemini');

/**
 * AI Service class to handle Gemini prompting and streaming.
 * Modularized for future RAG (Database Context) integration.
 */
class AIService {
  constructor() {
    this.modelName = 'gemini-2.5-flash';
  }

  /**
   * Base system prompt to instruct Gemini on its persona.
   */
  getSystemInstruction() {
    return `You are "CodeByTushu AI", an expert coding assistant created by Tushpendra Kumar for the CodeByTushu platform. 
Your goal is to help users with programming, DSA, LeetCode, Web Development, and DevOps.
Rules:
- Be concise and polite.
- Use markdown formatting.
- If the user asks about resources, courses, or blogs on CodeByTushu, recommend exploring the website.
- Do not mention that you are a Google model unless explicitly asked. You are the CodeByTushu AI.`;
  }

  /**
   * Generates a streaming response from Gemini.
   * 
   * @param {string} prompt - The user's input message.
   * @param {object} context - Additional context (e.g., current URL, user history).
   * @param {function} onChunk - Callback triggered when a chunk of text is received.
   */
  async streamResponse(prompt, context = {}, onChunk) {
    if (!ai) {
      throw new Error('Gemini API client is not initialized. Check your GEMINI_API_KEY.');
    }

    try {
      // Phase 3: Future RAG will inject context here
      const fullPrompt = prompt; 

      const responseStream = await ai.models.generateContentStream({
        model: this.modelName,
        contents: fullPrompt,
        config: {
          systemInstruction: this.getSystemInstruction(),
          temperature: 0.7,
        }
      });

      for await (const chunk of responseStream) {
        if (chunk.text) {
          onChunk(chunk.text);
        }
      }
    } catch (error) {
      console.error('Error generating AI response:', error);
      throw new Error('Failed to generate AI response.');
    }
  }
}

module.exports = new AIService();
