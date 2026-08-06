const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const helmet = require('helmet');

const config = require('./config/env');
const registerChatHandlers = require('./sockets/chatHandler');

// Initialize Express App
const app = express();

// Security Middleware
app.use(helmet());
app.use(cors({
  origin: config.FRONTEND_ORIGIN,
  methods: ['GET', 'POST']
}));
app.use(express.json());

// Basic Health Check Route
app.get('/api/health', (req, res) => {
  res.status(200).json({ status: 'ok', service: 'CodeByTushu AI Engine' });
});

// Create HTTP Server
const server = http.createServer(app);

// Initialize Socket.IO Server
const io = new Server(server, {
  cors: {
    origin: config.FRONTEND_ORIGIN,
    methods: ["GET", "POST"]
  }
});

// Setup Socket Connections
io.on('connection', (socket) => {
  console.log(`🔌 Client connected: ${socket.id}`);

  // Register all chat events for this socket
  registerChatHandlers(io, socket);

  socket.on('disconnect', () => {
    console.log(`🔴 Client disconnected: ${socket.id}`);
  });
});

// Start Server
server.listen(config.PORT, () => {
  console.log(`🚀 CodeByTushu AI Engine running on port ${config.PORT}`);
});
