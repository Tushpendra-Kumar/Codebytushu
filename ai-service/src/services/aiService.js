const { ai } = require('../config/gemini');
const dbService = require('./dbService');

/**
 * AI Service class to handle Gemini prompting and streaming.
 * Modularized for future RAG (Database Context) integration.
 */
class AIService {
  constructor() {
    this.modelName = 'gemini-2.5-flash';
  }

  /**
   * Generates dynamic system instructions based on the current URL context and DB.
   */
  async getSystemInstruction(urlContext) {
    let basePrompt = `You are "CodeByTushu AI", an expert coding assistant created by Tushpendra Kumar for the CodeByTushu platform. 
Your goal is to help users with programming, DSA, Web Development, and navigating the CodeByTushu website.

Strict Rules:
1. Always be polite, concise, and highly professional.
2. If asked about a topic unrelated to programming, tech, or this website, politely decline.
3. You MUST use markdown links when referring to website sections. Use EXACTLY these links:
   - Home: [Home](/)
   - Courses (Premium Content): [Courses](/courses/)
   - LeetCode DSA Solutions: [LeetCode](/Leetcode/)
   - Blogs & Articles: [Blogs](/blogs/)
   - Store (Digital Products): [Store](/store/)
   - Video Editing Services: [Video Editing](/Video%20Editing/)
   - Donate/Support: [Donate](/Donate/)
   - Login: [Login](/auth/)
4. Do NOT mention you are a Google model unless explicitly asked. You are the CodeByTushu AI.
`;

    // 1. URL-based Dynamic Context Injection
    if (urlContext) {
      basePrompt += `\n[System Context: The user is currently browsing the path: "${urlContext}"]\n`;
      if (urlContext.includes('/Leetcode')) {
        basePrompt += `Rule: The user is in the LeetCode section. Focus your answers on Data Structures and Algorithms (DSA), time/space complexity, and optimal solutions. Suggest checking out the [LeetCode](/Leetcode/) section for more solutions.\n`;
      } else if (urlContext.includes('/courses')) {
        basePrompt += `Rule: The user is in the Courses section. Promote learning and suggest purchasing premium web development courses available on the [Courses](/courses/) page.\n`;
      } else if (urlContext.includes('/store')) {
        basePrompt += `Rule: The user is in the Store. Answer questions related to purchasing digital products, source codes, and assets.\n`;
      } else if (urlContext.includes('/blogs')) {
        basePrompt += `Rule: The user is reading Blogs. Suggest related articles from the [Blogs](/blogs/) section and provide deep theoretical explanations.\n`;
      }
    }

    // 2. Database RAG Context Injection
    const dbContext = await dbService.buildDynamicContext(urlContext);
    basePrompt += dbContext;

    return basePrompt;
  }

  /**
   * Generates a streaming response from Gemini.
   */
  async streamResponse(prompt, context = {}, onChunk) {
    if (!ai) {
      throw new Error('Gemini API client is not initialized. Check your GEMINI_API_KEY.');
    }

    try {
      // Create chat history format for Gemini
      const history = (context.history || []).map(msg => ({
        role: msg.role === 'ai' ? 'model' : 'user',
        parts: [{ text: msg.text }]
      }));
      
      // We push the latest prompt as the current message
      history.push({ role: 'user', parts: [{ text: prompt }] });

      // Build the dynamic instruction asynchronously
      const systemInstruction = await this.getSystemInstruction(context.url);

      const responseStream = await ai.models.generateContentStream({
        model: this.modelName,
        contents: history,
        config: {
          systemInstruction: systemInstruction,
          temperature: 0.7,
        }
      });

      for await (const chunk of responseStream) {
        // In @google/genai SDK, chunk.text is a function, not a property
        const text = typeof chunk.text === 'function' ? chunk.text() : chunk.text;
        if (text) {
          onChunk(text);
        }
      }
    } catch (error) {
      // Log the full error for debugging on Render
      console.error('❌ [aiService] Error generating AI response:');
      console.error('   Message:', error.message);
      console.error('   Stack:', error.stack);
      if (error.response) {
        console.error('   API Response:', JSON.stringify(error.response, null, 2));
      }
      throw new Error('Failed to generate AI response.');
    }
  }
}

module.exports = new AIService();
