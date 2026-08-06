// ─── ChatWindow ──────────────────────────────────────────────────────────────
// Main chat window — header, messages list, typing indicator, input.
// In Phase 2, this component will connect to Socket.IO for real-time messages.
// ─────────────────────────────────────────────────────────────────────────────

import { useEffect, useRef } from 'react'
import { useChat } from '../context/ChatContext'
import MessageBubble from './MessageBubble'
import TypingIndicator from './TypingIndicator'
import ChatInput from './ChatInput'

export default function ChatWindow() {
  const { isOpen, messages, isTyping, closeChat, addMessage, setIsTyping, clearMessages } = useChat()
  const messagesEndRef = useRef(null)

  // Auto-scroll to bottom when messages change
  useEffect(() => {
    if (isOpen && messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' })
    }
  }, [messages, isTyping, isOpen])

  // Handle sending a message (Phase 1: simulate AI response)
  const handleSend = async (text) => {
    addMessage('user', text)
    setIsTyping(true)

    // ─── Phase 1: Simulated AI Response ──────────────────────────────────────
    // In Phase 2, this block will be replaced by a Socket.IO emit to the
    // Node.js backend, which will call the Gemini API and stream the response.
    // ─────────────────────────────────────────────────────────────────────────
    await new Promise((resolve) => setTimeout(resolve, 1200 + Math.random() * 800))

    const simulatedResponses = [
      "I'm **CodeByTushu AI** 🤖 — I'll be fully connected to the AI backend in Phase 2! For now, check out our **[JavaScript Course](/courses/)** or browse **[LeetCode solutions](/Leetcode/)**.",
      "Great question! I'm currently in **UI-only mode** (Phase 1). Once the Node.js backend is set up in Phase 2, I'll give you accurate AI-powered answers. Meanwhile, explore our **[Blog articles](/blogs/)**!",
      "I can see you're curious! My full AI capabilities will be live soon. In the meantime, visit our **[Store](/store/)** or our **[Courses](/courses/)** section for resources!",
    ]
    const response = simulatedResponses[Math.floor(Math.random() * simulatedResponses.length)]
    setIsTyping(false)
    addMessage('ai', response)
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
              <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
              <circle cx="12" cy="17" r="1" fill="currentColor"/>
            </svg>
            <span className="cbt-chat-avatar__status" aria-label="Online" />
          </div>
          <div className="cbt-chat-header__info">
            <span className="cbt-chat-header__name">CodeByTushu AI</span>
            <span className="cbt-chat-header__status">
              {isTyping ? 'Typing...' : 'Always here to help'}
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
        <div className="cbt-suggestions">
          {[
            'What JavaScript courses do you have?',
            'Show me DSA LeetCode solutions',
            'How do I reset my password?',
          ].map((q) => (
            <button
              key={q}
              className="cbt-suggestion-chip"
              onClick={() => handleSend(q)}
            >
              {q}
            </button>
          ))}
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
