import { createContext, useContext, useState, useCallback, useEffect } from 'react'

// ─── Chat Context ────────────────────────────────────────────────────────────
// Central state management for the AI chat widget.
// Now includes Chat History and Persistence.
// ─────────────────────────────────────────────────────────────────────────────

const ChatContext = createContext(null)

const WELCOME_MESSAGE = {
  id: 'welcome',
  role: 'ai',
  text: "Hi! I'm **CodeByTushu AI** 👋\n\nAsk me anything about JavaScript, DSA, React, DevOps, or anything on this website. I'll guide you to the right resource!",
  timestamp: new Date().toISOString(),
}

export function ChatProvider({ children }) {
  const [isOpen, setIsOpen] = useState(false)
  const [isTyping, setIsTyping] = useState(false)
  const [inputValue, setInputValue] = useState('')

  // State for Chat History
  const [storageKey, setStorageKey] = useState('cbt_ai_chats_guest')
  const [sessions, setSessions] = useState([])
  const [activeSessionId, setActiveSessionId] = useState(null)
  const [isInitialized, setIsInitialized] = useState(false)

  // 1. Determine Storage Key based on auth status
  useEffect(() => {
    fetch('/api/auth/status.php')
      .then(res => res.json())
      .then(data => {
        if (data.logged_in && data.user && data.user.id) {
          setStorageKey(`cbt_ai_chats_user_${data.user.id}`)
        }
      })
      .catch(() => {
        // Fallback silently
      })
      .finally(() => {
        setIsInitialized(true)
      })
  }, [])

  // 2. Load sessions from localStorage
  useEffect(() => {
    if (!isInitialized) return
    const stored = localStorage.getItem(storageKey)
    if (stored) {
      try {
        const parsed = JSON.parse(stored)
        setSessions(parsed)
        if (parsed.length > 0) {
          setActiveSessionId(parsed[0].id)
        }
      } catch (e) {
        console.error("Failed to parse chat sessions", e)
      }
    }
  }, [isInitialized, storageKey])

  // 3. Save sessions to localStorage whenever they change
  useEffect(() => {
    if (isInitialized) {
      if (sessions.length > 0) {
        localStorage.setItem(storageKey, JSON.stringify(sessions))
      } else {
        localStorage.removeItem(storageKey)
      }
    }
  }, [sessions, storageKey, isInitialized])

  // Create a new session
  const createNewSession = useCallback(() => {
    const newSession = {
      id: `session-${Date.now()}`,
      title: 'New Chat',
      updatedAt: Date.now(),
      messages: [{ ...WELCOME_MESSAGE, timestamp: new Date().toISOString() }]
    }
    setSessions(prev => [newSession, ...prev])
    setActiveSessionId(newSession.id)
  }, [])

  // Ensure there's always at least one session once initialized
  useEffect(() => {
    if (isInitialized && sessions.length === 0) {
      createNewSession()
    }
  }, [isInitialized, sessions.length, createNewSession])

  const loadSession = useCallback((id) => {
    setActiveSessionId(id)
  }, [])

  const deleteSession = useCallback((id) => {
    setSessions(prev => {
      const filtered = prev.filter(s => s.id !== id)
      if (activeSessionId === id) {
        if (filtered.length > 0) {
          setActiveSessionId(filtered[0].id)
        } else {
          setActiveSessionId(null) // Handled by useEffect above to create new
        }
      }
      return filtered
    })
  }, [activeSessionId])

  const clearActiveSession = useCallback(() => {
    setSessions(prev => prev.map(s => {
      if (s.id === activeSessionId) {
        return {
          ...s,
          updatedAt: Date.now(),
          messages: [{ ...WELCOME_MESSAGE, timestamp: new Date().toISOString() }],
          title: 'New Chat'
        }
      }
      return s
    }))
  }, [activeSessionId])

  const openChat = useCallback(() => setIsOpen(true), [])
  const closeChat = useCallback(() => setIsOpen(false), [])
  const toggleChat = useCallback(() => setIsOpen((prev) => !prev), [])

  // Add a new message to the active session
  const addMessage = useCallback((role, text) => {
    const msgId = `${role}-${Date.now()}`
    setSessions(prev => {
      const activeIndex = prev.findIndex(s => s.id === activeSessionId)
      if (activeIndex === -1) return prev

      const activeSession = prev[activeIndex]
      let newTitle = activeSession.title

      // Auto-generate title from first user message
      if (role === 'user' && activeSession.title === 'New Chat') {
        newTitle = text.slice(0, 30) + (text.length > 30 ? '...' : '')
      }

      const updatedSession = {
        ...activeSession,
        title: newTitle,
        updatedAt: Date.now(),
        messages: [...activeSession.messages, { id: msgId, role, text, timestamp: new Date().toISOString() }]
      }

      // Move updated session to top
      const others = prev.filter(s => s.id !== activeSessionId)
      return [updatedSession, ...others]
    })
    return msgId
  }, [activeSessionId])

  // Update the last message (used for streaming chunks)
  const updateLastMessage = useCallback((chunkText) => {
    setSessions(prev => prev.map(s => {
      if (s.id === activeSessionId) {
        const newMessages = [...s.messages]
        const lastIndex = newMessages.length - 1
        if (lastIndex >= 0 && newMessages[lastIndex].role === 'ai') {
          newMessages[lastIndex] = {
            ...newMessages[lastIndex],
            text: newMessages[lastIndex].text + chunkText,
          }
        }
        return { ...s, messages: newMessages, updatedAt: Date.now() }
      }
      return s
    }))
  }, [activeSessionId])

  // Update feedback (like/dislike) for a specific message
  const setFeedback = useCallback((messageId, feedbackType) => {
    setSessions(prev => prev.map(s => {
      if (s.id === activeSessionId) {
        return {
          ...s,
          messages: s.messages.map(msg => 
            msg.id === messageId ? { ...msg, feedback: feedbackType } : msg
          ),
          updatedAt: Date.now()
        }
      }
      return s
    }))
  }, [activeSessionId])

  // Derive current messages
  const activeSession = sessions.find(s => s.id === activeSessionId)
  const messages = activeSession ? activeSession.messages : []
  
  // Get user info from storageKey if authenticated (e.g. "cbt_ai_chats_user_123" -> "123")
  const userId = storageKey.startsWith('cbt_ai_chats_user_') ? storageKey.replace('cbt_ai_chats_user_', '') : null

  return (
    <ChatContext.Provider
      value={{
        isOpen,
        messages,
        isTyping,
        inputValue,
        sessions,
        activeSessionId,
        isInitialized,
        userId,
        setIsTyping,
        setInputValue,
        openChat,
        closeChat,
        toggleChat,
        addMessage,
        updateLastMessage,
        clearActiveSession,
        createNewSession,
        loadSession,
        deleteSession,
        setFeedback,
      }}
    >
      {children}
    </ChatContext.Provider>
  )
}

// Custom hook for consuming context
export function useChat() {
  const context = useContext(ChatContext)
  if (!context) throw new Error('useChat must be used within a ChatProvider')
  return context
}
