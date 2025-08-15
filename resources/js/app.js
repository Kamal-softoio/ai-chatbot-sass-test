import './bootstrap';
import ChatbotWidget from './components/ChatbotWidget.js';

// Make ChatbotWidget available globally immediately
window.ChatbotWidget = ChatbotWidget;

// Initialize chatbot widget when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize default chatbot if on public pages
    if (!document.querySelector('.chatbot-widget')) {
        const widget = new ChatbotWidget({
            chatbotId: 1,
            autoOpen: false,
            language: document.documentElement.lang || 'ar',
            enableVoice: true,
            enableFileUpload: true
        });
    }
});
