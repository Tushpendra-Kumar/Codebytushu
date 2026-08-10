const { streamAIResponse } = require('../services/aiService');
const xss = require('xss');

/**
 * Registers Socket.IO event listeners for basic chat functionality.
 */
function registerChatHandlers(io, socket) {
  
  socket.on('chat:send', async (data) => {
    // 1. Input Validation
    let { text, history } = data;
    if (typeof text !== 'string' || text.trim() === '') return;
    text = xss(text.trim()).substring(0, 800); // sanitize and cap at 800 chars

    // Sanitize history (keep it simple, last 5 messages)
    const safeHistory = Array.isArray(history) ? history.slice(-5).map(msg => ({
      role: msg.role,
      text: typeof msg.text === 'string' ? xss(msg.text).substring(0, 500) : ''
    })) : [];

    // 2. Signal typing started
    socket.emit('chat:typing', { status: true });

    try {
      console.log(`📨 [Socket ${socket.id}] Basic Message: "${text.substring(0, 60)}"`);
      
      // Stream response back using the simplified AI service
      await streamAIResponse(
        text,
        safeHistory,
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
}

module.exports = registerChatHandlers;
