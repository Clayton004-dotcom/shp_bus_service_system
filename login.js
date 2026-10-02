const loginForm = document.querySelector('#login-form');
const message = document.querySelector('#login-message');
const passwordInput = document.querySelector('#password');
const togglePassword = document.querySelector('#toggle-password');
const signupForm = document.querySelector('#signup-form');
const signupMessage = document.querySelector('#signup-message');
const switchFormButton = document.querySelector('#switch-form');
const loginTitle = document.querySelector('#login-title');
const formIntro = document.querySelector('#form-intro');
const switchPrompt = document.querySelector('#switch-prompt');
const helpNote = document.querySelector('#help-note');
const year = document.querySelector('#year');
let signingUp = false;

async function readApiResponse(response, endpoint) {
  const contentType = response.headers.get('content-type') || '';
  if (!contentType.toLowerCase().includes('application/json')) {
    throw new Error(`The server did not run ${endpoint}. Open this site through XAMPP/Apache or PHP's local server, not a static server such as Live Server.`);
  }
  try {
    return await response.json();
  } catch {
    throw new Error(`The server returned invalid data from ${endpoint}. Check the PHP server error log.`);
  }
}

function requestErrorMessage(error) {
  if (error instanceof TypeError) {
    return 'Cannot reach the PHP service. Open this site at http://localhost/fear_/ through XAMPP/Apache and make sure Apache and MySQL are running.';
  }
  return error instanceof Error ? error.message : 'The request failed. Please try again.';
}

if (year) year.textContent = new Date().getFullYear();
togglePassword?.addEventListener('click', () => {
  const show = passwordInput.type === 'password';
  passwordInput.type = show ? 'text' : 'password';
  togglePassword.textContent = show ? 'Hide' : 'Show';
  togglePassword.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});

switchFormButton?.addEventListener('click', () => {
  signingUp = !signingUp;
  loginForm.hidden = signingUp;
  signupForm.hidden = !signingUp;
  loginTitle.textContent = signingUp ? 'Create your passenger account' : 'Sign in to your account';
  formIntro.textContent = signingUp
    ? 'Choose a passenger ID and password to get started.'
    : 'Use your passenger ID and password to continue.';
  switchPrompt.textContent = signingUp ? 'Already have an account?' : 'New to SHP?';
  switchFormButton.textContent = signingUp ? 'Sign in' : 'Create an account';
  helpNote.hidden = signingUp;
  message.textContent = '';
  signupMessage.textContent = '';
});

loginForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  message.textContent = '';
  const passengerId = loginForm.elements.passenger_id.value.trim();
  const password = loginForm.elements.password.value;
  if (!passengerId || !password) {
    message.textContent = 'Enter your passenger ID and password to continue.';
    return;
  }
  const submitButton = loginForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.querySelector('span:first-child').textContent = 'Signing in…';
  try {
    const response = await fetch('login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ passenger_id: passengerId, password })
    });
    const result = await readApiResponse(response, 'login.php');
    if (!response.ok) throw new Error(result.error || 'Unable to sign in. Please try again.');
    window.location.assign('transaction.html');
  } catch (error) {
    message.textContent = requestErrorMessage(error);
  } finally {
    submitButton.disabled = false;
    submitButton.querySelector('span:first-child').textContent = 'Continue to fare portal';
  }
});

signupForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  signupMessage.textContent = '';
  const passengerId = signupForm.elements.passenger_id.value.trim();
  const password = signupForm.elements.password.value;
  const confirmPassword = signupForm.elements.confirm_password.value;
  if (!passengerId || !password || !confirmPassword) {
    signupMessage.textContent = 'Complete all fields to create your account.';
    return;
  }
  if (password.length < 8) {
    signupMessage.textContent = 'Your password must be at least 8 characters long.';
    return;
  }
  if (password !== confirmPassword) {
    signupMessage.textContent = 'The passwords do not match.';
    return;
  }

  const submitButton = signupForm.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.querySelector('span:first-child').textContent = 'Creating account…';
  try {
    const tokenResponse = await fetch('signup.php', {
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' }
    });
    const tokenResult = await readApiResponse(tokenResponse, 'signup.php');
    if (!tokenResponse.ok) throw new Error(tokenResult.error || 'Unable to start account creation. Please try again.');

    const response = await fetch('signup.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': tokenResult.csrf_token
      },
      credentials: 'same-origin',
      body: JSON.stringify({ passenger_id: passengerId, password, confirm_password: confirmPassword })
    });
    const result = await readApiResponse(response, 'signup.php');
    if (!response.ok) throw new Error(result.error || 'Unable to create your account. Please try again.');
    window.location.assign('transaction.html');
  } catch (error) {
    signupMessage.textContent = requestErrorMessage(error);
  } finally {
    submitButton.disabled = false;
    submitButton.querySelector('span:first-child').textContent = 'Create passenger account';
  }
});
