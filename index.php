<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with n8n Webhook</title>
    <style>
        body { font-family: Arial, sans-serif; }
        #chatbox { 
            width: 100%; 
            height: 400px; 
            overflow-y: scroll; 
            border: 1px solid #ccc; 
            margin-bottom: 10px; 
            padding: 10px; 
        }
        #message { width: 80%; padding: 10px; }
        #sendButton { padding: 10px; }
        .preset-button { margin-right: 10px; padding: 10px; }

        /* Chat bubble styles */
        .message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .message.user {
            justify-content: flex-end;
        }
        .message.bot {
            justify-content: flex-start;
        }
        .message .content {
            max-width: 70%;
            padding: 10px;
            border-radius: 10px;
        }
        .message.user .content {
            background-color: #d1ffd6;
            margin-right: 10px;
        }
        .message.bot .content {
            background-color: #f1f1f1;
            margin-left: 10px;
        }
        .message img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        /* Typing animation styles */
        .typing-indicator {
            display: flex;
            align-items: center;
        }
        .typing-indicator .dot {
            width: 8px;
            height: 8px;
            margin: 0 2px;
            background-color: #ccc;
            border-radius: 50%;
            display: inline-block;
            animation: blink 1.4s infinite both;
        }
        .typing-indicator .dot:nth-child(1) {
            animation-delay: 0.2s;
        }
        .typing-indicator .dot:nth-child(2) {
            animation-delay: 0.4s;
        }
        .typing-indicator .dot:nth-child(3) {
            animation-delay: 0.6s;
        }
        @keyframes blink {
            0% {
                opacity: 0.2;
            }
            20% {
                opacity: 1;
            }
            100% {
                opacity: 0.2;
            }
        }
    </style>
</head>
<body>

    <h1>Chat with n8n Webhook</h1>

    <div id="chatbox"></div>

    <input type="text" id="message" placeholder="Type your message here..." />
    <button id="sendButton">Send</button>
    
    <br><br>

    <!-- Div where preset buttons from PHP will be loaded -->
    <div id="preset_buttons"></div>

    <!-- Load Marked.js for Markdown-to-HTML conversion -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <script>
        const webhookUrl = 'YOUR_N8N_WEBHOOK_URL';
        const chatbox = document.getElementById('chatbox');

        // Function to add typing indicator to the chatbox
        function showTyping() {
            const typingIndicator = document.createElement('div');
            typingIndicator.id = 'typing';
            typingIndicator.className = 'typing-indicator';
            typingIndicator.innerHTML = `
                <span class="dot"></span>
                <span class="dot"></span>
                <span class="dot"></span>
                <p>n8n is typing...</p>
            `;
            chatbox.appendChild(typingIndicator);
            chatbox.scrollTop = chatbox.scrollHeight; // Scroll to bottom
        }

        // Function to remove typing indicator from the chatbox
        function removeTyping() {
            const typingIndicator = document.getElementById('typing');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        // Function to send message to n8n webhook
        function sendMessage(message) {
            addMessageToChatbox('user', 'You', message);

            showTyping();

            fetch(webhookUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: message })
            })
            .then(response => response.json())
            .then(data => {
                removeTyping();  // Remove typing indicator once response is received
                const markdownContent = data.output;

                // Convert markdown to HTML using marked.js
                const renderedHTML = marked.parse(markdownContent);
                addMessageToChatbox('bot', 'n8n', renderedHTML);
                chatbox.scrollTop = chatbox.scrollHeight;  // Scroll to bottom
            })
            .catch(error => {
                removeTyping();
                addMessageToChatbox('bot', 'n8n', `<p>Error: ${error.message}</p>`);
            });
        }

        // Function to load preset buttons from PHP
        function loadPresetButtons() {
            fetch('/preset_buttons.php', {
                method: 'GET'
            })
            .then(response => response.text())  // Get the HTML content as text
            .then(html => {
                document.getElementById('preset_buttons').innerHTML = html;  // Inject the HTML into the target div
            })
            .catch(error => {
                console.error('Error loading preset buttons:', error);
                document.getElementById('preset_buttons').innerHTML = '<p>Error loading buttons.</p>';
            });
        }

        // Function to add message to the chatbox
        function addMessageToChatbox(sender, name, content) {
            const messageElement = document.createElement('div');
            messageElement.classList.add('message', sender);

            const imgSrc = sender === 'user' ? 'user-profile.png' : 'bot-profile.png';

            messageElement.innerHTML = `
                <img src="${imgSrc}" alt="${name}">
                <div class="content">${content}</div>
            `;
            
            chatbox.appendChild(messageElement);
            chatbox.scrollTop = chatbox.scrollHeight;  // Scroll to bottom
        }

        // Call the function to load preset buttons when the page loads
        window.onload = loadPresetButtons;

        // Assign the function to the window object so it's globally accessible
        window.sendPresetMessage = function(presetMessage) {
            // Grab present prompt from textarea instead
            var strPrompt = document.getElementById(presetMessage).value;
            sendMessage(strPrompt);
        };

        // Send a message when clicking the send button
        document.getElementById('sendButton').onclick = function() {
            const message = document.getElementById('message').value;
            sendMessage(message);
            document.getElementById('message').value = '';
        };
    </script>

</body>
</html>
