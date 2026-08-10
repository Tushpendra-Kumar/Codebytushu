// ─── TypingIndicator ─────────────────────────────────────────────────────────
// Animated "AI is typing..." indicator — three bouncing dots.
// ─────────────────────────────────────────────────────────────────────────────

export default function TypingIndicator() {
  return (
    <div className="cbt-message cbt-message--ai">
      <div className="cbt-message__avatar">
        <img src={`${window.cbtBasePath || ''}/ai-widget/dist/bot_avatar.jpg`} alt="AI Avatar" />
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
