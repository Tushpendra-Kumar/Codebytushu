// ─── ChatButton ──────────────────────────────────────────────────────────────
// Floating Action Button (FAB) anchored to the bottom-right corner.
// Shows a chat icon when closed, an X when open.
// ─────────────────────────────────────────────────────────────────────────────

import { useChat } from '../context/ChatContext'

export default function ChatButton() {
  const { isOpen, toggleChat } = useChat()

  return (
    <button
      className={`cbt-chat-fab ${isOpen ? 'cbt-chat-fab--open' : ''}`}
      onClick={toggleChat}
      aria-label={isOpen ? 'Close AI Chat' : 'Open AI Chat'}
      title={isOpen ? 'Close AI Chat' : 'Ask CodeByTushu AI'}
    >
      {/* Chat Icon */}
      <span className={`cbt-fab-icon cbt-fab-icon--chat ${isOpen ? 'cbt-fab-icon--hidden' : ''}`}>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <circle cx="9" cy="10" r="1" fill="currentColor"/>
          <circle cx="12" cy="10" r="1" fill="currentColor"/>
          <circle cx="15" cy="10" r="1" fill="currentColor"/>
        </svg>
      </span>

      {/* Close (X) Icon */}
      <span className={`cbt-fab-icon cbt-fab-icon--close ${!isOpen ? 'cbt-fab-icon--hidden' : ''}`}>
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round"/>
        </svg>
      </span>

      {/* Ping animation — visible only when chat is closed */}
      {!isOpen && <span className="cbt-fab-ping" />}
    </button>
  )
}
