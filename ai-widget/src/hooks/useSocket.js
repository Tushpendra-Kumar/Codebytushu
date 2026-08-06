// ─── useSocket Hook (Phase 2 Real-Time Implementation) ──────────────────────
// Establishes a Socket.IO connection to the Node.js backend.
// ─────────────────────────────────────────────────────────────────────────────

import { useState, useEffect, useCallback } from 'react';
import { io } from 'socket.io-client';

// The backend AI microservice URL
// In production, this should be an environment variable or the production URL
const SOCKET_URL = 'http://localhost:3001';

export function useSocket() {
  const [socket, setSocket] = useState(null);
  const [connected, setConnected] = useState(false);

  useEffect(() => {
    // Initialize socket connection
    const newSocket = io(SOCKET_URL, {
      reconnectionAttempts: 5,
      reconnectionDelay: 1000,
    });

    newSocket.on('connect', () => {
      console.log('✅ Connected to CodeByTushu AI Engine');
      setConnected(true);
    });

    newSocket.on('disconnect', () => {
      console.log('🔴 Disconnected from AI Engine');
      setConnected(false);
    });

    setSocket(newSocket);

    // Cleanup on unmount
    return () => {
      newSocket.disconnect();
    };
  }, []);

  const sendMessage = useCallback((event, data) => {
    if (socket && connected) {
      socket.emit(event, data);
    } else {
      console.warn('[useSocket] Cannot emit event. Socket not connected.', event, data);
    }
  }, [socket, connected]);

  return { socket, connected, sendMessage };
}
