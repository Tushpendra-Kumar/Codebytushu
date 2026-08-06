const { GoogleGenAI } = require('@google/genai');
const config = require('../config/env');

// ============================================================
// CodeByTushu AI Service — Complete Rewrite
// SDK: @google/genai v2.x
// ============================================================

/**
 * System prompt builder — makes AI behave like ChatGPT but
 * with CodeByTushu context as highest priority.
 */
function buildSystemInstruction(urlContext) {
  return `You are "CodeByTushu AI" — a smart, friendly, and highly professional AI assistant built by Tushpendra Kumar for the CodeByTushu platform (codebytushu.com).

PERSONALITY:
- Be conversational, warm, and helpful like ChatGPT.
- Use natural Hinglish/English mix when appropriate.
- Keep answers concise but thorough.
- Use emojis occasionally to keep things friendly.

WEBSITE KNOWLEDGE:
The CodeByTushu platform offers:
1. **Courses** — Web Development, DSA, JavaScript, React, Java etc → /courses/
2. **LeetCode Solutions** — Daily DSA problems with video explanations → /Leetcode/
3. **Blogs** — Technical articles on programming → /blogs/
4. **Store** — Digital products, T-Shirts, Source codes → /store/
5. **Video Editing** — Professional video editing services → /Video%20Editing/
6. **Donate** — Support the creator → /Donate/
7. **Login/Signup** → /auth/

BEHAVIOR RULES:
1. **Website queries FIRST**: If the user asks about anything available on CodeByTushu (courses, DSA, Java, React, Web Dev, blogs, store, T-shirts, video editing), give a helpful answer AND suggest the relevant page link.
   - Example format: "Here's a quick answer... ✅\n\nFor more details, check out: [Courses Page](/courses/)"
   
2. **Out-of-scope queries**: If the user asks something NOT related to the website (like ".NET", "Python basics", general programming questions), answer it FULLY like a normal AI (use your Gemini knowledge). Do NOT redirect them to the website. Just help them.

3. **NEVER say** "I can only answer website-related questions" or "Visit the website for this". Either answer helpfully OR guide to the right page.

4. **Greeting Flow**: When someone says "hello", "hi", "hey" etc., respond naturally:
   "Welcome to CodeByTushu! 👋 I'm your AI assistant here to help you with programming, DSA, courses, blogs, and anything on this website. What would you like to explore today?"

5. **Links format**: Always use relative links like [Courses Page](/courses/), [LeetCode](/Leetcode/), [Store](/store/) etc.

${urlContext ? `\n[Current Page Context: User is on "${urlContext}"]\n` : ''}`;
}

/**
 * Core AI response generator.
 * Uses @google/genai v2.x streaming API.
 */
async function streamAIResponse(prompt, contextHistory = [], urlContext = '', onChunk) {
  if (!config.GEMINI_API_KEY) {
    throw new Error('GEMINI_API_KEY is not configured in environment variables.');
  }

  // Fresh AI client (safe for serverless/free tier)
  const genAI = new GoogleGenAI({ apiKey: config.GEMINI_API_KEY });

  // Build contents array (chat history + current message)
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

  const systemInstruction = buildSystemInstruction(urlContext);

  console.log(`🤖 Generating response for: "${prompt.substring(0, 50)}..."`);

  try {
    // Use generateContentStream with correct v2.x API format
    const result = await genAI.models.generateContentStream({
      model: 'gemini-2.5-flash',
      contents: contents,
      config: {
        systemInstruction: systemInstruction,
        temperature: 0.7,
        maxOutputTokens: 1024,
      }
    });

    // Stream chunks back
    for await (const chunk of result) {
      // In @google/genai v2.x, chunk.text is a getter/function
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
    
    console.log('✅ Response stream completed successfully.');
    
  } catch (error) {
    console.error('❌ Gemini API Error Details:');
    console.error('   Name:', error.name);
    console.error('   Message:', error.message);
    if (error.status) console.error('   Status:', error.status);
    if (error.errorDetails) console.error('   Details:', JSON.stringify(error.errorDetails));
    throw error;
  }
}

module.exports = { streamAIResponse };
