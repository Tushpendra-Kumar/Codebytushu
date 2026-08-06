// ─── useSocket Hook (Phase 2 Real-Time Implementation) ──────────────────────
// Establishes a Socket.IO connection to the Node.js backend.
// ─────────────────────────────────────────────────────────────────────────────

import { useState, useEffect, useCallback, useRef } from 'react';
import { io } from 'socket.io-client';

// The backend AI microservice URL
// In production, this should be an environment variable or the production URL
const SOCKET_URL = 'http://localhost:3001';

export function useSocket() {
  const [socket, setSocket] = useState(null);
  const [connected, setConnected] = useState(false);
  const socketRef = useRef(null);

  useEffect(() => {
    let activeSocket = null;

    const connectSocket = async () => {
      try {
        // Step 1: Fetch JWT Token for authentication
        const response = await fetch(`${SOCKET_URL}/api/token`);
        if (!response.ok) throw new Error('Failed to fetch token');
        const data = await response.json();
        
        // Step 2: Initialize Socket with token
        activeSocket = io(SOCKET_URL, {
          auth: { token: data.token },
          reconnectionAttempts: 5,
          timeout: 10000,
        });

        activeSocket.on('connect', () => {
          console.log('✅ Connected to CodeByTushu AI Engine');
          setConnected(true);
        });

        activeSocket.on('disconnect', () => {
          console.log('🔴 Disconnected from AI Engine');
          setConnected(false);
        });

        activeSocket.on('connect_error', (err) => {
          console.error('Socket connection error:', err.message);
          setConnected(false);
        });

        setSocket(activeSocket);
        socketRef.current = activeSocket;
      } catch (err) {
        console.error('Failed to initialize socket:', err.message);
      }
    };

    connectSocket();

    return () => {
      if (activeSocket) {
        activeSocket.disconnect();
      }
    };
  }, []);

  const sendMessage = useCallback((event, payload) => {
    if (socketRef.current && socketRef.current.connected) {
      socketRef.current.emit(event, payload);
    } else {
      console.error('Socket not connected, message not sent');
    }
  }, []);

  return { socket, connected, sendMessage };
}
