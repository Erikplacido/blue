function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Fechar Modal clicando fora
window.onclick = function(event) {
    const modals = document.getElementsByClassName('custom-modal');
    for (let modal of modals) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
};

// Novo: Salvar Reservation e Quotation via AJAX
document.addEventListener('DOMContentLoaded', function() {

// Reservation Form
const reservationForm = document.getElementById('reservationForm');
if (reservationForm) {
    reservationForm.addEventListener('submit', function (e) {
        e.preventDefault();

const serviceSelect = this.querySelector('select[name="service_name"]');
const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

const data = {
    consumer_name: this.querySelector('input[placeholder="Consumer Name"]').value,
    referred: this.querySelector('input[placeholder="First Name"]').value,
    referred_last_name: this.querySelector('input[placeholder="Last Name"]').value,
    referral_code: this.querySelector('input[placeholder="Referral Code"]').value,
    email: this.querySelector('input[type="email"]').value,
    mobile: this.querySelector('input[placeholder="Mobile"]').value,
    postcode: this.querySelector('input[placeholder="Postcode"]').value,
    address: this.querySelector('input[placeholder="Address"]').value,
    number: this.querySelector('input[name="number"]').value,
    suburb: this.querySelector('input[placeholder="Suburb"]').value,
    city: this.querySelector('input[placeholder="City"]').value,
    territory: this.querySelector('input[placeholder="Territory"]').value,
    service_id: selectedOption.value,
    service_name: selectedOption.text,
    commission_amount: this.querySelector('input[placeholder="Commission Amount"]').value,
    client_type: this.querySelector('select[name="client_type"]').value
};

        console.log('Reservation data:', data);

        fetch('actions/insert_reservation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                alert('Reservation saved successfully!');
                closeModal('newReservationModal');
                window.location.reload();
            } else {
                alert('Error saving reservation: ' + (res.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error saving reservation. Please try again.');
        });
    });
}

// Quotation Form
const quotationForm = document.getElementById('quotationForm');
if (quotationForm) {
    quotationForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const serviceSelect = this.querySelector('select[name="service_name"]');
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];

        const data = {
            referred: this.querySelector('input[name="referred"]').value,
            referred_last_name: this.querySelector('input[name="referred_last_name"]').value,
            referral_code: this.querySelector('input[name="referral_code"]').value,
            email: this.querySelector('input[name="email"]').value,
            mobile: this.querySelector('input[name="mobile"]').value,
            postcode: this.querySelector('input[name="postcode"]').value,
            address: this.querySelector('input[name="address"]').value,
            number: this.querySelector('input[name="number"]').value,
            suburb: this.querySelector('input[name="suburb"]').value,
            city: this.querySelector('input[name="city"]').value,
            territory: this.querySelector('input[name="territory"]').value,
            service_id: selectedOption.value,
            service_name: selectedOption.text,
            more_details: this.querySelector('textarea[name="more_details"]').value,

            // ✅ Novo campo: Client Type
            client_type: this.querySelector('select[name="client_type"]').value
        };

        console.log('Quotation data:', data);

        fetch('actions/insert_quotation.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) throw new Error("Server returned an error");
            return response.json();
        })
        .then(res => {
            if (res.success) {
                alert('Quotation saved successfully!');
                closeModal('newQuotationModal');
                window.location.reload();
            } else {
                alert('Error saving quotation: ' + (res.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('Error saving quotation. Please try again.');
        });
    });
}



});

document.querySelectorAll("form").forEach(form => {
  form.addEventListener("submit", e => {
    const input = form.querySelector("input[id^='autocomplete-address']");
    if (input && !input.value.trim()) {
      alert("Please enter a valid address.");
      input.classList.add("error");
      setTimeout(() => input.classList.remove("error"), 2000);
      input.focus();
      e.preventDefault();
    }
  });
});