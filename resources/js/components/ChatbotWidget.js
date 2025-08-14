/**
 * WebSocket-powered Chatbot Widget
 * 
 * Features:
 * - Real-time message streaming via WebSockets
 * - Typing indicators
 * - Modern responsive UI
 * - Multi-language support (Arabic/English)
 * - Voice input support
 * - File attachment support
 * - Message history
 */

class ChatbotWidget {
    constructor(options = {}) {
        this.options = {
            chatbotId: options.chatbotId || 1,
            containerId: options.containerId || 'chatbot-container',
            theme: options.theme || 'modern',
            language: options.language || 'ar',
            position: options.position || 'bottom-right',
            autoOpen: options.autoOpen || false,
            enableVoice: options.enableVoice || true,
            enableFileUpload: options.enableFileUpload || true,
            maxHeight: options.maxHeight || '500px',
            maxWidth: options.maxWidth || '400px',
            ...options
        };

        this.isOpen = false;
        this.isTyping = false;
        this.messages = [];
        this.currentConversationId = null;
        this.sessionId = this.generateSessionId();

        this.init();
    }

    generateSessionId() {
        return 'session-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
    }

    init() {
        this.createStyles();
        this.createWidget();
        this.bindEvents();
        this.initWebSocket();
        
        if (this.options.autoOpen) {
            this.open();
        }
    }

    createStyles() {
        const styles = `
            .chatbot-widget {
                position: fixed;
                z-index: 10000;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                direction: ${this.options.language === 'ar' ? 'rtl' : 'ltr'};
            }

            .chatbot-widget.bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .chatbot-widget.bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .chatbot-toggle {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border: none;
                cursor: pointer;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 24px;
            }

            .chatbot-toggle:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 25px rgba(0,0,0,0.2);
            }

            .chatbot-container {
                position: absolute;
                bottom: 80px;
                right: 0;
                width: ${this.options.maxWidth};
                max-height: ${this.options.maxHeight};
                background: white;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                overflow: hidden;
                transform: translateY(20px);
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
                display: flex;
                flex-direction: column;
            }

            .chatbot-container.open {
                transform: translateY(0);
                opacity: 1;
                visibility: visible;
            }

            .chatbot-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 16px 20px;
                display: flex;
                align-items: center;
                justify-content: between;
                gap: 12px;
            }

            .chatbot-header .avatar {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: rgba(255,255,255,0.2);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 16px;
            }

            .chatbot-header .info {
                flex: 1;
            }

            .chatbot-header .title {
                font-weight: 600;
                font-size: 14px;
                margin: 0;
            }

            .chatbot-header .status {
                font-size: 12px;
                opacity: 0.8;
                margin: 0;
                margin-top: 2px;
            }

            .chatbot-header .close-btn {
                background: none;
                border: none;
                color: white;
                font-size: 18px;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.2s;
            }

            .chatbot-header .close-btn:hover {
                opacity: 1;
            }

            .chatbot-messages {
                flex: 1;
                overflow-y: auto;
                padding: 20px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                max-height: 350px;
                background: #fafafa;
            }

            .message {
                max-width: 80%;
                word-wrap: break-word;
                animation: messageSlideIn 0.3s ease;
            }

            @keyframes messageSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .message.user {
                align-self: flex-end;
            }

            .message.bot {
                align-self: flex-start;
            }

            .message-bubble {
                padding: 12px 16px;
                border-radius: 18px;
                font-size: 14px;
                line-height: 1.4;
                position: relative;
            }

            .message.user .message-bubble {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-bottom-right-radius: 6px;
            }

            .message.bot .message-bubble {
                background: white;
                color: #333;
                border: 1px solid #e5e5e5;
                border-bottom-left-radius: 6px;
            }

            .message-time {
                font-size: 11px;
                opacity: 0.6;
                margin-top: 4px;
                text-align: ${this.options.language === 'ar' ? 'right' : 'left'};
            }

            .typing-indicator {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 16px;
                background: white;
                border-radius: 18px;
                border-bottom-left-radius: 6px;
                border: 1px solid #e5e5e5;
                max-width: 80px;
                margin-bottom: 8px;
            }

            .typing-indicator.hidden {
                display: none;
            }

            .typing-dots {
                display: flex;
                gap: 3px;
            }

            .typing-dots span {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: #ccc;
                animation: typingBounce 1.4s infinite both;
            }

            .typing-dots span:nth-child(1) { animation-delay: 0s; }
            .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
            .typing-dots span:nth-child(3) { animation-delay: 0.4s; }

            @keyframes typingBounce {
                0%, 60%, 100% { transform: translateY(0); }
                30% { transform: translateY(-10px); }
            }

            .chatbot-input-area {
                padding: 16px 20px;
                border-top: 1px solid #e5e5e5;
                background: white;
                display: flex;
                gap: 12px;
                align-items: flex-end;
            }

            .input-wrapper {
                flex: 1;
                position: relative;
            }

            .message-input {
                width: 100%;
                border: 1px solid #e5e5e5;
                border-radius: 20px;
                padding: 10px 16px;
                font-size: 14px;
                resize: none;
                max-height: 100px;
                min-height: 40px;
                outline: none;
                transition: border-color 0.2s;
                font-family: inherit;
            }

            .message-input:focus {
                border-color: #667eea;
            }

            .input-actions {
                display: flex;
                gap: 8px;
            }

            .action-btn {
                width: 40px;
                height: 40px;
                border: none;
                border-radius: 50%;
                background: #f5f5f5;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s;
                font-size: 16px;
                color: #666;
            }

            .action-btn:hover {
                background: #e5e5e5;
            }

            .action-btn.primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }

            .action-btn.primary:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }

            .action-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .file-input {
                display: none;
            }

            .quick-actions {
                padding: 12px 20px;
                border-top: 1px solid #e5e5e5;
                background: #fafafa;
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .quick-action {
                padding: 6px 12px;
                background: white;
                border: 1px solid #e5e5e5;
                border-radius: 16px;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s;
                white-space: nowrap;
            }

            .quick-action:hover {
                background: #667eea;
                color: white;
                border-color: #667eea;
            }

            .welcome-message {
                text-align: center;
                padding: 40px 20px;
                color: #666;
            }

            .welcome-message .icon {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.5;
            }

            .welcome-message h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
                color: #333;
            }

            .welcome-message p {
                margin: 0;
                font-size: 14px;
                line-height: 1.5;
            }

            @media (max-width: 480px) {
                .chatbot-container {
                    width: calc(100vw - 40px);
                    right: 20px;
                    left: 20px;
                }
            }
        `;

        // Add styles to head
        const styleSheet = document.createElement('style');
        styleSheet.textContent = styles;
        document.head.appendChild(styleSheet);
    }

    createWidget() {
        const container = document.getElementById(this.options.containerId) || document.body;
        
        const widget = document.createElement('div');
        widget.className = `chatbot-widget ${this.options.position}`;
        
        widget.innerHTML = `
            <button class="chatbot-toggle" id="chatbot-toggle">
                <i class="fas fa-comments"></i>
            </button>
            
            <div class="chatbot-container" id="chatbot-window">
                <div class="chatbot-header">
                    <div class="avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="info">
                        <h4 class="title">${this.options.language === 'ar' ? 'أنيس - مساعدك الذكي' : 'AI Assistant'}</h4>
                        <p class="status" id="connection-status">
                            ${this.options.language === 'ar' ? 'متصل' : 'Online'}
                        </p>
                    </div>
                    <button class="close-btn" id="chatbot-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="chatbot-messages" id="chatbot-messages">
                    <div class="welcome-message">
                        <div class="icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3>${this.options.language === 'ar' ? 'أهلاً بك!' : 'Welcome!'}</h3>
                        <p>
                            ${this.options.language === 'ar' 
                                ? 'أنا هنا لمساعدتك. اسألني أي شيء تريد معرفته.'
                                : 'I\'m here to help you. Ask me anything you\'d like to know.'}
                        </p>
                    </div>
                    <div class="typing-indicator hidden" id="typing-indicator">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <button class="quick-action" data-message="${this.options.language === 'ar' ? 'مرحبا' : 'Hello'}">
                        ${this.options.language === 'ar' ? '👋 مرحبا' : '👋 Hello'}
                    </button>
                    <button class="quick-action" data-message="${this.options.language === 'ar' ? 'كيف يمكنني المساعدة؟' : 'How can you help?'}">
                        ${this.options.language === 'ar' ? '❓ كيف تساعدني' : '❓ How can you help'}
                    </button>
                    <button class="quick-action" data-message="${this.options.language === 'ar' ? 'ما هي خدماتكم؟' : 'What are your services?'}">
                        ${this.options.language === 'ar' ? '🛍️ الخدمات' : '🛍️ Services'}
                    </button>
                </div>
                
                <div class="chatbot-input-area">
                    <div class="input-wrapper">
                        <textarea 
                            class="message-input" 
                            id="message-input" 
                            placeholder="${this.options.language === 'ar' ? 'اكتب رسالتك هنا...' : 'Type your message here...'}"
                            rows="1"
                        ></textarea>
                    </div>
                    <div class="input-actions">
                        ${this.options.enableFileUpload ? `
                            <button class="action-btn" id="file-upload-btn" title="${this.options.language === 'ar' ? 'إرفاق ملف' : 'Attach file'}">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="file" id="file-input" class="file-input" accept=".pdf,.doc,.docx,.txt,.png,.jpg,.jpeg">
                        ` : ''}
                        ${this.options.enableVoice ? `
                            <button class="action-btn" id="voice-btn" title="${this.options.language === 'ar' ? 'رسالة صوتية' : 'Voice message'}">
                                <i class="fas fa-microphone"></i>
                            </button>
                        ` : ''}
                        <button class="action-btn primary" id="send-btn" title="${this.options.language === 'ar' ? 'إرسال' : 'Send'}">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(widget);
        this.widget = widget;
    }

    bindEvents() {
        // Toggle chatbot
        const toggleBtn = this.widget.querySelector('#chatbot-toggle');
        const closeBtn = this.widget.querySelector('#chatbot-close');
        
        toggleBtn.addEventListener('click', () => this.toggle());
        closeBtn.addEventListener('click', () => this.close());

        // Message input
        const messageInput = this.widget.querySelector('#message-input');
        const sendBtn = this.widget.querySelector('#send-btn');
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        messageInput.addEventListener('input', () => {
            this.autoResizeTextarea(messageInput);
        });

        sendBtn.addEventListener('click', () => this.sendMessage());

        // Quick actions
        const quickActions = this.widget.querySelectorAll('.quick-action');
        quickActions.forEach(action => {
            action.addEventListener('click', () => {
                const message = action.dataset.message;
                messageInput.value = message;
                this.sendMessage();
            });
        });

        // File upload
        if (this.options.enableFileUpload) {
            const fileUploadBtn = this.widget.querySelector('#file-upload-btn');
            const fileInput = this.widget.querySelector('#file-input');
            
            fileUploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', (e) => this.handleFileUpload(e));
        }

        // Voice input
        if (this.options.enableVoice) {
            const voiceBtn = this.widget.querySelector('#voice-btn');
            voiceBtn.addEventListener('click', () => this.toggleVoiceRecording());
        }
    }

    initWebSocket() {
        console.log('initWebSocket called');
        console.log('window.Echo available:', !!window.Echo);
        
        if (window.Echo) {
            try {
                // Disconnect from previous channel if exists
                if (this.conversationChannel) {
                    console.log('Disconnecting from previous channel');
                    this.conversationChannel.stopListening('MessageSent');
                    this.conversationChannel.stopListening('MessageReceived');
                }
                
                // Listen to conversation channel for real-time messages
                console.log('Setting up WebSocket channel for session:', this.sessionId);
                this.conversationChannel = window.Echo.channel(`conversation.${this.sessionId}`)
                    .listen('MessageSent', (e) => {
                        console.log('Message received via WebSocket:', e);
                        console.log('Message role:', e.message?.role);
                        console.log('Message content:', e.message?.content);
                        // Only show bot messages, not user messages
                        if (e.message && e.message.role === 'assistant') {
                            this.addMessage(e.message.content, 'bot');
                            this.hideTypingIndicator();
                        }
                    })
                    .subscribed(() => {
                        console.log('Successfully subscribed to channel:', `conversation.${this.sessionId}`);
                    })
                    .error((error) => {
                        console.error('WebSocket channel error:', error);
                    });

                // Update connection status
                this.updateConnectionStatus(true);
                
                console.log('WebSocket initialized successfully for session:', this.sessionId);
            } catch (error) {
                console.error('WebSocket initialization error:', error);
                this.updateConnectionStatus(false);
            }
        } else {
            console.warn('Laravel Echo is not available. WebSocket functionality disabled.');
            this.updateConnectionStatus(false);
        }
    }

    updateConnectionStatus(connected) {
        const statusElement = this.widget.querySelector('#connection-status');
        if (connected) {
            statusElement.textContent = this.options.language === 'ar' ? 'متصل' : 'Online';
            statusElement.style.color = '#4ade80';
        } else {
            statusElement.textContent = this.options.language === 'ar' ? 'غير متصل' : 'Offline';
            statusElement.style.color = '#f87171';
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const container = this.widget.querySelector('.chatbot-container');
        container.classList.add('open');
        this.isOpen = true;
        
        // Focus on input
        setTimeout(() => {
            const input = this.widget.querySelector('#message-input');
            input.focus();
        }, 300);
    }

    close() {
        const container = this.widget.querySelector('.chatbot-container');
        container.classList.remove('open');
        this.isOpen = false;
    }

    async sendMessage() {
        const input = this.widget.querySelector('#message-input');
        const message = input.value.trim();
        
        if (!message) return;

        // Add user message to UI
        this.addMessage(message, 'user');
        input.value = '';
        this.autoResizeTextarea(input);

        // Show typing indicator
        this.showTypingIndicator();

        try {
            // Send message via API
            console.log('Sending message with session_id:', this.sessionId);
            const response = await fetch('/api/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({
                    message: message,
                    chatbot_id: this.options.chatbotId,
                    conversation_id: this.currentConversationId,
                    session_id: this.sessionId,
                }),
            });

            const data = await response.json();
            console.log('API response:', data);
            
            if (data.success) {
                this.currentConversationId = data.conversation_id || data.data?.conversation_id;
                
                // Update session ID and reinitialize WebSocket if needed
                const newSessionId = data.session_id || data.data?.session_id;
                if (newSessionId && newSessionId !== this.sessionId) {
                    console.log('Updating session ID from', this.sessionId, 'to', newSessionId);
                    this.sessionId = newSessionId;
                    this.initWebSocket(); // Reconnect to new session channel
                }
                
                // If WebSocket is not available, add bot response directly
                if (!window.Echo) {
                    this.addMessage(data.message, 'bot');
                    this.hideTypingIndicator();
                }
            } else {
                throw new Error(data.message || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.addMessage(
                this.options.language === 'ar' 
                    ? 'عذراً، حدث خطأ أثناء إرسال الرسالة. حاول مرة أخرى.' 
                    : 'Sorry, there was an error sending your message. Please try again.',
                'bot',
                true
            );
            this.hideTypingIndicator();
        }
    }

    addMessage(content, sender, isError = false) {
        const messagesContainer = this.widget.querySelector('#chatbot-messages');
        const welcomeMessage = messagesContainer.querySelector('.welcome-message');
        
        // Hide welcome message if it exists
        if (welcomeMessage) {
            welcomeMessage.style.display = 'none';
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString(this.options.language === 'ar' ? 'ar-EG' : 'en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageDiv.innerHTML = `
            <div class="message-bubble ${isError ? 'error' : ''}">
                ${content}
            </div>
            <div class="message-time">${timeString}</div>
        `;

        // Insert before typing indicator
        const typingIndicator = messagesContainer.querySelector('#typing-indicator');
        messagesContainer.insertBefore(messageDiv, typingIndicator);

        // Scroll to bottom
        this.scrollToBottom();
        
        // Store message
        this.messages.push({
            content,
            sender,
            timestamp: now.toISOString(),
            isError
        });
    }

    showTypingIndicator() {
        const indicator = this.widget.querySelector('#typing-indicator');
        indicator.classList.remove('hidden');
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        const indicator = this.widget.querySelector('#typing-indicator');
        indicator.classList.add('hidden');
    }

    scrollToBottom() {
        const messagesContainer = this.widget.querySelector('#chatbot-messages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
    }

    handleFileUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file size (max 10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert(this.options.language === 'ar' 
                ? 'حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت.'
                : 'File size too large. Maximum 10MB allowed.');
            return;
        }

        // Show upload message
        this.addMessage(
            `📎 ${this.options.language === 'ar' ? 'تم إرفاق ملف:' : 'File attached:'} ${file.name}`,
            'user'
        );

        // TODO: Implement file upload logic
        console.log('File selected:', file);
    }

    toggleVoiceRecording() {
        // TODO: Implement voice recording
        console.log('Voice recording clicked');
    }

    destroy() {
        if (this.conversationChannel) {
            this.conversationChannel.stopListening('MessageSent');
            this.conversationChannel.stopListening('MessageReceived');
        }
        
        if (this.widget) {
            this.widget.remove();
        }
    }
}

// Export for use
window.ChatbotWidget = ChatbotWidget;

export default ChatbotWidget;
