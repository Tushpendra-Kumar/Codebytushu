// ─── useSocket Hook (Phase 2 Real-Time Implementation) ──────────────────────
// Establishes a Socket.IO connection to the Node.js backend.
// ─────────────────────────────────────────────────────────────────────────────

import { useState, useEffect, useCallback, useRef } from 'react';
import { io } from 'socket.io-client';

// Dynamically determine the backend AI microservice URL
const SOCKET_URL = window.location.hostname.includes('codebytushu.com')
  ? 'https://codebytushu.onrender.com' // Production Node.js Server URL (Render)
  : 'http://127.0.0.1:3001';           // Local Development URL

export function useSocket() {
  const [socket, setSocket] = useState(null);
  const [connected, setConnected] = useState(false);
  const [connectionError, setConnectionError] = useState(null);
  const socketRef = useRef(null);

  useEffect(() => {
    let activeSocket = null;

    const connectSocket = async () => {
      try {
        console.log('[AI Widget] Attempting to connect to AI Engine at:', SOCKET_URL);
        
        // Step 1: Fetch JWT Token for authentication
        console.log('[AI Widget] Fetching authentication token...');
        const response = await fetch(`${SOCKET_URL}/api/token`).catch(err => {
          throw new Error(`Network fetch failed: ${err.message}`);
        });
        
        if (!response.ok) {
          throw new Error(`Token endpoint returned status: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('[AI Widget] Token received, initializing Socket.IO connection...');
        
        // Step 2: Initialize Socket with token
        activeSocket = io(SOCKET_URL, {
          auth: { token: data.token },
          reconnectionAttempts: 5,
          timeout: 10000,
          transports: ['websocket', 'polling']
        });

        activeSocket.on('connect', () => {
          console.log('✅ [AI Widget] Connected to CodeByTushu AI Engine successfully!');
          setConnected(true);
          setConnectionError(null);
        });

        activeSocket.on('disconnect', (reason) => {
          console.log(`🔴 [AI Widget] Disconnected from AI Engine. Reason: ${reason}`);
          setConnected(false);
        });

        activeSocket.on('connect_error', (err) => {
          console.error('❌ [AI Widget] Socket connection error:', err.message);
          setConnected(false);
          setConnectionError(`Socket Error: ${err.message}`);
        });

        setSocket(activeSocket);
        socketRef.current = activeSocket;
      } catch (err) {
        console.error('❌ [AI Widget] Failed to initialize connection:', err.message);
        setConnected(false);
        setConnectionError(`Init Error: ${err.message}`);
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
      console.error('[AI Widget] Socket not connected, message not sent');
    }
  }, []);

  return { socket, connected, connectionError, sendMessage };
}
