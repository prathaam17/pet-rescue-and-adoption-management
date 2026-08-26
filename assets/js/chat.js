document.addEventListener('DOMContentLoaded', function () {
    const chatToggle = document.getElementById('chatToggle');
    const chatWindow = document.getElementById('chatWindow');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');
    const typingIndicator = document.getElementById('typingIndicator');

    // Toggle Chat Window
    chatToggle.addEventListener('click', () => {
        chatWindow.classList.toggle('open');
        chatToggle.classList.toggle('active');
        if (chatWindow.classList.contains('open')) {
            chatInput.focus();
        }
    });

    // Close on escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && chatWindow.classList.contains('open')) {
            chatWindow.classList.remove('open');
            chatToggle.classList.remove('active');
        }
    });

    // Send Message
    async function sendMessage() {
        const text = chatInput.value.trim();
        if (text === "") return;

        addMessage(text, 'user');
        chatInput.value = "";

        showTypingIndicator();

        try {
            const response = await fetch(APP_BASE_URL + 'chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });

            if (!response.ok) {
                throw new Error(`Server returned ${response.status} ${response.statusText}`);
            }

            const textResponse = await response.text();
            let data;
            try {
                data = JSON.parse(textResponse);
            } catch (e) {
                console.error("JSON Parse Error:", textResponse);
                throw new Error("Received invalid response from server (not JSON).");
            }

            if (data.reply) {
                addMessage(data.reply, 'bot');
            } else {
                addMessage("I'm sorry, I hit a snag! Please check your API configuration.", 'bot');
            }
        } catch (error) {
            console.error('Error:', error);
            addMessage(`System Error: ${error.message} (Please check console/network for details)`, 'bot');
        } finally {
            hideTypingIndicator();
        }
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.classList.add('message', sender);
        div.innerHTML = text;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        typingIndicator.style.display = 'block';
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function hideTypingIndicator() {
        typingIndicator.style.display = 'none';
    }

    // Quick Replies
    window.handleQuickReply = function (text) {
        chatInput.value = text;
        sendMessage();
    };
});
