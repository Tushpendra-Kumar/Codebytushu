// ─── MessageBubble ───────────────────────────────────────────────────────────
// Renders a single chat message — either from the user or from the AI.
// Supports basic markdown rendering (bold, inline code, line breaks, links).
// ─────────────────────────────────────────────────────────────────────────────

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

export default function MessageBubble({ message }) {
  const isUser = message.role === 'user'
  const formattedText = formatText(message.text)

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
            <div className="cbt-welcome-text-area" dangerouslySetInnerHTML={{ __html: formattedText }} />
            <img src={`${window.cbtBasePath || ''}/ai-widget/dist/welcome_robot.jpg`} alt="Welcome AI Robot" className="cbt-welcome-img" />
          </div>
        ) : (
          <div
            className={`cbt-message__bubble ${isUser ? 'cbt-message__bubble--user' : 'cbt-message__bubble--ai'}`}
            dangerouslySetInnerHTML={{ __html: formattedText }}
          />
        )}
        <span className={`cbt-message__time ${isUser ? 'cbt-message__time--user' : ''}`}>
          {timeStr}
        </span>
      </div>
    </div>
  )
}
