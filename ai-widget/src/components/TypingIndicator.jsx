// ─── TypingIndicator ─────────────────────────────────────────────────────────
// Animated "AI is typing..." indicator — three bouncing dots.
// ─────────────────────────────────────────────────────────────────────────────

export default function TypingIndicator() {
  return (
    <div className="cbt-message cbt-message--ai">
      <div className="cbt-message__avatar">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
          <circle cx="12" cy="17" r="1" fill="currentColor"/>
        </svg>
      </div>
      <div className="cbt-message__bubble cbt-message__bubble--ai">
        <div className="cbt-typing-dots">
          <span className="cbt-dot" style={{ animationDelay: '0ms' }} />
          <span className="cbt-dot" style={{ animationDelay: '160ms' }} />
          <span className="cbt-dot" style={{ animationDelay: '320ms' }} />
        </div>
      </div>
    </div>
  )
}
