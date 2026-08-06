import { createContext, useContext, useState, useCallback } from 'react'

// ─── Chat Context ────────────────────────────────────────────────────────────
// Central state management for the AI chat widget.
// Phase 2 will add socket connection state here.
// ─────────────────────────────────────────────────────────────────────────────

const ChatContext = createContext(null)

export function ChatProvider({ children }) {
  const [isOpen, setIsOpen] = useState(false)
  const [messages, setMessages] = useState([
    {
      id: 'welcome',
      role: 'ai',
      text: "Hi! I'm **CodeByTushu AI** 👋\n\nAsk me anything about JavaScript, DSA, React, DevOps, or anything on this website. I'll guide you to the right resource!",
      timestamp: new Date(),
    },
  ])
  const [isTyping, setIsTyping] = useState(false)
  const [inputValue, setInputValue] = useState('')

  const openChat = useCallback(() => setIsOpen(true), [])
  const closeChat = useCallback(() => setIsOpen(false), [])
  const toggleChat = useCallback(() => setIsOpen((prev) => !prev), [])

  // Add a new message to the chat
  const addMessage = useCallback((role, text) => {
    const msg = {
      id: `${role}-${Date.now()}`,
      role,
      text,
      timestamp: new Date(),
    }
    setMessages((prev) => [...prev, msg])
    return msg.id
  }, [])

  // Clear all messages (except welcome)
  const clearMessages = useCallback(() => {
    setMessages([{
      id: 'welcome',
      role: 'ai',
      text: "Hi! I'm **CodeByTushu AI** 👋\n\nAsk me anything about JavaScript, DSA, React, DevOps, or anything on this website!",
      timestamp: new Date(),
    }])
  }, [])

  return (
    <ChatContext.Provider
      value={{
        isOpen,
        messages,
        isTyping,
        inputValue,
        setIsTyping,
        setInputValue,
        openChat,
        closeChat,
        toggleChat,
        addMessage,
        clearMessages,
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
