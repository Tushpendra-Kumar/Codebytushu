// ─── MessageBubble ───────────────────────────────────────────────────────────
// Renders a single chat message — either from the user or from the AI.
// Supports basic markdown rendering and includes Copy Code / Copy Answer buttons.
// ─────────────────────────────────────────────────────────────────────────────

import { useState } from 'react'

function CopyButton({ textToCopy, label = "Copy", successLabel = "Copied ✓", className = "cbt-copy-btn" }) {
  const [copied, setCopied] = useState(false)
  
  const handleCopy = async () => {
    try {
      await navigator.clipboard.writeText(textToCopy)
      setCopied(true)
      setTimeout(() => setCopied(false), 2000)
    } catch (err) {
      console.error('Failed to copy: ', err)
    }
  }

  return (
    <button 
      className={className} 
      onClick={handleCopy}
      title="Copy to clipboard"
    >
      {copied ? successLabel : label}
    </button>
  )
}

const CopyIcon = (
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
  </svg>
)

const CheckIcon = (
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
    <polyline points="20 6 9 17 4 12"></polyline>
  </svg>
)

function formatText(text) {
  // Escape HTML to prevent XSS
  const escaped = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  return escaped
    // Bold: **text**
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    // Inline code: `code`
    .replace(/`([^`]+)`/g, '<code class="cbt-inline-code">$1</code>')
    // Links: [text](url)
    .replace(/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/g,
      '<a href="$2" target="_blank" rel="noopener noreferrer" class="cbt-msg-link">$1</a>')
    // Newlines → <br>
    .replace(/\n/g, '<br>')
}

function renderMarkdown(text) {
  // Split by ```lang\ncode\n```
  // Capturing groups: 1 = lang, 2 = code
  const parts = text.split(/```(\w*)\r?\n([\s\S]*?)```/g)
  const elements = []
  
  for (let i = 0; i < parts.length; i += 3) {
    const normalText = parts[i]
    if (normalText) {
      elements.push(
        <div 
          key={`text-${i}`} 
          className="cbt-markdown-text"
          dangerouslySetInnerHTML={{ __html: formatText(normalText) }} 
        />
      )
    }
    
    // If there is a matching code block
    if (i + 2 < parts.length) {
      const lang = parts[i + 1]
      const code = parts[i + 2]
      elements.push(
        <div key={`code-${i}`} className="cbt-code-block-container">
          <div className="cbt-code-block-header">
            <span className="cbt-code-lang">{lang || 'code'}</span>
            <CopyButton textToCopy={code.trim()} label={CopyIcon} successLabel={CheckIcon} className="cbt-copy-code-btn" />
          </div>
          <pre className="cbt-code-block">
            <code>{code}</code>
          </pre>
        </div>
      )
    }
  }
  
  return elements
}

export default function MessageBubble({ message, onFeedback }) {
  const isUser = message.role === 'user'
  
  const timeStr = message.timestamp
    ? new Date(message.timestamp).toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
    : ''

  return (
    <div className={`cbt-message ${isUser ? 'cbt-message--user' : 'cbt-message--ai'}`}>
      {/* AI Avatar */}
      {!isUser && (
        <div className="cbt-message__avatar">
          <img src={`${window.cbtBasePath || ''}/ai-widget/dist/bot_avatar.jpg`} alt="AI Avatar" />
        </div>
      )}

      {/* Message bubble */}
      <div className="cbt-message__content">
        {message.id === 'welcome' ? (
          <div className="cbt-message__bubble--welcome">
            <div className="cbt-welcome-grid-bg"></div>
            <div className="cbt-welcome-content-row">
              <div className="cbt-welcome-text-area" dangerouslySetInnerHTML={{ __html: formatText(message.text) }} />
              <img src={`${window.cbtBasePath || ''}/ai-widget/dist/welcome_robot.jpg`} alt="Welcome AI Robot" className="cbt-welcome-img" />
            </div>
          </div>
        ) : (
          <div className={`cbt-message__bubble ${isUser ? 'cbt-message__bubble--user' : 'cbt-message__bubble--ai'}`}>
            {renderMarkdown(message.text)}
          </div>
        )}
        
        <div className="cbt-message__footer">
          <span className={`cbt-message__time ${isUser ? 'cbt-message__time--user' : ''}`}>
            {timeStr}
          </span>
          {!isUser && message.id !== 'welcome' && (
            <div className="cbt-message__actions">
              <CopyButton textToCopy={message.text} label={CopyIcon} successLabel={CheckIcon} className="cbt-feedback-btn" />
              <div className="cbt-feedback-group">
                <button 
                  className={`cbt-feedback-btn ${message.feedback === 'like' ? 'active' : ''}`}
                  onClick={() => onFeedback && onFeedback(message.id, 'like')}
                  title="Helpful response"
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path>
                  </svg>
                </button>
                <button 
                  className={`cbt-feedback-btn ${message.feedback === 'dislike' ? 'active' : ''}`}
                  onClick={() => onFeedback && onFeedback(message.id, 'dislike')}
                  title="Unhelpful response"
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M10 15v4a3 3 0 0 0 3 3l4-9V2H5.72a2 2 0 0 0-2 1.7l-1.38 9a2 2 0 0 0 2 2.3zm7-13h2.67A2.31 2.31 0 0 1 22 4v7a2.31 2.31 0 0 1-2.33 2H17"></path>
                  </svg>
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}
