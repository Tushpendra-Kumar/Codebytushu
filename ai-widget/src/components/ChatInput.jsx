// ─── ChatInput ───────────────────────────────────────────────────────────────
// Text input area + Send button at the bottom of the chat window.
// Handles Enter key (Shift+Enter for newline) and character limit.
// ─────────────────────────────────────────────────────────────────────────────

import { useRef } from 'react'
import { useChat } from '../context/ChatContext'

const MAX_CHARS = 500

export default function ChatInput({ onSend }) {
  const { inputValue, setInputValue, isTyping } = useChat()
  const textareaRef = useRef(null)

  const handleKeyDown = (e) => {
    // Send on Enter (not Shift+Enter)
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  const handleSend = () => {
    const trimmed = inputValue.trim()
    if (!trimmed || isTyping) return
    onSend(trimmed)
    setInputValue('')
    // Reset textarea height
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto'
    }
  }

  const handleInput = (e) => {
    const val = e.target.value
    if (val.length > MAX_CHARS) return
    setInputValue(val)
    // Auto-grow textarea
    const el = textareaRef.current
    if (el) {
      el.style.height = 'auto'
      el.style.height = Math.min(el.scrollHeight, 120) + 'px'
    }
  }

  const remaining = MAX_CHARS - inputValue.length
  const isEmpty = !inputValue.trim()

  return (
    <div className="cbt-chat-input-area">
      <div className="cbt-chat-input-wrapper">
        <div className="cbt-input-sparkle">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--cbt-gold)" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
        </div>
        <textarea
          ref={textareaRef}
          className="cbt-chat-textarea"
          value={inputValue}
          onChange={handleInput}
          onKeyDown={handleKeyDown}
          placeholder="Ask me anything..."
          rows={1}
          disabled={isTyping}
          aria-label="Chat message input"
          maxLength={MAX_CHARS}
        />
        <button
          className={`cbt-chat-send-btn ${isEmpty || isTyping ? 'cbt-chat-send-btn--disabled' : ''}`}
          onClick={handleSend}
          disabled={isEmpty || isTyping}
          aria-label="Send message"
          title="Send (Enter)"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
            <path d="M3.4 20.4l17.45-7.48a1 1 0 000-1.84L3.4 3.6a.993.993 0 00-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.06-.87.49-.87.99l.01 4.61c0 .71.73 1.2 1.39.92z"/>
          </svg>
        </button>
      </div>
      <div className="cbt-chat-input-footer">
        <span className="cbt-char-count" style={{ color: remaining < 50 ? '#ff6b6b' : undefined }}>
          {remaining < 100 ? `${remaining} left` : ''}
        </span>
        <span className="cbt-input-hint">Enter to send · Shift+Enter for newline</span>
      </div>
    </div>
  )
}
