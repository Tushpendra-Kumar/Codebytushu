const aiService = require('../services/aiService');
const xss = require('xss');

// In-memory rate limiting (max 5 requests per 30 seconds per socket)
const rateLimits = new Map();

/**
 * Registers Socket.IO event listeners for chat functionality.
 * @param {import('socket.io').Server} io 
 * @param {import('socket.io').Socket} socket 
 */
function registerChatHandlers(io, socket) {
  
  socket.on('chat:send', async (data) => {
    // 1. Rate Limiting Check
    const now = Date.now();
    const userLimit = rateLimits.get(socket.id) || { count: 0, firstRequest: now };
    
    if (now - userLimit.firstRequest > 30000) {
      userLimit.count = 1;
      userLimit.firstRequest = now;
    } else {
      userLimit.count++;
      if (userLimit.count > 5) {
        socket.emit('chat:error', { message: 'You are sending messages too fast. Please wait 30 seconds.' });
        return;
      }
    }
    rateLimits.set(socket.id, userLimit);

    // 2. Input Validation and Sanitization
    let { text, url, history } = data;
    if (typeof text !== 'string' || text.trim() === '') return;

    // Cap length to 500 characters
    if (text.length > 500) {
      text = text.substring(0, 500);
    }
    
    // Sanitize to prevent XSS
    text = xss(text.trim());

    // Acknowledge receipt and signal typing
    socket.emit('chat:typing', { status: true });

    try {
      // Call the AI Service and pass a callback to stream chunks back to the client
      await aiService.streamResponse(
        text,
        { url, history },
        (chunk) => {
          socket.emit('chat:chunk', { text: chunk });
        }
      );

      // Signal the end of the stream
      socket.emit('chat:end');
    } catch (error) {
      console.error(`Socket ${socket.id} error:`, error.message);
      socket.emit('chat:error', { message: 'Sorry, I am having trouble connecting to my brain right now.' });
    } finally {
      socket.emit('chat:typing', { status: false });
    }
  });

  socket.on('disconnect', () => {
    // Cleanup if needed
  });
}

module.exports = registerChatHandlers;
