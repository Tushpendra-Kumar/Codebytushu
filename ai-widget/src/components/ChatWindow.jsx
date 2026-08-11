// ─── ChatWindow ──────────────────────────────────────────────────────────────
// Main chat window — header, messages list, typing indicator, input.
// Includes Phase 3 features: Chat History Panel and Clear Chat Modal.
// ─────────────────────────────────────────────────────────────────────────────

import { useEffect, useRef, useState } from 'react'
import { useChat } from '../context/ChatContext'
import { useSocket } from '../hooks/useSocket'
import MessageBubble from './MessageBubble'
import TypingIndicator from './TypingIndicator'
import ChatInput from './ChatInput'

export default function ChatWindow() {
  const { 
    isOpen, messages, isTyping, sessions, activeSessionId, userId,
    closeChat, addMessage, updateLastMessage, setFeedback,
    setIsTyping, clearActiveSession, createNewSession, loadSession, deleteSession
  } = useChat()
  const messagesEndRef = useRef(null)
  
  // Local UI State
  const [isHistoryOpen, setIsHistoryOpen] = useState(false)
  const [isClearModalOpen, setIsClearModalOpen] = useState(false)

  // Initialize socket connection
  const { socket, connected, connectionError, sendMessage } = useSocket()
  
  // Track if we are currently receiving a stream to know when to append
  const [isStreaming, setIsStreaming] = useState(false)

  // Register Socket.IO listeners
  useEffect(() => {
    if (!socket) return

    const handleTyping = ({ status }) => setIsTyping(status)
    
    const handleChunk = ({ text }) => {
      if (!isStreaming) setIsStreaming(true)
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
    if (isOpen && messagesEndRef.current && !isHistoryOpen) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' })
    }
  }, [messages, isTyping, isOpen, isHistoryOpen])

  // Handle sending a message to Node.js backend
  const handleSend = (text, attachment) => {
    addMessage('user', text, attachment)
    
    if (!connected) {
      const errorMsg = connectionError ? ` (${connectionError})` : ''
      addMessage('ai', `❌ **Offline:** Cannot connect to the AI Engine.${errorMsg}`)
      return
    }

    // Emit event to Socket.IO backend
    setIsStreaming(false) // reset flag for new request
    sendMessage('chat:send', {
      text,
      attachment,
      url: window.location.pathname, // Contextual routing (Phase 3 prep)
      history: messages.slice(0, -1) // Excluding the message just added locally
    })
  }

  const confirmClearChat = () => {
    clearActiveSession()
    setIsClearModalOpen(false)
  }

  const handleFeedback = (messageId, type) => {
    setFeedback(messageId, type)
    if (connected) {
      sendMessage('chat:feedback', {
        userId,
        sessionId: activeSessionId,
        messageId,
        feedbackType: type
      })
    }
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
            className={`cbt-header-btn ${isHistoryOpen ? 'active' : ''}`}
            onClick={() => setIsHistoryOpen(!isHistoryOpen)}
            title="Chat History"
            aria-label="Chat History"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
            </svg>
          </button>
          <button
            className="cbt-header-btn"
            onClick={() => setIsClearModalOpen(true)}
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

      {/* ── Clear Chat Modal ───────────────────────────────────────────────── */}
      {isClearModalOpen && (
        <div className="cbt-modal-overlay">
          <div className="cbt-modal-content">
            <h4>Clear Chat?</h4>
            <p>Are you sure you want to clear this conversation? This action will remove the current chat messages.</p>
            <div className="cbt-modal-actions">
              <button className="cbt-btn-cancel" onClick={() => setIsClearModalOpen(false)}>Cancel</button>
              <button className="cbt-btn-confirm" onClick={confirmClearChat}>Clear Chat</button>
            </div>
          </div>
        </div>
      )}

      {/* ── Chat History Panel ─────────────────────────────────────────────── */}
      {isHistoryOpen ? (
        <div className="cbt-chat-history-panel">
          <div className="cbt-history-header">
            <h4>Chat History</h4>
            <button className="cbt-new-chat-btn" onClick={() => { createNewSession(); setIsHistoryOpen(false); }}>
              + New Chat
            </button>
          </div>
          <div className="cbt-history-list">
            {sessions.map(session => (
              <div 
                key={session.id} 
                className={`cbt-history-item ${session.id === activeSessionId ? 'active' : ''}`}
                onClick={() => { loadSession(session.id); setIsHistoryOpen(false); }}
              >
                <div className="cbt-history-item-title">{session.title}</div>
                <button 
                  className="cbt-history-delete-btn"
                  onClick={(e) => { e.stopPropagation(); deleteSession(session.id); }}
                  title="Delete chat"
                >
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                  </svg>
                </button>
              </div>
            ))}
            {sessions.length === 0 && <div className="cbt-history-empty">No previous chats.</div>}
          </div>
        </div>
      ) : (
        <>
          {/* ── Messages List ──────────────────────────────────────────────────── */}
          <div className="cbt-chat-messages" role="log" aria-live="polite">
            {messages.map((msg) => (
              <MessageBubble key={msg.id} message={msg} onFeedback={handleFeedback} />
            ))}
            {isTyping && <TypingIndicator />}
            <div ref={messagesEndRef} />
          </div>

          {/* ── Input Area ─────────────────────────────────────────────────────── */}
          <ChatInput onSend={handleSend} isTyping={isTyping} />
        </>
      )}
      
      {/* ── Footer Branding ────────────────────────────────────────────────── */}
      <div className="cbt-chat-footer-brand">
        <span>Powered by</span>
        <span className="cbt-brand-name"> CodeByTushu AI 💛</span>
      </div>
    </div>
  )
}
