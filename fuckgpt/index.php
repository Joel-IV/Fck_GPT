<?php
session_start();

// Initialize conversations if not exists
if (!isset($_SESSION['conversations'])) {
    $_SESSION['conversations'] = [];
}

// Store new chat if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['query'])) {
    $newMessage = [
        'type' => 'user',
        'content' => $_POST['query']
    ];
    $_SESSION['conversations'][] = [$newMessage];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coding Assistant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #f8fafc;
        }
        .thinking-animation {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .thinking-animation span {
            width: 8px;
            height: 8px;
            background-color: #94a3b8;
            border-radius: 50%;
            animation: bounce 1.5s infinite ease-in-out;
        }
        .thinking-animation span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .thinking-animation span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }
        .sidebar-text {
            color: #cbd5e1;
        }
        .sidebar-text:hover {
            color: #f8fafc;
        }
        .chat-message {
            color: #f8fafc;
        }
        .chat-message-user {
            background-color: #2563eb;
        }
        .chat-message-assistant {
            background-color: #374151;
        }
        .sidebar-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #38bdf8;
            text-align: center;
            margin-bottom: 1rem;
        }
        .sidebar-title:hover {
            color: #0ea5e9;
        }
        .recent-chats {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-900">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 border-r border-gray-700 p-4">
            <div class="sidebar-title">FCK_GPT</div>
            <form method="post" action="new_chat.php" class="mb-4">
                <button type="submit" class="w-full bg-green-500 text-white py-2 rounded-md hover:bg-green-600">
                    + New Chat
                </button>
            </form>
            <div class="space-y-2 recent-chats">
                <div class="text-sm sidebar-text">Recent Chats</div>
                <?php 
                // List recent conversations
                if (!empty($_SESSION['conversations'])) {
                    foreach (array_reverse($_SESSION['conversations']) as $index => $conversation) {
                        echo "<div class='p-2 hover:bg-gray-700 rounded sidebar-text'>";
                        echo htmlspecialchars(substr($conversation[0]['content'], 0, 30) . '...');
                        echo "</div>";
                    }
                } else {
                    echo "<div class='p-2 text-gray-500'>No recent chats</div>";
                }
                ?>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="flex-grow flex flex-col">
            <!-- Chat Messages Container -->
            <div class="flex-grow overflow-y-auto p-6 space-y-6" id="messagesContainer">
                <?php 
                // Display existing conversations
                if (!empty($_SESSION['conversations'])) {
                    foreach ($_SESSION['conversations'] as $conversation) {
                        foreach ($conversation as $message) {
                            $messageClass = $message['type'] === 'user' 
                                ? 'chat-message chat-message-user justify-end' 
                                : 'chat-message chat-message-assistant justify-start';
                            
                            echo "<div class='flex {$messageClass}'>";
                            echo "<div class='max-w-2xl p-4 rounded-lg'>";
                            echo htmlspecialchars($message['content']);
                            echo "</div></div>";
                        }
                    }
                }
                ?>
            </div>

            <!-- Input Area -->
            <div class="p-6 bg-gray-800 border-t border-gray-700">
                <form id="chatForm" method="post" action="process_query.php" class="flex items-center space-x-2">
                    <div class="flex-grow">
                        <textarea 
                            name="query" 
                            placeholder="Send a message..." 
                            class="w-full p-2 border rounded-md resize-none max-h-24 overflow-y-auto bg-gray-700 text-white placeholder-gray-400"
                            rows="1"
                        ></textarea>
                    </div>
                    <button 
                        type="submit" 
                        class="bg-green-500 text-white p-2 rounded-md hover:bg-green-600"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-scroll to bottom
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            container.scrollTop = container.scrollHeight;
        }
        scrollToBottom();

        // Enable Enter key to submit form
        document.querySelector('textarea[name="query"]').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.getElementById('chatForm').dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });

        // Form submission via AJAX
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = e.target;
            const formData = new FormData(form);

            // Add user message to chat
            const messagesContainer = document.getElementById('messagesContainer');
            const userMessageDiv = document.createElement('div');
            userMessageDiv.className = 'flex justify-end';
            userMessageDiv.innerHTML = `
                <div class='max-w-2xl p-4 rounded-lg chat-message chat-message-user'>
                    ${formData.get('query')}
                </div>
            `;
            messagesContainer.appendChild(userMessageDiv);

            // Add thinking animation
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'flex justify-start';
            loadingDiv.innerHTML = `
                <div class='max-w-2xl p-4 rounded-lg chat-message chat-message-assistant'>
                    <div class="thinking-animation">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(loadingDiv);
            scrollToBottom();

            // Clear input
            form.querySelector('textarea').value = '';

            // Send AJAX request
            fetch('process_query.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Remove thinking animation
                messagesContainer.removeChild(loadingDiv);

                // Add assistant response
                const assistantMessageDiv = document.createElement('div');
                assistantMessageDiv.className = 'flex justify-start';
                assistantMessageDiv.innerHTML = `
                    <div class='max-w-2xl p-4 rounded-lg chat-message chat-message-assistant'>
                        ${data.response}
                    </div>
                `;
                messagesContainer.appendChild(assistantMessageDiv);
                scrollToBottom();

                // Highlight code blocks
                document.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightBlock(block);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                // Remove thinking animation
                messagesContainer.removeChild(loadingDiv);
            });
        });
    </script>
</body>
</html>