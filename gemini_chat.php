<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AI Assistant - Kalinga Medical Clinic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="gemini_chat.css" />
  </head>
  <body>
    <div class="chat-container">
      <div class="chat-header">
        <div class="header-content">
          <div class="clinic-info">
            <img src="logoo.PNG" alt="Clinic Logo" class="header-logo" />
            <div>
              <h1>Kalinga Medical AI Assistant</h1>
              <p>Get instant help with your health questions</p>
            </div>
          </div>
          <button class="close-btn" onclick="closeChat()">&times;</button>
        </div>
      </div>

      <div class="chat-messages" id="chatMessages">
        <div class="message bot-message">
          <div class="message-avatar">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 9l-9 9z"
              />
            </svg>
          </div>
          <div class="message-content">
            <p>
              Hello! I'm your AI assistant for Kalinga Medical Clinic. I can
              help you with:
            </p>
            <ul>
              <li>General health information and guidance</li>
              <li>Information about our clinic services</li>
              <li>Appointment scheduling assistance</li>
              <li>Basic health questions</li>
              <li>Emergency guidance</li>
            </ul>
            <p><strong>How can I help you today?</strong></p>
          </div>
        </div>
      </div>

      <div class="quick-actions">
        <button
          class="quick-btn"
          onclick="sendQuickMessage('What services do you offer?')"
        >
          Our Services
        </button>
        <button
          class="quick-btn"
          onclick="sendQuickMessage('What are your clinic hours?')"
        >
          Clinic Hours
        </button>
        <button
          class="quick-btn"
          onclick="sendQuickMessage('How do I schedule an appointment?')"
        >
          Book Appointment
        </button>
        <button
          class="quick-btn"
          onclick="sendQuickMessage('What should I do in a medical emergency?')"
        >
          Emergency Help
        </button>
      </div>

      <div class="chat-input-container">
        <div class="input-wrapper">
          <input
            type="text"
            id="messageInput"
            placeholder="Type your health question here..."
            maxlength="500"
          />
          <button id="sendBtn" onclick="sendMessage()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
            </svg>
          </button>
        </div>
        <div class="input-footer">
          <small
            >This AI assistant provides general information only. For medical
            emergencies, call 911 or (044) 810 2497.</small
          >
        </div>
      </div>
    </div>

    <div class="loading-overlay" id="loadingOverlay">
      <div class="loading-spinner"></div>
      <p>AI is thinking...</p>
    </div>

    <script src="gemini_chat.js"></script>
  </body>
</html>
