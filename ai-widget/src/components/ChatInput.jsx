// ─── ChatInput ───────────────────────────────────────────────────────────────
// Text input area + Send button at the bottom of the chat window.
// Handles Enter key (Shift+Enter for newline) and character limit.
// ─────────────────────────────────────────────────────────────────────────────

import { useRef, useState } from 'react'
import { useChat } from '../context/ChatContext'
import { SOCKET_URL } from '../hooks/useSocket'

const MAX_CHARS = 500

export default function ChatInput({ onSend }) {
  const { inputValue, setInputValue, isTyping } = useChat()
  const textareaRef = useRef(null)
  const fileInputRef = useRef(null)

  const [attachmentFile, setAttachmentFile] = useState(null)
  const [isUploading, setIsUploading] = useState(false)
  const [uploadError, setUploadError] = useState('')

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  const handleSend = () => {
    const trimmed = inputValue.trim()
    if ((!trimmed && !attachmentFile) || isTyping || isUploading) return
    
    onSend(trimmed, attachmentFile)
    setInputValue('')
    setAttachmentFile(null)
    setUploadError('')
    if (textareaRef.current) {
      textareaRef.current.style.height = 'auto'
    }
  }

  const handleInput = (e) => {
    const val = e.target.value
    if (val.length > MAX_CHARS) return
    setInputValue(val)
    const el = textareaRef.current
    if (el) {
      el.style.height = 'auto'
      el.style.height = Math.min(el.scrollHeight, 120) + 'px'
    }
  }

  const handleFileSelect = (e) => {
    const file = e.target.files[0]
    if (!file) return
    e.target.value = '' // reset

    if (file.size > 5 * 1024 * 1024) {
      setUploadError('File exceeds 5MB limit.')
      return
    }

    setUploadError('')
    setIsUploading(true)

    const reader = new FileReader()
    reader.onload = async (ev) => {
      const base64Data = ev.target.result.split(',')[1]
      const mimeType = file.type || 'text/plain'
      const isImage = mimeType.startsWith('image/')
      
      try {
        const response = await fetch(`${SOCKET_URL}/api/chat/upload`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            fileName: file.name,
            mimeType: mimeType,
            data: base64Data
          })
        })
        
        const textResponse = await response.text()
        let data
        try {
          data = JSON.parse(textResponse)
        } catch (e) {
          throw new Error('Server returned an invalid response.')
        }

        if (!response.ok) {
          throw new Error(data.error || `Upload failed (${response.status})`)
        }
        
        if (data.error) throw new Error(data.error)
        
        setAttachmentFile({
          fileUri: data.fileUri,
          mimeType: data.mimeType,
          name: data.name,
          size: file.size,
          previewUrl: isImage ? ev.target.result : null
        })
      } catch (err) {
        setUploadError(err.message || 'Upload failed')
      } finally {
        setIsUploading(false)
      }
    }
    reader.readAsDataURL(file)
  }

  const clearAttachment = () => {
    setAttachmentFile(null)
    setUploadError('')
  }

  const remaining = MAX_CHARS - inputValue.length
  const isEmpty = !inputValue.trim() && !attachmentFile
  const isDisabled = isEmpty || isTyping || isUploading

  return (
    <div className="cbt-chat-input-area">
      {/* File Preview Area */}
      {(attachmentFile || isUploading || uploadError) && (
        <div className="cbt-attachment-preview">
          {isUploading ? (
            <div className="cbt-attachment-item uploading">
              <span className="cbt-spinner"></span> Uploading...
            </div>
          ) : uploadError ? (
            <div className="cbt-attachment-item error">
              <span>⚠️ {uploadError}</span>
              <button onClick={() => setUploadError('')}>✕</button>
            </div>
          ) : attachmentFile ? (
            <div className={`cbt-attachment-item ${attachmentFile.previewUrl ? 'has-image-preview' : ''}`}>
              {attachmentFile.previewUrl ? (
                <img src={attachmentFile.previewUrl} alt="preview" className="cbt-attachment-img-thumb" />
              ) : (
                <span className="cbt-attachment-icon">📎</span>
              )}
              <span className="cbt-attachment-name">{attachmentFile.name}</span>
              <button className="cbt-attachment-remove" onClick={clearAttachment} title="Remove file">✕</button>
            </div>
          ) : null}
        </div>
      )}

      <div className="cbt-chat-input-wrapper">
        <button 
          className="cbt-chat-attach-btn" 
          onClick={() => fileInputRef.current?.click()}
          disabled={isTyping || isUploading || attachmentFile}
          title="Attach file (PDF, Image, Code)"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
        </button>
        <input 
          type="file" 
          ref={fileInputRef} 
          style={{ display: 'none' }}
          onChange={handleFileSelect}
          accept="image/*,.pdf,.doc,.docx,.txt,.java,.js,.jsx,.ts,.tsx,.py,.cpp,.c,.cs,.php,.html,.css,.sql,.json,.xml,.md"
        />

        <textarea
          ref={textareaRef}
          className="cbt-chat-textarea"
          value={inputValue}
          onChange={handleInput}
          onKeyDown={handleKeyDown}
          placeholder="Type your question here..."
          rows={1}
          disabled={isTyping || isUploading}
          aria-label="Chat message input"
          maxLength={MAX_CHARS}
        />
        
        <button
          className={`cbt-chat-send-btn ${isDisabled ? 'cbt-chat-send-btn--disabled' : ''}`}
          onClick={handleSend}
          disabled={isDisabled}
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
