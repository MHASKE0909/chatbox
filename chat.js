document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".typing-area"),
        inputField = form.querySelector(".input-field"),
        sendBtn = form.querySelector("button"),
        chatBox = document.querySelector(".chat-box"),
        suggestionsBox = document.getElementById("suggestions-box"),
        incoming_id = form.querySelector(".incoming_id").value;

  form.onsubmit = (e) => e.preventDefault(); // Prevent normal form submission

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
              addSpeechIcons();  // Add speaker icons to messages
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
              speakIcon.innerHTML = "🔊"; // Speaker Icon
              speakIcon.classList.add("speak-icon");
              speakIcon.style.cursor = "pointer";
              speakIcon.style.marginLeft = "8px";
              
              speakIcon.onclick = (event) => {
                  event.stopPropagation(); // Prevents any unwanted bubbling effects
                  playMessage(msgElement);
              };
              
              msgElement.appendChild(speakIcon);
          }
      });
  }

  function playMessage(msgElement) {
      let messageText = msgElement.cloneNode(true);  // Clone the message
      let icon = messageText.querySelector(".speak-icon"); // Find the speaker icon

      if (icon) {
          messageText.removeChild(icon);  // Remove the speaker icon from cloned text
      }

      let text = messageText.innerText.trim(); // Extract only the clean message text

      // Option 1: Use Free Browser TTS
      if ("speechSynthesis" in window) {
          let speech = new SpeechSynthesisUtterance(text);
          speech.lang = "en-US"; // Set language (e.g., "hi-IN" for Hindi)
          speech.rate = 1;
          speechSynthesis.speak(speech);
      } else {
          // Option 2: Use Free VoiceRSS API
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
});