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
            <CopyButton textToCopy={code.trim()} label="📋 Copy Code" successLabel="✓ Copied" className="cbt-copy-code-btn" />
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

export default function MessageBubble({ message }) {
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
            <CopyButton textToCopy={message.text} label="📋 Copy Answer" successLabel="✓ Copied" className="cbt-copy-answer-btn" />
          )}
        </div>
      </div>
    </div>
  )
}
