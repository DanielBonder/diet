// Get the button and message elements
const button = document.getElementById('magicButton');
const message = document.getElementById('message');

// Add an event listener to the button
button.addEventListener('click', () => {
    // Change the text of the message
    message.textContent = "Ta-da! You've made some magic happen!";
    // Change the button's text
    button.textContent = "Magic Done!";
    // Disable the button after click
    button.disabled = true;
    button.style.backgroundColor = "#6c757d";
});
