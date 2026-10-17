import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { Link, Navigate, Route, Routes, useLocation, useNavigate } from 'react-router-dom';
import { isSupabaseConfigured, supabase } from './lib/supabase.js';

const AuthContext = createContext(null);
const useAuth = () => useContext(AuthContext);

function AuthProvider({ children }) {
  const [session, setSession] = useState(null);
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(isSupabaseConfigured);

  useEffect(() => {
    if (!supabase) return;
    let active = true;
    const loadProfile = async (user) => {
      if (!user) { if (active) setProfile(null); return; }
      const { data } = await supabase.from('profiles').select('*').eq('id', user.id).maybeSingle();
      if (active) setProfile(data ?? { id: user.id, username: user.user_metadata?.username ?? '', ...user.user_metadata });
    };
    supabase.auth.getSession().then(async ({ data }) => {
      if (!active) return;
      setSession(data.session);
      await loadProfile(data.session?.user);
      if (active) setLoading(false);
    });
    const { data: listener } = supabase.auth.onAuthStateChange((_event, nextSession) => {
      setSession(nextSession);
      if (!nextSession) setProfile(null);
      else queueMicrotask(() => loadProfile(nextSession.user));
      setLoading(false);
    });
    return () => { active = false; listener.subscription.unsubscribe(); };
  }, []);

  const value = useMemo(() => ({ session, profile, setProfile, loading }), [session, profile, loading]);
  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

const media = [
  { title: 'Toy Story 5', image: '/toy_story_five.jpg', genre: 'Animation' },
  { title: 'Captain America: Brave New World', image: '/captain_america_brave_new_world.jpg', genre: 'Action' },
  { title: 'Thunderbolts*', image: '/thunderbolts.jpg', genre: 'Action' },
  { title: 'Ballerina', image: '/ballerina.jpg', genre: 'Action' },
  { title: 'Avengers: Doomsday', image: '/avengers_doomsday.jpg', genre: 'Action' },
  { title: 'The Conjuring: Last Rites', image: '/conjuring_last_rites.jpg', genre: 'Horror' },
  { title: 'Smile 2', image: '/smile_two.jpg', genre: 'Horror' },
  { title: 'Scream 7', image: '/scream_seven.jpg', genre: 'Horror' },
  { title: 'The Running Man', image: '/running_man.jpg', genre: 'Action' },
  { title: 'Wicked', image: '/wicked_ver2.jpg', genre: 'Drama' },
  { title: 'Sinister', image: '/sinister.jpg', genre: 'Horror' },
  { title: 'It', image: '/it.jpg', genre: 'Horror' },
  { title: 'John Wick', image: '/john_wick.jpg', genre: 'Action' },
  { title: 'Deliver Us from Evil', image: '/deliver_us_from_evil.jpg', genre: 'Horror' },
  { title: 'The Shining', image: '/shining_ver2.jpg', genre: 'Horror' },
];

function SetupNotice() {
  if (isSupabaseConfigured) return null;
  return <div className="setup-notice"><strong>Supabase setup needed</strong><span>Copy <code>.env.example</code> to <code>.env</code>, then add your project URL and publishable key.</span></div>;
}

function App() {
  return <AuthProvider><SetupNotice /><Routes>
    <Route path="/" element={<SignIn />} />
    <Route path="/signup" element={<SignUp />} />
    <Route path="/forgot-password" element={<ForgotPassword />} />
    <Route path="/reset-password" element={<ResetPassword />} />
    <Route path="/home" element={<Protected><Home /></Protected>} />
    <Route path="/profile" element={<Protected><Profile /></Protected>} />
    <Route path="/series/squid-game" element={<Protected><Series /></Protected>} />
    <Route path="*" element={<Navigate to="/" replace />} />
  </Routes></AuthProvider>;
}

function Protected({ children }) {
  const { session, loading } = useAuth();
  if (loading) return <div className="loading">Loading ScreenHub…</div>;
  return session ? children : <Navigate to="/" replace />;
}

function AuthShell({ children, title, sub }) {
  return <main className="auth-page"><div className="auth-glow" /><div className="auth-brand">SCREENHUB</div><section className="auth-card"><div className="eyebrow">YOUR NEXT FAVORITE STORY</div><h1>{title}</h1><p className="auth-sub">{sub}</p>{children}</section><div className="auth-foot">A home for everything worth watching.</div></main>;
}

function SignIn() {
  const { session } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [busy, setBusy] = useState(false);
  if (session) return <Navigate to="/home" replace />;
  async function submit(event) {
    event.preventDefault(); setError(''); setBusy(true);
    const { error: authError } = await supabase.auth.signInWithPassword(form);
    setBusy(false);
    if (authError) setError(authError.message);
    else navigate('/home');
  }
  return <AuthShell title="Welcome back" sub="Sign in to pick up where you left off.">
    <form className="form" onSubmit={submit}>
      <Field label="Email address" type="email" autoComplete="email" value={form.email} onChange={(email) => setForm({ ...form, email })} required />
      <Field label="Password" type="password" autoComplete="current-password" value={form.password} onChange={(password) => setForm({ ...form, password })} required />
      <div className="form-inline"><span /> <Link to="/forgot-password">Forgot password?</Link></div>
      {error && <p className="form-error">{error}</p>}
      <button className="primary-btn" disabled={busy || !supabase}>{busy ? 'Signing in…' : 'Sign in'}</button>
    </form>
    <p className="auth-switch">New to ScreenHub? <Link to="/signup">Create an account</Link></p>
  </AuthShell>;
}

function SignUp() {
  const { session } = useAuth();
  const navigate = useNavigate();
  const [form, setForm] = useState({ username: '', email: '', first_name: '', middle_name: '', last_name: '', birthdate: '', password: '', confirm: '' });
  const [error, setError] = useState(''); const [message, setMessage] = useState(''); const [busy, setBusy] = useState(false);
  if (session) return <Navigate to="/home" replace />;
  const change = (key) => (value) => setForm((old) => ({ ...old, [key]: value }));
  async function submit(event) {
    event.preventDefault(); setError(''); setMessage('');
    if (form.password.length < 8 || !/[A-Z]/.test(form.password) || !/[a-z]/.test(form.password) || !/\d/.test(form.password) || !/[^A-Za-z0-9]/.test(form.password)) { setError('Use at least 8 characters with uppercase, lowercase, a number, and a symbol.'); return; }
    if (form.password !== form.confirm) { setError('Passwords do not match.'); return; }
    if (new Date(form.birthdate) > new Date(Date.now() - 18 * 365.25 * 86400000)) { setError('You must be 18 or older to register.'); return; }
    setBusy(true);
    const { data, error: authError } = await supabase.auth.signUp({ email: form.email.trim(), password: form.password, options: { data: { username: form.username.trim(), first_name: form.first_name.trim(), middle_name: form.middle_name.trim(), last_name: form.last_name.trim(), birthdate: form.birthdate } } });
    setBusy(false);
    if (authError) { setError(authError.message.includes('create_profile') || authError.message.includes('duplicate') ? 'That username is already taken.' : authError.message); return; }
    if (data.session) navigate('/home'); else setMessage('Check your email to confirm your account, then sign in.');
  }
  return <AuthShell title="Join ScreenHub" sub="Create an account and start your watchlist.">
    <form className="form signup-form" onSubmit={submit}>
      <Field label="Username" value={form.username} onChange={change('username')} minLength={3} maxLength={30} required />
      <Field label="Email address" type="email" autoComplete="email" value={form.email} onChange={change('email')} required />
      <div className="form-row"><Field label="First name" value={form.first_name} onChange={change('first_name')} required /><Field label="Last name" value={form.last_name} onChange={change('last_name')} required /></div>
      <Field label="Middle name (optional)" value={form.middle_name} onChange={change('middle_name')} />
      <Field label="Date of birth" type="date" value={form.birthdate} onChange={change('birthdate')} required />
      <Field label="Password" type="password" autoComplete="new-password" value={form.password} onChange={change('password')} required />
      <Field label="Confirm password" type="password" autoComplete="new-password" value={form.confirm} onChange={change('confirm')} required />
      {error && <p className="form-error">{error}</p>}{message && <p className="form-success">{message}</p>}
      <button className="primary-btn" disabled={busy || !supabase}>{busy ? 'Creating account…' : 'Create account'}</button>
    </form>
    <p className="auth-switch">Already have an account? <Link to="/">Sign in</Link></p>
  </AuthShell>;
}

function ForgotPassword() {
  const [email, setEmail] = useState(''); const [error, setError] = useState(''); const [message, setMessage] = useState(''); const [busy, setBusy] = useState(false);
  async function submit(event) {
    event.preventDefault(); setError(''); setMessage(''); setBusy(true);
    const { error: resetError } = await supabase.auth.resetPasswordForEmail(email.trim(), { redirectTo: `${window.location.origin}/reset-password` });
    setBusy(false);
    if (resetError) setError(resetError.message); else setMessage('If there is an account for that email, a password reset link is on its way.');
  }
  return <AuthShell title="Reset your password" sub="We’ll email you a secure reset link."><form className="form" onSubmit={submit}><Field label="Email address" type="email" value={email} onChange={setEmail} required />{error && <p className="form-error">{error}</p>}{message && <p className="form-success">{message}</p>}<button className="primary-btn" disabled={busy || !supabase}>{busy ? 'Sending…' : 'Send reset link'}</button></form><p className="auth-switch"><Link to="/">Back to sign in</Link></p></AuthShell>;
}

function ResetPassword() {
  const navigate = useNavigate(); const [password, setPassword] = useState(''); const [confirm, setConfirm] = useState(''); const [error, setError] = useState(''); const [done, setDone] = useState(false);
  async function submit(event) {
    event.preventDefault(); setError('');
    if (password.length < 8 || password !== confirm) { setError(password !== confirm ? 'Passwords do not match.' : 'Password must have at least 8 characters.'); return; }
    const { error: updateError } = await supabase.auth.updateUser({ password });
    if (updateError) setError(updateError.message); else setDone(true);
  }
  return <AuthShell title="Choose a new password" sub="Set a new password for your ScreenHub account.">{done ? <div className="form-success">Password changed. <button className="text-button" onClick={() => navigate('/')}>Sign in</button></div> : <form className="form" onSubmit={submit}><Field label="New password" type="password" value={password} onChange={setPassword} required /><Field label="Confirm new password" type="password" value={confirm} onChange={setConfirm} required />{error && <p className="form-error">{error}</p>}<button className="primary-btn" disabled={!supabase}>Update password</button></form>}</AuthShell>;
}

function Field({ label, value, onChange, type = 'text', ...props }) {
  return <label className="field"><span>{label}</span><input type={type} value={value} onChange={(event) => onChange(event.target.value)} {...props} /></label>;
}

function Header() {
  const { profile } = useAuth(); const navigate = useNavigate(); const [menu, setMenu] = useState(false);
  async function signOut() { await supabase.auth.signOut(); navigate('/'); }
  const initials = `${profile?.first_name?.[0] ?? ''}${profile?.last_name?.[0] ?? ''}`.toUpperCase() || profile?.username?.[0]?.toUpperCase() || 'S';
  return <header className="site-header"><Link className="wordmark" to="/home">SCREENHUB</Link><nav><Link to="/home">Home</Link><a href="#catalog">Movies &amp; series</a></nav><div className="header-user"><button className="avatar" onClick={() => setMenu(!menu)} aria-label="Open account menu">{initials}</button>{menu && <div className="account-menu"><b>{[profile?.first_name, profile?.last_name].filter(Boolean).join(' ') || 'Your account'}</b><span>@{profile?.username}</span><Link to="/profile" onClick={() => setMenu(false)}>Edit profile</Link><button onClick={signOut}>Sign out</button></div>}</div></header>;
}

function PosterRow({ title, items }) { return <section className="catalog-section" id="catalog"><div className="section-heading"><h2>{title}</h2><span>Explore all</span></div><div className="poster-row">{items.map((item) => <Link className="poster-card" to={item.title === 'Squid Game' ? '/series/squid-game' : '/home'} key={item.title}><img src={item.image} alt={item.title} /><span>{item.title}</span></Link>)}</div></section>; }

function Home() {
  const [query, setQuery] = useState(''); const [genre, setGenre] = useState('All');
  const filtered = media.filter((item) => item.title.toLowerCase().includes(query.toLowerCase()) && (genre === 'All' || item.genre === genre));
  const popular = [{ title: 'Squid Game', image: 'https://static.wikia.nocookie.net/thesquidgame/images/7/7d/GdWDj4ZaoAEgw26.jpg/revision/latest?cb=20241214072040' }, ...media.slice(2, 9)];
  return <><Header /><main className="home-page"><section className="featured-hero"><img src="/predator_badlands_ver5.jpg" alt="Predator: Badlands" /><div className="hero-shade"/><div className="hero-copy"><div className="eyebrow">✦ &nbsp;FEATURED TONIGHT</div><h1>Predator:<br />Badlands</h1><p>Banished from his group, a young Predator forms an unexpected partnership with a human survivor deep in the dangerous Badlands.</p><div className="hero-meta"><span>IMDb 7.3</span><span>2025</span><span>Action · Sci-Fi</span><span>1h 52m</span></div><a className="primary-btn hero-btn" href="#catalog">Explore titles <span>→</span></a></div></section>
    <div className="discover-tools"><div className="genre-tabs">{['All', 'Action', 'Horror', 'Drama', 'Animation'].map((item) => <button key={item} className={genre === item ? 'selected' : ''} onClick={() => setGenre(item)}>{item}</button>)}</div><label className="search"><span>⌕</span><input placeholder="Search titles" value={query} onChange={(event) => setQuery(event.target.value)} /></label></div>
    {filtered.length ? <PosterRow title={query || genre !== 'All' ? 'Search results' : 'Trending movies'} items={filtered} /> : <p className="empty-state">No titles match that search.</p>}
    <PosterRow title="Popular series" items={popular} />
  </main></>;
}

function Profile() {
  const { profile, session, setProfile } = useAuth(); const navigate = useNavigate(); const [username, setUsername] = useState(profile?.username ?? ''); const [password, setPassword] = useState(''); const [notice, setNotice] = useState(''); const [error, setError] = useState(''); const [busy, setBusy] = useState(false);
  useEffect(() => { setUsername(profile?.username ?? ''); }, [profile]);
  async function save(event) {
    event.preventDefault(); setNotice(''); setError(''); setBusy(true);
    const { error: profileError } = await supabase.from('profiles').update({ username: username.trim() }).eq('id', session.user.id);
    if (profileError) { setError(profileError.code === '23505' ? 'That username is already taken.' : profileError.message); setBusy(false); return; }
    let passwordError;
    if (password) ({ error: passwordError } = await supabase.auth.updateUser({ password }));
    setBusy(false);
    if (passwordError) setError(passwordError.message); else { setProfile({ ...profile, username: username.trim() }); setPassword(''); setNotice('Your profile has been saved.'); }
  }
  return <><Header /><main className="profile-page"><Link className="back-link" to="/home">← Back to ScreenHub</Link><div className="profile-panel"><div className="eyebrow">ACCOUNT</div><h1>Your profile</h1><p className="muted">Manage your username and sign-in details.</p><form className="form" onSubmit={save}><Field label="Username" value={username} onChange={setUsername} minLength={3} maxLength={30} required /><Field label="New password (leave blank to keep current)" type="password" value={password} onChange={setPassword} minLength={8} /><div className="profile-email"><span>Email</span><strong>{session?.user?.email}</strong><small>Email changes are managed through Supabase Auth.</small></div>{error && <p className="form-error">{error}</p>}{notice && <p className="form-success">{notice}</p>}<button className="primary-btn" disabled={busy}>{busy ? 'Saving…' : 'Save changes'}</button></form><button className="signout-link" onClick={async () => { await supabase.auth.signOut(); navigate('/'); }}>Sign out</button></div></main></>;
}

function Series() {
  return <><Header /><main className="series-page"><div className="series-hero"><img src="https://static.wikia.nocookie.net/thesquidgame/images/7/7d/GdWDj4ZaoAEgw26.jpg/revision/latest?cb=20241214072040" alt="Squid Game" /><div className="hero-shade" /><div className="series-copy"><div className="eyebrow">#1 ON SCREENHUB</div><h1>SQUID<br />GAME</h1><p>2021 – 2024　·　TV-MA　·　Thriller · Drama · Mystery</p><p>456 cash-strapped players accept a mysterious invitation to compete in deadly children's games for a life-changing prize.</p><a className="primary-btn hero-btn" href="https://www.youtube.com/results?search_query=Squid+Game+official+trailer" target="_blank" rel="noreferrer">▶ &nbsp;Play trailer</a></div></div><section className="catalog-section"><div className="section-heading"><h2>More to discover</h2><Link to="/home">Browse all</Link></div><div className="poster-row">{media.slice(0, 6).map((item) => <div className="poster-card" key={item.title}><img src={item.image} alt={item.title} /><span>{item.title}</span></div>)}</div></section></main></>;
}

export default App;
