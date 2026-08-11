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
3. Keep the conversation natural and simple. Do not overcomplicate answers.

CRITICAL INSTRUCTION REGARDING LINKS AND URLs:
You must NEVER hallucinate, guess, or invent URLs. Only provide links to actual existing pages on the CodeByTushu website based EXACTLY on the following rules.
IMPORTANT: You MUST format ALL URLs as proper clickable Markdown links, for example: [Blogs](https://codebytushu.com/blogs/index.php). NEVER provide raw plain-text URLs.

--- STATIC PAGES DIRECTORY ---
- Home Page: [CodeByTushu Home](https://codebytushu.com/)
- LeetCode Section: [LeetCode](https://codebytushu.com/Leetcode/)
- Blogs (Main Page for all blogs): [CodeByTushu Blogs](https://codebytushu.com/blogs/index.php)
- Courses (Main Page for all courses): [CodeByTushu Courses](https://codebytushu.com/courses/)
- Video Editing Services: [Video Editing](https://codebytushu.com/video-editing/)
- Store / Merchandise: [CodeByTushu Store](https://codebytushu.com/store/)
- Donate Page: [Donate](https://codebytushu.com/Leetcode/donate.php)
- About Platform: [About Us](https://codebytushu.com/about-platform.html)
- Support / Contact: [Support](https://codebytushu.com/support/index.html)
- Login: [Login](https://codebytushu.com/auth/login.php)
- User Dashboard: [Dashboard](https://codebytushu.com/user/dashboard.php)

--- DYNAMIC PAGES (Individual Courses / Blogs) ---
If a user asks for a SPECIFIC course (e.g., "Java Masterclass") or a SPECIFIC blog (e.g., "Java OOP Concepts"):
1. Check the "REAL-TIME DATABASE KNOWLEDGE" section provided below in the prompt.
2. If the specific course or blog is listed there, use EXACTLY the Markdown link provided.
3. If the specific course or blog is NOT listed in the real-time knowledge, DO NOT invent a link like /courses/java-masterclass. Instead, provide the main directory link formatted as Markdown (e.g., [Courses](https://codebytushu.com/courses/)) and tell the user they can search for it there.

Never provide a link that results in a 404. If you don't know the exact URL, provide the closest valid parent directory from the STATIC PAGES DIRECTORY above, always as a clickable Markdown link.`;
}

/**
 * Core AI response generator.
 * Uses @google/genai v2.x streaming API.
 */
async function streamAIResponse(prompt, contextHistory = [], onChunk, attachment = null) {
  if (!config.GEMINI_API_KEY) {
    throw new Error('GEMINI_API_KEY is not configured in environment variables.');
  }

  const genAI = new GoogleGenAI({ apiKey: config.GEMINI_API_KEY });

  const contents = [];
  
  // Add previous history
  for (const msg of contextHistory) {
    if (msg.role === 'user' || msg.role === 'ai') {
      const partObj = { text: msg.text || '' };
      
      // If a historical message has an attachment, include it to preserve context
      if (msg.role === 'user' && msg.attachment && msg.attachment.fileUri) {
        contents.push({
          role: 'user',
          parts: [
            { fileData: { fileUri: msg.attachment.fileUri, mimeType: msg.attachment.mimeType } },
            partObj
          ]
        });
      } else {
        contents.push({
          role: msg.role === 'ai' ? 'model' : 'user',
          parts: [partObj]
        });
      }
    }
  }
  
  // Add current user message
  const currentParts = [];
  if (attachment && attachment.fileUri) {
    currentParts.push({ fileData: { fileUri: attachment.fileUri, mimeType: attachment.mimeType } });
  }
  currentParts.push({ text: prompt });

  contents.push({
    role: 'user',
    parts: currentParts
  });

  const systemInstruction = buildSystemInstruction();

  // Array of models to fallback through in case of rate limits or unavailability
  const FALLBACK_MODELS = [
    'gemini-3.5-flash',
    'gemini-3.5-flash-lite',
    'gemini-3.6-flash',
    'gemini-flash-latest'
  ];

  for (let i = 0; i < FALLBACK_MODELS.length; i++) {
    const currentModel = FALLBACK_MODELS[i];
    let streamStarted = false;

    try {
      console.log(`[AI Service] Attempting generation with model: ${currentModel}`);
      const result = await genAI.models.generateContentStream({
        model: currentModel,
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
          streamStarted = true;
          onChunk(text);
        }
      }
      
      // If we successfully streamed the entire response without crashing, we are done!
      console.log(`✅ [AI Service] Generation completed successfully using ${currentModel}`);
      return; 
      
    } catch (error) {
      console.error(`❌ [AI Service] Model ${currentModel} failed:`, error.message);
      
      // If we already sent partial response to the user, we cannot seamlessly switch models.
      // We must abort and let the user know the stream broke midway.
      if (streamStarted) {
         throw new Error(`Stream interrupted midway during generation with ${currentModel}.`);
      }
      
      const status = error.status || error.code || 500;
      
      // Do not fallback for 400 (Bad Request), 401/403 (Auth/Invalid Key)
      if (status === 400 || status === 401 || status === 403) {
         throw error;
      }
      
      // If it's the last model in our list, we have to throw the error up
      if (i === FALLBACK_MODELS.length - 1) {
         throw new Error('All configured AI models are currently unavailable due to high traffic or rate limits.');
      }
      
      // Otherwise, the loop continues and tries the next model in FALLBACK_MODELS!
      console.log(`⚠️ Switching to fallback model: ${FALLBACK_MODELS[i+1]}...`);
    }
  }
}

module.exports = { streamAIResponse };
