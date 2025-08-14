import './bootstrap';
import ChatbotWidget from './components/ChatbotWidget.js';

// Initialize chatbot widget when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Make ChatbotWidget available globally
    window.ChatbotWidget = ChatbotWidget;
    
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
