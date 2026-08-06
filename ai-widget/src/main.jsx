import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import App from './App.jsx'

// Mount to a dedicated div injected by the lazy-load script
// This ensures zero conflict with the existing PHP website's #root or any other element
const mountPoint = document.getElementById('cbt-ai-widget')
if (mountPoint) {
  createRoot(mountPoint).render(
    <StrictMode>
      <App />
    </StrictMode>,
  )
}
