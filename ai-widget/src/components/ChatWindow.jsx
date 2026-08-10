// ─── ChatWindow ──────────────────────────────────────────────────────────────
// Main chat window — header, messages list, typing indicator, input.
// In Phase 2, this component will connect to Socket.IO for real-time messages.
// ─────────────────────────────────────────────────────────────────────────────

import { useEffect, useRef, useState } from 'react'
import { useChat } from '../context/ChatContext'
import { useSocket } from '../hooks/useSocket'
import MessageBubble from './MessageBubble'
import TypingIndicator from './TypingIndicator'
import ChatInput from './ChatInput'

export default function ChatWindow() {
  const { 
    isOpen, messages, isTyping, 
    closeChat, addMessage, updateLastMessage, 
    setIsTyping, clearMessages 
  } = useChat()
  const messagesEndRef = useRef(null)
  
  // Initialize socket connection
  const { socket, connected, connectionError, sendMessage } = useSocket()
  
  // Track if we are currently receiving a stream to know when to append
  const [isStreaming, setIsStreaming] = useState(false)

  // Register Socket.IO listeners
  useEffect(() => {
    if (!socket) return

    const handleTyping = ({ status }) => setIsTyping(status)
    
    const handleChunk = ({ text }) => {
      if (!isStreaming) {
        setIsStreaming(true)
        addMessage('ai', '') // Create empty bubble to start appending
      }
      updateLastMessage(text)
    }

    const handleEnd = () => {
      setIsStreaming(false)
      setIsTyping(false)
    }

    const handleError = ({ message }) => {
      setIsStreaming(false)
      setIsTyping(false)
      addMessage('ai', `❌ **Error:** ${message}`)
    }

    socket.on('chat:typing', handleTyping)
    socket.on('chat:chunk', handleChunk)
    socket.on('chat:end', handleEnd)
    socket.on('chat:error', handleError)

    return () => {
      socket.off('chat:typing', handleTyping)
      socket.off('chat:chunk', handleChunk)
      socket.off('chat:end', handleEnd)
      socket.off('chat:error', handleError)
    }
  }, [socket, isStreaming, addMessage, updateLastMessage, setIsTyping])

  // Auto-scroll to bottom when messages change
  useEffect(() => {
    if (isOpen && messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' })
    }
  }, [messages, isTyping, isOpen])

  // Handle sending a message to Node.js backend
  const handleSend = (text) => {
    addMessage('user', text)
    
    if (!connected) {
      const errorMsg = connectionError ? ` (${connectionError})` : ''
      addMessage('ai', `❌ **Offline:** Cannot connect to the AI Engine.${errorMsg}`)
      return
    }

    // Emit event to Socket.IO backend
    setIsStreaming(false) // reset flag for new request
    sendMessage('chat:send', {
      text,
      url: window.location.pathname, // Contextual routing (Phase 3 prep)
      history: messages.slice(-5) // Send last 5 messages as context
    })
  }

  return (
    <div
      className={`cbt-chat-window ${isOpen ? 'cbt-chat-window--open' : 'cbt-chat-window--closed'}`}
      role="dialog"
      aria-label="CodeByTushu AI Assistant"
      aria-modal="false"
    >
      {/* ── Header ─────────────────────────────────────────────────────────── */}
      <div className="cbt-chat-header">
        <div className="cbt-chat-header__left">
          <div className="cbt-chat-avatar">
            <img src={`${window.cbtBasePath || ''}/ai-widget/dist/bot_avatar.jpg`} alt="AI Avatar" />
            <span className="cbt-chat-avatar__status" aria-label="Online" />
          </div>
          <div className="cbt-chat-header__info">
            <div className="cbt-chat-header__title-row">
              <span className="cbt-chat-header__name">CodeByTushu AI</span>
              <span className="cbt-chat-header__badge">AI</span>
            </div>
            <span className="cbt-chat-header__status">
              {isTyping ? 'Typing...' : 'Always here to help ✨'}
            </span>
          </div>
        </div>
        <div className="cbt-chat-header__actions">
          <button
            className="cbt-header-btn"
            onClick={clearMessages}
            title="Clear conversation"
            aria-label="Clear conversation"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
          </button>
          <button
            className="cbt-header-btn"
            onClick={closeChat}
            title="Close chat"
            aria-label="Close AI Chat"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
            </svg>
          </button>
        </div>
      </div>

      {/* ── Messages List ──────────────────────────────────────────────────── */}
      <div className="cbt-chat-messages" role="log" aria-live="polite">
        {messages.map((msg) => (
          <MessageBubble key={msg.id} message={msg} />
        ))}
        {isTyping && <TypingIndicator />}
        <div ref={messagesEndRef} />
      </div>

      {/* ── Suggested Questions ────────────────────────────────────────────── */}
      {messages.length === 1 && (
        <div className="cbt-suggestions-container">
          <div className="cbt-suggestions-title">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
            </svg>
            Quick Actions
          </div>
          <div className="cbt-suggestions">
            {[
              {
                title: 'What JavaScript courses do you have?',
                subtitle: 'Explore our JavaScript courses',
                color: '#ffc400',
                icon: (
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M16 18l6-6-6-6M8 6l-6 6 6 6"/></svg>
                )
              },
              {
                title: 'Show me DSA LeetCode solutions',
                subtitle: 'Browse DSA solutions by topics',
                color: '#a855f7',
                icon: (
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1m-1.636 6.364l-.707-.707M3 12h1m1.636-6.364l.707.707M12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
                )
              },
              {
                title: 'How do I reset my password?',
                subtitle: 'Get help with your account',
                color: '#10b981',
                icon: (
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                )
              }
            ].map((q, idx) => (
              <button
                key={idx}
                className="cbt-suggestion-card"
                onClick={() => handleSend(q.title)}
              >
                <div className="cbt-suggestion-icon-wrapper" style={{ borderColor: q.color, color: q.color }}>
                  {q.icon}
                </div>
                <div className="cbt-suggestion-text">
                  <span className="cbt-suggestion-title">{q.title}</span>
                  <span className="cbt-suggestion-subtitle">{q.subtitle}</span>
                </div>
                <div className="cbt-suggestion-arrow">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16l4-4-4-4M8 12h8"/></svg>
                </div>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* ── Input ──────────────────────────────────────────────────────────── */}
      <ChatInput onSend={handleSend} />

      {/* ── Footer Branding ────────────────────────────────────────────────── */}
      <div className="cbt-chat-footer-brand">
        <span>Powered by</span>
        <span className="cbt-brand-name"> CodeByTushu AI</span>
      </div>
    </div>
  )
}
