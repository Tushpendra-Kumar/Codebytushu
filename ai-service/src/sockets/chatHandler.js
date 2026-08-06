const aiService = require('../services/aiService');

/**
 * Registers Socket.IO event listeners for chat functionality.
 * @param {import('socket.io').Server} io 
 * @param {import('socket.io').Socket} socket 
 */
function registerChatHandlers(io, socket) {
  
  socket.on('chat:send', async (data) => {
    const { text, url, history } = data;

    if (!text || text.trim() === '') return;

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
