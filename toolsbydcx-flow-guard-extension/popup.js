const $ = selector => document.querySelector(selector);
const message = $('#message'), statusBox = $('#status'), login = $('#login'), logout = $('#logout');

function render(status, error = '') {
  if (error) message.textContent = error, message.classList.add('error');
  else message.classList.remove('error');
  if (!status?.authenticated) {
    statusBox.classList.add('hidden'); login.classList.remove('hidden'); logout.classList.add('hidden');
    if (!error) message.textContent = 'Sign in with your ToolsByDcx panel account to use Flow.';
    return;
  }
  login.classList.add('hidden'); logout.classList.remove('hidden'); statusBox.classList.remove('hidden');
  const access = status.access || {};
  statusBox.replaceChildren();
  const label = document.createElement('div');
  label.className = 'status-label';
  label.textContent = 'Connected account';
  const title = document.createElement('strong');
  title.textContent = status.user?.name || status.user?.email || 'Authenticated';
  const credits = document.createElement('span');
  credits.textContent = `Credits: ${access.remaining_credits == null ? 'Unlimited' : `${access.remaining_credits} remaining`}`;
  const cost = document.createElement('span');
  cost.textContent = `Cost per video: ${access.generation_cost || 0}`;
  const model = document.createElement('span');
  const allowedModels = Array.isArray(access.allowed_models) && access.allowed_models.length
    ? access.allowed_models
    : (access.allowed_model ? [access.allowed_model] : []);
  model.textContent = `Allowed models: ${allowedModels.length ? allowedModels.join(', ') : 'No model assigned'}`;
  const control = document.createElement('span');
  control.textContent = `Browser control: ${access.full_control === true ? 'Full control enabled' : 'Standard'}`;
  statusBox.append(label, title, credits, cost, model, control);
  message.textContent = status.access?.enabled === true
    ? 'Flow Guard is active.'
    : 'Server login detected. Flow Guard access is not enabled for this user yet.';
}

chrome.runtime.sendMessage({ type: 'GET_STATUS' }).then(result => render(result?.status)).catch(() => render(null, 'Could not reach the Flow Guard service.'));
login.addEventListener('submit', async event => {
  event.preventDefault();
  const button = login.querySelector('button'); button.disabled = true; message.textContent = 'Signing in…';
  const form = new FormData(login);
  const result = await chrome.runtime.sendMessage({ type: 'LOGIN', username: form.get('username'), password: form.get('password') });
  button.disabled = false;
  if (!result?.ok) return render(null, result?.error || 'Sign in failed.');
  render(result.status);
});
$('#panel').addEventListener('click', () => chrome.runtime.sendMessage({ type: 'OPEN_PANEL' }));
logout.addEventListener('click', async () => { await chrome.runtime.sendMessage({ type: 'LOGOUT' }); render(null); });
