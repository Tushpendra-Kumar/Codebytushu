const { streamAIResponse } = require('../services/aiService');
const xss = require('xss');
const db = require('../config/db'); // Import DB pool for feedback storage

/**
 * Registers Socket.IO event listeners for basic chat functionality.
 */
function registerChatHandlers(io, socket) {
  
  socket.on('chat:send', async (data) => {
    // 1. Input Validation
    let { text, history, attachment } = data;
    if (typeof text !== 'string' || text.trim() === '') return;
    text = xss(text.trim()).substring(0, 800); // sanitize and cap at 800 chars

    // Sanitize history (keep it simple, last 5 messages)
    const safeHistory = Array.isArray(history) ? history.slice(-5).map(msg => ({
      role: msg.role,
      text: typeof msg.text === 'string' ? xss(msg.text).substring(0, 500) : '',
      attachment: msg.attachment && typeof msg.attachment === 'object' ? {
        fileUri: typeof msg.attachment.fileUri === 'string' ? msg.attachment.fileUri : null,
        mimeType: typeof msg.attachment.mimeType === 'string' ? msg.attachment.mimeType : null,
        name: typeof msg.attachment.name === 'string' ? xss(msg.attachment.name).substring(0, 100) : null
      } : null
    })) : [];

    // Sanitize current attachment
    const safeAttachment = attachment && typeof attachment === 'object' ? {
      fileUri: typeof attachment.fileUri === 'string' ? attachment.fileUri : null,
      mimeType: typeof attachment.mimeType === 'string' ? attachment.mimeType : null,
      name: typeof attachment.name === 'string' ? xss(attachment.name).substring(0, 100) : null
    } : null;

    // 2. Signal typing started
    socket.emit('chat:typing', { status: true });

    try {
      console.log(`📨 [Socket ${socket.id}] Basic Message: "${text.substring(0, 60)}" ${safeAttachment ? '+ Attachment' : ''}`);
      
      // Stream response back using the simplified AI service
      await streamAIResponse(
        text,
        safeHistory,
        (chunk) => {
          socket.emit('chat:chunk', { text: chunk });
        },
        safeAttachment
      );

      // Signal stream end
      socket.emit('chat:end');
      console.log(`✅ [Socket ${socket.id}] Response sent successfully.`);
      
    } catch (error) {
      console.error(`❌ [Socket ${socket.id}] Chat handler error:`, error.message);
      socket.emit('chat:error', { 
        message: 'Sorry, something went wrong. Please try again in a moment.' 
      });
    } finally {
      socket.emit('chat:typing', { status: false });
    }
  });

  // Handle AI Feedback (Like/Dislike)
  socket.on('chat:feedback', async (data) => {
    let { userId, sessionId, messageId, feedbackType } = data;
    
    // Basic validation
    if (!sessionId || !messageId || !['like', 'dislike'].includes(feedbackType)) {
      return; // Silently ignore invalid feedback
    }

    // Sanitize strings just to be safe
    const sUserId = typeof userId === 'string' ? xss(userId).substring(0, 255) : null;
    const sSessionId = xss(sessionId).substring(0, 255);
    const sMessageId = xss(messageId).substring(0, 255);

    try {
      // Use INSERT ... ON DUPLICATE KEY UPDATE to elegantly handle switching from like to dislike
      const query = `
        INSERT INTO ai_feedback (user_id, session_id, message_id, feedback_type) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        feedback_type = VALUES(feedback_type), 
        updated_at = CURRENT_TIMESTAMP
      `;
      
      await db.execute(query, [sUserId, sSessionId, sMessageId, feedbackType]);
      console.log(`👍👎 [Socket ${socket.id}] Feedback saved: ${feedbackType} for msg ${sMessageId}`);
    } catch (error) {
      // We log but don't emit error to user to avoid disrupting chat
      console.error(`❌ [Socket ${socket.id}] Failed to save feedback:`, error.message);
    }
  });
}

module.exports = registerChatHandlers;
