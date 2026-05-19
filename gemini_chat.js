class GeminiChat {
  constructor() {
    this.chatMessages = document.getElementById("chatMessages");
    this.messageInput = document.getElementById("messageInput");
    this.sendBtn = document.getElementById("sendBtn");
    this.loadingOverlay = document.getElementById("loadingOverlay");

    this.initializeEventListeners();
  }

  initializeEventListeners() {
    
    this.messageInput.addEventListener("keypress", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage();
      }
    });

    
    this.messageInput.addEventListener("input", () => {
      this.messageInput.style.height = "auto";
      this.messageInput.style.height =
        Math.min(this.messageInput.scrollHeight, 120) + "px";
    });
  }

  async sendMessage(message = null) {
    const text = message || this.messageInput.value.trim();

    if (!text) return;

    
    if (!message) {
      this.messageInput.value = "";
      this.messageInput.style.height = "auto";
    }

    
    this.addMessage(text, "user");

    
    this.showLoading(true);

    try {
      
      const response = await fetch("gemini_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: "message=" + encodeURIComponent(text),
      });

      const data = await response.json();
      
      
      if (data.reply) {
        this.addMessage(data.reply, "bot");
      } else if (data.error) {
        this.addMessage(
          `I'm sorry, I encountered an error: ${data.error}. Please try again or contact our clinic directly.`,
          "bot",
          true
        );
      }
    } catch (error) {
      console.error("Error:", error);
      this.addMessage(
        "I'm sorry, I'm having trouble connecting right now. Please try again later or contact our clinic directly at (044) 810 2497.",
        "bot",
        true
      );
    } finally {
      this.showLoading(false);
    }
  }

  addMessage(text, sender, isError = false) {
    const messageDiv = document.createElement("div");
    messageDiv.className = `message ${sender}-message${
      isError ? " error-message" : ""
    }`;

    const avatar = document.createElement("div");
    avatar.className = "message-avatar";

    if (sender === "bot") {
      avatar.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 9l-9 9z"/>
                </svg>
            `;
    } else {
      avatar.innerHTML = `
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            `;
    }

    const content = document.createElement("div");
    content.className = "message-content";
    content.innerHTML = this.formatMessage(text);

    messageDiv.appendChild(avatar);
    messageDiv.appendChild(content);

    this.chatMessages.appendChild(messageDiv);
    this.scrollToBottom();
  }

  formatMessage(text) {
    
    return text
      .replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>")
      .replace(/\*(.*?)\*/g, "<em>$1</em>")
      .replace(/\n/g, "<br>")
      .replace(/- (.*?)(?=\n|$)/g, "<li>$1</li>")
      .replace(/(<li>.*<\/li>)/s, "<ul>$1</ul>");
  }



  showLoading(show) {
    this.loadingOverlay.style.display = show ? "flex" : "none";
    this.sendBtn.disabled = show;
    this.messageInput.disabled = show;
  }

  scrollToBottom() {
    this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
  }
}


document.addEventListener("DOMContentLoaded", () => {
  window.geminiChat = new GeminiChat();
});


function sendMessage() {
  window.geminiChat.sendMessage();
}

function sendQuickMessage(message) {
  window.geminiChat.sendMessage(message);
}

function closeChat() {
  if (window.opener) {
    window.close();
  } else {
    window.history.back();
  }
}