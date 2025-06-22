<?php  
  session_start();
  include_once "php/config.php";
  if (!isset($_SESSION['unique_id'])) {
    header("location: login.php");
  }
?>
<?php include_once "header.php"; ?>
<body>
  <div class="wrapper">
    <section class="chat-area">
      <header>
        <?php 
          $user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
          $sql = mysqli_query($conn, "SELECT * FROM users WHERE unique_id = {$user_id}");
          if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_assoc($sql);
          } else {
            header("location: users.php");
          }
        ?>
        <a href="users.php" class="back-icon"><i class="fas fa-arrow-left"></i></a>
        <img src="php/images/<?php echo $row['img']; ?>" alt="User Image">
        <div class="details">
          <span><?php echo $row['fname']. " " . $row['lname']; ?></span>
          <p><?php echo $row['status']; ?></p>
        </div>
      </header>

      <div class="chat-box"></div>

      <!-- AI Reply Suggestions -->
      <div class="suggestions-box" id="suggestions-box"></div>

      <form action="#" class="typing-area">
        <input type="hidden" class="incoming_id" name="incoming_id" value="<?php echo $user_id; ?>">
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <button type="submit">
          <i class="fab fa-telegram-plane"></i>
        </button>
      </form>
    </section>
  </div>
  <script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".typing-area"),
          inputField = form.querySelector(".input-field"),
          sendBtn = form.querySelector("button"),
          chatBox = document.querySelector(".chat-box"),
          suggestionsBox = document.getElementById("suggestions-box"),
          incoming_id = form.querySelector(".incoming_id").value;

    form.onsubmit = (e) => e.preventDefault();

    inputField.onkeyup = () => {
        sendBtn.classList.toggle("active", inputField.value.trim() !== "");
    };
    
    sendBtn.onclick = () => {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "php/insert-chat.php", true);
        xhr.onload = () => {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                inputField.value = "";
                sendBtn.classList.remove("active");
                scrollToBottom();
                suggestionsBox.innerHTML = ""; // Clear suggestions after sending
            }
        };
        let formData = new FormData(form);
        xhr.send(formData);
    };

    setInterval(() => {
        let xhr = new XMLHttpRequest();
        xhr.open("POST", "php/get-chat.php", true);
        xhr.onload = () => {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
                chatBox.innerHTML = xhr.responseText;
                scrollToBottom();
                addSpeechIcons();
                showSmartReplies(); // Generate suggestions from last receiver message
            }
        };
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send("incoming_id=" + incoming_id);
    }, 1000);

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function addSpeechIcons() {
        document.querySelectorAll(".chat .details p").forEach(msgElement => {
            if (!msgElement.querySelector(".speak-icon")) {
                let speakIcon = document.createElement("span");
                speakIcon.innerHTML = "🔊";
                speakIcon.classList.add("speak-icon");
                speakIcon.style.cursor = "pointer";
                speakIcon.style.marginLeft = "8px";

                speakIcon.onclick = (event) => {
                    event.stopPropagation();
                    playMessage(msgElement);
                };

                msgElement.appendChild(speakIcon);
            }
        });
    }
     
    function playMessage(msgElement) {
        let messageText = msgElement.cloneNode(true);
        let icon = messageText.querySelector(".speak-icon");

        if (icon) {
            messageText.removeChild(icon);
        }

        let text = messageText.innerText.trim();

        if ("speechSynthesis" in window) {
            let speech = new SpeechSynthesisUtterance(text);
            speech.lang = "en-US";
            speech.rate = 1;
            speechSynthesis.speak(speech);
        } else {
            fetch("php/speak.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "text=" + encodeURIComponent(text)
            })
            .then(response => response.json())
            .then(data => {
                if (data.audio_url) {
                    let audio = new Audio(data.audio_url);
                    audio.play();
                } else {
                    console.error("Error playing message:", data.error);
                }
            })
            .catch(error => console.error("TTS Request failed!", error));
        }
    }

    function fetchSuggestions(userMessage) {
        if (!userMessage.trim()) return;

        fetch("php/get-suggestions.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "message=" + encodeURIComponent(userMessage)
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("API Error:", data.error);
            } else {
                displaySuggestions(data.suggestions);
            }
        })
        .catch(error => console.error("Request failed!", error));
    }

    function displaySuggestions(suggestions) {
        suggestionsBox.innerHTML = "";
        if (suggestions.length > 0) {
            suggestions.forEach(text => {
                let suggestion = document.createElement("button");
                suggestion.classList.add("suggestion-item");
                suggestion.textContent = text;
                suggestion.onclick = () => {
                    inputField.value = text;
                    suggestionsBox.innerHTML = "";
                    sendBtn.click(); // Auto send the message
                };
                suggestionsBox.appendChild(suggestion);
            });
        }
    }

    function showSmartReplies() {
        if (inputField.value.trim() !== "") return;

        let messages = document.querySelectorAll(".chat-box .chat.incoming .details p");
        if (messages.length === 0) return;

        let lastReceiverMessage = messages[messages.length - 1].innerText.trim();
        if (lastReceiverMessage) {
            fetchSuggestions(lastReceiverMessage);
        }
    }
});
</script>

  <script src="javascript/chat.js"></script>
</body>
</html>