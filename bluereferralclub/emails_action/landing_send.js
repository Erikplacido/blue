document.getElementById("quoteForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);

  fetch("emails_action/send_quote_email.php", {
    method: "POST",
    body: formData,
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Request received! Please check your email.");
        form.reset();
        document.querySelector(".modal").style.display = "none";
      } else {
        alert("Failed to send: " + data.message);
      }
    })
    .catch((err) => {
      console.error("Error:", err);
      alert("An unexpected error occurred. Please try again.");
    });
});