const form = document.querySelector('#transaction-form');
const message = document.querySelector('#transaction-message');
const receipt = document.querySelector('#receipt');
const receiptDetails = document.querySelector('#receipt-details');
const submitButton = form.querySelector('button[type="submit"]');
let csrfToken = '';

function showError(text) { message.textContent = text; }
function addReceiptItem(label, value) {
  const item = document.createElement('div');
  const term = document.createElement('dt');
  const detail = document.createElement('dd');
  term.textContent = label;
  detail.textContent = value;
  item.append(term, detail);
  receiptDetails.append(item);
}

async function checkSession() {
  try {
    const response = await fetch('session.php', { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
    const result = await response.json();
    if (!response.ok || !result.authenticated) {
      window.location.replace('index.html');
      return;
    }
    document.querySelector('#passenger-name').textContent = result.passenger_id;
    document.querySelector('.avatar').textContent = result.passenger_id.slice(0, 1).toUpperCase();
    csrfToken = result.csrf_token;
  } catch {
    window.location.replace('index.html');
  }
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  message.textContent = '';
  receipt.hidden = true;
  const fare = Number(form.elements.fare_amount.value);
  if (!form.elements.pcard_id.value.trim() || !form.elements.bus_id.value.trim()) {
    showError('Enter both the payment card ID and bus ID.');
    return;
  }
  if (!Number.isFinite(fare) || fare < 0.01 || fare > 10000) {
    showError('Enter a fare amount from K0.01 to K10,000.00.');
    return;
  }
  submitButton.disabled = true;
  submitButton.querySelector('span:first-child').textContent = 'Saving transaction…';
  try {
    const response = await fetch('transaction.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': csrfToken },
      credentials: 'same-origin',
      body: JSON.stringify({ pcard_id: form.elements.pcard_id.value.trim(), bus_id: form.elements.bus_id.value.trim(), fare_amount: fare })
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'The fare could not be saved.');
    receiptDetails.replaceChildren();
    addReceiptItem('Transaction ID', result.transaction_id);
    addReceiptItem('Payment Card ID', result.pcard_id);
    addReceiptItem('Bus ID', result.bus_id);
    addReceiptItem('Fare amount', `K${Number(result.fare_amount).toFixed(2)} PGK`);
    addReceiptItem('Timestamp', result.timestamp);
    receipt.hidden = false;
    form.reset();
    receipt.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  } catch (error) {
    showError(error.message || 'Could not connect. Check your connection and try again.');
  } finally {
    submitButton.disabled = false;
    submitButton.querySelector('span:first-child').textContent = 'Save fare transaction';
  }
});

document.querySelector('#new-transaction').addEventListener('click', () => {
  receipt.hidden = true;
  form.elements.pcard_id.focus();
});
document.querySelector('#logout-button').addEventListener('click', async () => {
  try { await fetch('logout.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken } }); }
  finally { window.location.replace('index.html'); }
});
checkSession();
