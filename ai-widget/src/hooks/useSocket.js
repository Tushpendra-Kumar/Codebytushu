// ─── useSocket Hook (Phase 2 Stub) ───────────────────────────────────────────
// This hook will contain Socket.IO connection logic in Phase 2.
// Currently it returns null connection state as a placeholder.
// ─────────────────────────────────────────────────────────────────────────────

import { useState } from 'react'

export function useSocket() {
  // Phase 2: These will be real socket state values
  const [socket] = useState(null)
  const [connected] = useState(false)

  // Phase 2: This will emit to the Socket.IO server
  const sendMessage = (event, data) => {
    console.warn('[useSocket] Socket.IO not connected yet. Phase 2 will implement this.', event, data)
  }

  return { socket, connected, sendMessage }
}
