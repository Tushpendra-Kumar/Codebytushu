const { streamAIResponse } = require('../services/aiService');
const xss = require('xss');

// In-memory rate limiting (max 10 messages per 60 seconds per socket)
const rateLimits = new Map();

/**
 * Registers Socket.IO event listeners for chat functionality.
 */
function registerChatHandlers(io, socket) {
  
  socket.on('chat:send', async (data) => {
    // 1. Rate Limiting
    const now = Date.now();
    const userLimit = rateLimits.get(socket.id) || { count: 0, firstRequest: now };
    
    if (now - userLimit.firstRequest > 60000) {
      userLimit.count = 1;
      userLimit.firstRequest = now;
    } else {
      userLimit.count++;
      if (userLimit.count > 10) {
        socket.emit('chat:error', { message: 'Too many messages! Please wait a moment before sending more.' });
        return;
      }
    }
    rateLimits.set(socket.id, userLimit);

    // 2. Input Validation
    let { text, url, history } = data;
    if (typeof text !== 'string' || text.trim() === '') return;
    text = xss(text.trim()).substring(0, 800); // sanitize and cap at 800 chars

    // Sanitize history
    const safeHistory = Array.isArray(history) ? history.slice(-10).map(msg => ({
      role: msg.role,
      text: typeof msg.text === 'string' ? xss(msg.text).substring(0, 500) : ''
    })) : [];

    const urlContext = typeof url === 'string' ? url : '';

    // 3. Signal typing started
    socket.emit('chat:typing', { status: true });

    try {
      console.log(`📨 [Socket ${socket.id}] Message: "${text.substring(0, 60)}"`);
      
      // Stream response back
      await streamAIResponse(
        text,
        safeHistory,
        urlContext,
        (chunk) => {
          socket.emit('chat:chunk', { text: chunk });
        }
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

  socket.on('disconnect', () => {
    rateLimits.delete(socket.id);
  });
}

module.exports = registerChatHandlers;
