/* assets/js/code.js — versão blindada finalizada */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Hamburger
  document.querySelector('.hamburger')?.addEventListener('click', () =>
    document.querySelector('.nav-links')?.classList.toggle('open')
  );

  // 2. Hero Slider
  const slides = document.querySelector('.slides'),
        list   = [...document.querySelectorAll('.slide')],
        prev   = document.querySelector('.prev'),
        next   = document.querySelector('.next'),
        dotsC  = document.querySelector('.indicators');
  let idx = 0;

  if (slides && list.length && dotsC) {
    list.forEach((_, k) => {
      const b = document.createElement('button');
      b.addEventListener('click', () => { idx = k; paint(); });
      dotsC.append(b);
    });
    const dots = [...dotsC.children];
    const paint = () => {
      slides.style.transform = `translateX(-${idx * 100}%)`;
      dots.forEach((d, i) => d.classList.toggle('active', i === idx));
    };
    prev?.addEventListener('click', () => { idx = (idx - 1 + list.length) % list.length; paint(); });
    next?.addEventListener('click', () => { idx = (idx + 1) % list.length; paint(); });
    paint();
  }

  // 3. Services config
  const services = {
    home_cleaning: {
      name: 'Home Cleaning',
      selectValue: 'Home Cleaning',
      subtitle: 'Transform Your Home with Our Cleaning Services.',
      desc: 'Experience unmatched cleanliness with Basic, Deep, and End of Lease Cleaning tailored to your needs.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/home-cleaning-bg.webp'
    },
    commercial_cleaning: {
      name: 'Commercial Cleaning',
      selectValue: 'Commercial Cleaning',
      subtitle: 'Elevate Your Workspace with Tailored Cleaning.',
      desc: 'We serve gyms, churches, medical centers, and offices with expert commercial cleaning solutions.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/commercial-cleaning-bg.webp'
    },
    short_rental_cleaning: {
      name: 'Short Rental Cleaning',
      selectValue: 'Short Rental Cleaning',
      subtitle: 'Comprehensive Short‑Rental Management Solutions.',
      desc: 'Keep your rental property immaculate with our professional services.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/short-rental-bg.webp'
    },
    short_rental_management: {
      name: 'Short Rental Management',
      selectValue: 'Short Rental Management',
      subtitle: 'Comprehensive Management Services.',
      desc: 'Full service for your short-term rental operations.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/short-rental-bg.webp'
    },
    handyman: {
      name: 'Handyman',
      selectValue: 'Handyman',
      subtitle: 'General Repairs and Maintenance.',
      desc: 'Helping you with household fixes and installations.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/support-services-bg.webp'
    },
    gardening: {
      name: 'Gardening',
      selectValue: 'Gardening',
      subtitle: 'Garden Maintenance and Care.',
      desc: 'We keep your green areas looking great.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/support-services-bg.webp'
    },
    pressure_washing: {
      name: 'Pressure Washing',
      selectValue: 'Pressure Washing',
      subtitle: 'Surface and Exterior Deep Cleaning.',
      desc: 'Restore surfaces with high-pressure water cleaning.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/support-services-bg.webp'
    },
    steam_cleaning: {
      name: 'Steam Cleaning',
      selectValue: 'Steam Cleaning',
      subtitle: 'Disinfection and Deep Sanitation.',
      desc: 'We use steam to clean and sanitize all surfaces.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/support-services-bg.webp'
    },
    window_cleaning: {
      name: 'Window Cleaning',
      selectValue: 'Window Cleaning',
      subtitle: 'Crystal Clear Window Service.',
      desc: 'Let the light in with spotless windows.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/support-services-bg.webp'
    },
    strata_services: {
      name: 'Strata Services',
      selectValue: 'Strata Services',
      subtitle: 'Care for Shared Spaces and Buildings.',
      desc: 'Maintain harmony and cleanliness in shared spaces with expert care.',
      bg: 'https://bluefacilityservices.com.au/wp-content/uploads/2025/01/strata-services-bg.webp'
    }
  };

  // 4. Modal handlers
  const modal   = document.getElementById('quoteModal'),
        left    = document.getElementById('modalLeft'),
        nameEl  = document.getElementById('modalServiceName'),
        subEl   = document.getElementById('modalServiceSubtitle'),
        descEl  = document.getElementById('modalServiceDesc'),
        selEl   = document.querySelector('select[name="service_name"],select[name="service"]');

  const showModal = rawKey => {
    if (!modal) return;
    const key = Object.keys(services).find(k => services[k].selectValue.toLowerCase() === rawKey?.toLowerCase());
    if (key && services[key]) {
      const s = services[key];
      nameEl.textContent  = s.name;
      subEl.textContent   = s.subtitle;
      descEl.textContent  = s.desc;
      left.style.backgroundImage = `url('${s.bg}')`;
      selEl && (selEl.value = s.selectValue);
    }

    const refInput = document.getElementById('referral_code');
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('referral');
    if (refInput && refCode && /^[A-Z0-9]{3,20}$/i.test(refCode)) {
      refInput.value = refCode;
      refInput.setAttribute('readonly', true);
      refInput.style.backgroundColor = '#f4f4f4';
    }

    modal.style.display = 'flex';
  };

  document.querySelectorAll('.booking-btn').forEach(btn =>
    btn.addEventListener('click', e => {
      e.preventDefault();
      showModal(btn.closest('.card')?.dataset.service);
    })
  );

  const bookBtn = document.querySelector('.btn-book');
  if (bookBtn) {
    bookBtn.addEventListener('click', e => {
      e.preventDefault();
      showModal(selEl?.value);
    });
  }

  document.querySelector('.close-modal')?.addEventListener('click', () => modal.style.display = 'none');
  window.addEventListener('click', e => {
    if (e.target === modal) modal.style.display = 'none';
  });

  // 5. Referral pre-fill on first load
  const refInput = document.getElementById('referral_code');
  const referralCode = new URLSearchParams(window.location.search).get('referral');
  if (refInput && referralCode && /^[A-Z0-9]{3,20}$/i.test(referralCode)) {
    refInput.value = referralCode;
    refInput.setAttribute('readonly', true);
    refInput.style.backgroundColor = '#f4f4f4';
  }

  // 6. Submit
  const form = document.getElementById('quoteForm');
  if (!form) return;

  let isSubmitting = false;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    if (isSubmitting) return;
    isSubmitting = true;

    const required = form.querySelectorAll('[required]');
    let isValid = true;
    required.forEach(field => {
      if (!field.value.trim()) {
        isValid = false;
        field.classList.add('error');
        setTimeout(() => field.classList.remove('error'), 3000); // efeito temporário
      }
    });

    if (!isValid) {
      alert('Please fill all required fields.');
      isSubmitting = false;
      return;
    }

    // Verifica client_type válido
    if (!form.client_type.value || form.client_type.value === 'Select Client Type') {
      alert('Please select a valid Client Type.');
      form.client_type.classList.add('error');
      setTimeout(() => form.client_type.classList.remove('error'), 3000);
      isSubmitting = false;
      return;
    }

const payload = {
  referral_code: form.referral_code.value.trim(),
  referred: form.referred.value.trim(),
  referred_last_name: form.referred_last_name.value.trim(),
  email: form.email.value.trim(),
  mobile: form.mobile.value.trim(),
  postcode: form.postcode.value.trim(),
  client_type: form.client_type.value,
  service_name: form.service_name.value,
  more_details: form.more_details.value.trim(),
  number: form.number.value.trim(),
  address: form.address.value.trim(),
  suburb: form.suburb.value.trim(),
  city: form.city.value.trim(),
  territory: form.territory.value.trim(),
  user_id: form.user_id?.value || null
};

    try {
      const res = await fetch('quote/submit_quote_2.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json; charset=utf-8' },
        body: JSON.stringify(payload)
      });

      const ct = res.headers.get('Content-Type') || '';
      const body = ct.includes('application/json') ? await res.json() : await res.text();

      if (!res.ok || (body && body.error)) {
        console.error('Submission failed', body);
        alert(body.error || 'An error occurred.');
      } else {
        alert('Thank you! Our team will contact you soon.');
        window.location.reload(); // 🔄 força reload para manter o referral ativo
      }
    } catch (err) {
      console.error(err);
      alert('Submit failed – check console.');
    } finally {
      isSubmitting = false;
    }
  });
});