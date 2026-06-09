/**
 * HustleKingdom — API Bridge
 * Replaces all localStorage calls with real API fetch calls.
 * Falls back to localStorage on network failure (offline support).
 *
 * Usage:
 *   const profile = await HK_API.getProfile();
 *   await HK_API.saveIncome({ amount: 5000, custom_name: 'Freelance', logged_at: '2025-06-01' });
 *   await HK_API.toggleSaved(3);
 */

const HK_API = (() => {

  const BASE = '';  // same origin — no CORS needed

  // ── Core fetch wrapper ─────────────────────────────────────────────────────
  async function req(method, path, body = null) {
    const opts = {
      method,
      headers: { 'Accept': 'application/json' },
      credentials: 'same-origin',
    };

    if (body !== null) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify(body);
    }

    let res;
    try {
      res = await fetch(BASE + path, opts);
    } catch (networkErr) {
      // Offline — return null so callers can fall back to localStorage
      console.warn('[HK_API] Network error:', networkErr.message);
      return null;
    }

    if (res.status === 401) {
      // Session expired — redirect to login
      window.location.href = '/auth/login?next=' + encodeURIComponent(window.location.pathname);
      return null;
    }

    let data;
    try { data = await res.json(); }
    catch { data = null; }

    return data;
  }

  // ── Profile ────────────────────────────────────────────────────────────────
  async function getProfile() {
    const r = await req('GET', '/api/profile');
    return r?.data ?? null;
  }

  async function updateProfile(fields) {
    const r = await req('PUT', '/api/profile', fields);
    return r?.data ?? null;
  }

  // ── Income ─────────────────────────────────────────────────────────────────
  async function getIncome(days = 90) {
    const r = await req('GET', `/api/income?days=${days}`);
    return r?.data ?? null;
  }

  async function saveIncome(entry) {
    // entry: { amount, hustle_id?, custom_name?, note?, logged_at? }
    const r = await req('POST', '/api/income', entry);
    return r?.data ?? null;
  }

  async function deleteIncome(id) {
    const r = await req('DELETE', `/api/income?id=${id}`);
    return r?.ok ?? false;
  }

  // ── Goals ──────────────────────────────────────────────────────────────────
  async function getGoals() {
    const r = await req('GET', '/api/goals');
    return r?.data ?? [];
  }

  async function saveGoal(goal) {
    const r = await req('POST', '/api/goals', goal);
    return r?.data ?? null;
  }

  async function updateGoal(id, fields) {
    const r = await req('PUT', `/api/goals?id=${id}`, fields);
    return r?.data ?? null;
  }

  async function deleteGoal(id) {
    const r = await req('DELETE', `/api/goals?id=${id}`);
    return r?.ok ?? false;
  }

  // ── Hustles ────────────────────────────────────────────────────────────────
  async function getHustles(params = {}) {
    const qs = new URLSearchParams(params).toString();
    const r  = await req('GET', `/api/hustles${qs ? '?' + qs : ''}`);
    return r?.data ?? { hustles: [], total: 0 };
  }

  async function getHustle(slug) {
    const r = await req('GET', `/api/hustles?slug=${encodeURIComponent(slug)}`);
    return r?.data ?? null;
  }

  // ── Saved Hustles ──────────────────────────────────────────────────────────
  async function getSaved() {
    const r = await req('GET', '/api/saved');
    return r?.data ?? [];
  }

  async function toggleSaved(hustleId) {
    const r = await req('POST', '/api/saved', { hustle_id: hustleId });
    return r?.data ?? null;
  }

  // ── Referral ───────────────────────────────────────────────────────────────
  async function getReferral() {
    const r = await req('GET', '/api/referral');
    return r?.data ?? null;
  }

  // ── Subscription ───────────────────────────────────────────────────────────
  async function getSubscriptionStatus() {
    const r = await req('GET', '/api/subscription');
    return r?.data ?? { is_pro: false };
  }

  // ── Auth helpers ───────────────────────────────────────────────────────────
  function isLoggedIn() {
    // Presence of hk_session_active cookie set by PHP on login
    return document.cookie.split(';').some(c => c.trim().startsWith('hk_authed=1'));
  }

  // ── EXPOSED PUBLIC API ─────────────────────────────────────────────────────
  return {
    getProfile, updateProfile,
    getIncome, saveIncome, deleteIncome,
    getGoals, saveGoal, updateGoal, deleteGoal,
    getHustles, getHustle,
    getSaved, toggleSaved,
    getReferral,
    getSubscriptionStatus,
    isLoggedIn,
  };
})();


// ── ONE-TIME MIGRATION: localStorage → DB ─────────────────────────────────────
const HK_MIGRATE = (() => {

  const DONE_KEY = 'hk_migrated_v1';

  async function run() {
    if (localStorage.getItem(DONE_KEY)) return;  // already done
    if (!document.cookie.includes('hk_authed=1')) return;  // not logged in

    const incomeRaw   = localStorage.getItem('incomeLog');
    const goalsRaw    = localStorage.getItem('hk_goals');
    const savedRaw    = localStorage.getItem('savedHustles');

    const incomeLog   = incomeRaw  ? JSON.parse(incomeRaw)  : [];
    const goals       = goalsRaw   ? JSON.parse(goalsRaw)   : [];
    const savedHustles= savedRaw   ? JSON.parse(savedRaw)   : [];

    const hasData = incomeLog.length || goals.length || savedHustles.length;
    if (!hasData) {
      localStorage.setItem(DONE_KEY, '1');
      return;
    }

    console.log('[HK_MIGRATE] Migrating localStorage to server…');

    try {
      const res = await fetch('/api/migrate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ incomeLog, goals, savedHustles }),
      });

      const data = await res.json();
      if (data.ok) {
        console.log('[HK_MIGRATE] Done:', data.data);
        localStorage.setItem(DONE_KEY, '1');
        // Clear migrated keys
        ['incomeLog','hk_goals','savedHustles','hk_ref_count','hk_user','hk_streak'].forEach(k => {
          localStorage.removeItem(k);
        });
        if (typeof showToast === 'function') showToast('✅ Your data has been saved to your account!');
      } else {
        console.warn('[HK_MIGRATE] Server error:', data.error);
      }
    } catch (e) {
      console.warn('[HK_MIGRATE] Network error, will retry next session.');
    }
  }

  return { run };
})();

// Auto-run migration after DOM loads
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => HK_MIGRATE.run());
} else {
  HK_MIGRATE.run();
}
