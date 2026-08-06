const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const cors = require('cors');
const helmet = require('helmet');
const jwt = require('jsonwebtoken');
const rateLimit = require('express-rate-limit');

const config = require('./config/env');
const registerChatHandlers = require('./sockets/chatHandler');

// Initialize Express App
const app = express();

// Security Middleware
app.use(helmet());
app.use(cors({
  origin: '*', // Allow all origins for the public widget
  methods: ['GET', 'POST']
}));
app.use(express.json());

// API Rate Limiter (to prevent token spam)
const tokenLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 20, // Limit each IP to 20 requests per window
  message: { error: 'Too many requests for a chat session. Please try again later.' }
});

// Basic Health Check Route
app.get('/api/health', (req, res) => {
  res.status(200).json({ status: 'ok', service: 'CodeByTushu AI Engine' });
});

// JWT Token Generator Route (Anonymous Session)
app.get('/api/token', tokenLimiter, (req, res) => {
  // Generate a token valid for 24 hours
  const payload = { role: 'user', session: Date.now() };
  const token = jwt.sign(payload, config.JWT_SECRET, { expiresIn: '24h' });
  res.status(200).json({ token });
});

// Create HTTP Server
const server = http.createServer(app);

// Initialize Socket.IO Server
const io = new Server(server, {
  cors: {
    origin: '*',
    methods: ["GET", "POST"]
  }
});

// Socket.IO Authentication Middleware
io.use((socket, next) => {
  const token = socket.handshake.auth?.token;
  if (!token) {
    return next(new Error('Authentication error: No token provided'));
  }
  
  jwt.verify(token, config.JWT_SECRET, (err, decoded) => {
    if (err) {
      return next(new Error('Authentication error: Invalid or expired token'));
    }
    socket.user = decoded;
    next();
  });
});

// Setup Socket Connections
io.on('connection', (socket) => {
  console.log(`🔌 Client connected: ${socket.id} (Session: ${socket.user.session})`);

  // Register all chat events for this socket
  registerChatHandlers(io, socket);

  socket.on('disconnect', () => {
    console.log(`🔴 Client disconnected: ${socket.id}`);
  });
});

// Start Server
server.listen(config.PORT, '0.0.0.0', () => {
  console.log(`🚀 CodeByTushu AI Engine running on port ${config.PORT}`);
});

