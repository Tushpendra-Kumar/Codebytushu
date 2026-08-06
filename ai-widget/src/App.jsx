// ─── App.jsx ─────────────────────────────────────────────────────────────────
// Root component: Wraps everything in ChatProvider and renders the
// floating button + chat window. This is what gets injected into
// the existing PHP website via includes/footer.php.
// ─────────────────────────────────────────────────────────────────────────────

import { ChatProvider } from './context/ChatContext'
import ChatButton from './components/ChatButton'
import ChatWindow from './components/ChatWindow'

export default function App() {
  return (
    <ChatProvider>
      {/* Widget container — fixed position, scoped z-index */}
      <div className="cbt-widget-root" id="cbt-widget-root">
        <ChatWindow />
        <ChatButton />
      </div>
    </ChatProvider>
  )
}
