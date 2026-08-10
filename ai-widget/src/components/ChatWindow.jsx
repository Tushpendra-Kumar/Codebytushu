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


      {/* ── Input Area ─────────────────────────────────────────────────────── */}
      <ChatInput onSend={handleSend} isTyping={isTyping} />
      
      {/* ── Footer Branding ────────────────────────────────────────────────── */}
      <div className="cbt-chat-footer-brand">
        <span>Powered by</span>
        <span className="cbt-brand-name"> CodeByTushu AI 💛</span>
      </div>
    </div>
  )
}
