import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { Link, Navigate, Route, Routes, useLocation, useNavigate, useParams, useSearchParams } from 'react-router-dom';
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
  { title: 'Predator: Badlands', genre: 'Sci-Fi', year: 2025, rating: '7.3', runtime: '1h 47m', mood: 'predator', description: 'Cast out on a dangerous world, a young hunter finds an unlikely ally and fights to survive the ultimate proving ground.' },
  { title: 'Dune: Part Two', genre: 'Adventure', year: 2024, rating: '8.5', runtime: '2h 46m', mood: 'dune', description: 'Paul Atreides unites with Chani and the Fremen on a journey of revenge and destiny across Arrakis.' },
  { title: 'The Batman', genre: 'Action', year: 2022, rating: '7.8', runtime: '2h 56m', mood: 'batman', description: 'A relentless detective follows a trail of clues through Gotham’s darkest corners.' },
  { title: 'Interstellar', genre: 'Sci-Fi', year: 2014, rating: '8.7', runtime: '2h 49m', mood: 'interstellar', description: 'A team of explorers travels beyond this galaxy to discover whether mankind has a future among the stars.' },
  { title: 'John Wick', genre: 'Action', year: 2014, rating: '7.4', runtime: '1h 41m', mood: 'wick', description: 'A retired hitman returns to the underworld and becomes a legend in his own right.' },
  { title: 'Wicked', genre: 'Fantasy', year: 2024, rating: '7.5', runtime: '2h 40m', mood: 'wicked', description: 'Two unlikely friends discover that their differences may change the future of Oz.' },
  { title: 'The Shining', genre: 'Horror', year: 1980, rating: '8.4', runtime: '2h 26m', mood: 'shining', description: 'A winter at an isolated hotel takes a terrifying turn for a family of three.' },
  { title: 'Spider-Man: Across the Spider-Verse', genre: 'Animation', year: 2023, rating: '8.6', runtime: '2h 20m', mood: 'spiderverse', description: 'Miles Morales swings across the multiverse and meets a team of Spider-People protecting its very existence.' },
  { title: 'The Grand Budapest Hotel', genre: 'Comedy', year: 2014, rating: '8.1', runtime: '1h 39m', mood: 'budapest', description: 'A legendary concierge and his protégé become wrapped up in a glamorous European mystery.' },
  { title: 'The Conjuring: Last Rites', genre: 'Horror', year: 2025, rating: '6.8', runtime: '2h 15m', mood: 'conjuring', description: 'The Warrens face one final case that brings the darkest parts of their past back to light.' },
  { title: 'La La Land', genre: 'Romance', year: 2016, rating: '8.0', runtime: '2h 8m', mood: 'lalaland', description: 'A jazz musician and an aspiring actor fall in love while pursuing their dreams in Los Angeles.' },
  { title: 'Oppenheimer', genre: 'Drama', year: 2023, rating: '8.3', runtime: '3h', mood: 'oppenheimer', description: 'A brilliant physicist faces the world-changing consequences of the secret project he helped create.' },
  { title: 'Whiplash', genre: 'Drama', year: 2014, rating: '8.5', runtime: '1h 46m', mood: 'whiplash', description: 'An ambitious young drummer pushes himself to the limit under the eye of a demanding instructor.' },
  { title: 'Captain America: Brave New World', genre: 'Action', year: 2025, rating: '5.7', runtime: '1h 58m', mood: 'captain', description: 'Sam Wilson steps into a new role and faces a conspiracy with global consequences.' },
  { title: 'Toy Story 5', genre: 'Animation', year: 2026, rating: '—', runtime: 'Coming soon', mood: 'toystory', description: 'Woody, Buzz and the gang return for a brand-new adventure.' },
  { title: 'Smile 2', genre: 'Horror', year: 2024, rating: '6.8', runtime: '2h 7m', mood: 'smile', description: 'A pop star’s world tour becomes a nightmare as an old terror follows her into the spotlight.' },
  { title: 'The Martian', genre: 'Sci-Fi', year: 2015, rating: '8.0', runtime: '2h 24m', mood: 'martian', description: 'Stranded on Mars, one astronaut uses ingenuity and humor to make it home alive.' },
  { title: 'Scream', genre: 'Thriller', year: 1996, rating: '7.4', runtime: '1h 51m', mood: 'scream', description: 'A masked killer turns the rules of horror movies into a deadly game.' },
  { title: 'The Secret Life of Walter Mitty', genre: 'Adventure', year: 2013, rating: '7.3', runtime: '1h 54m', mood: 'mitty', description: 'A quiet daydreamer takes the leap into a real-world adventure across the globe.' },
  { title: 'Inside Out 2', genre: 'Animation', year: 2024, rating: '7.5', runtime: '1h 36m', mood: 'insideout', description: 'New emotions arrive just as Riley begins a new chapter of growing up.' },
];
const genres = ['Action', 'Adventure', 'Animation', 'Comedy', 'Drama', 'Fantasy', 'Horror', 'Romance', 'Sci-Fi', 'Thriller'];
const titleSlug = (title) => title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
const TmdbPostersContext = createContext({ posters: {}, loading: false, error: '' });
const posterCacheKey = 'screenhub-tmdb-posters';
const posterCacheDuration = 7 * 24 * 60 * 60 * 1000;
let pendingPosterRequest;

function readPosterCache() {
  try {
    const cached = JSON.parse(localStorage.getItem(posterCacheKey) || 'null');
    const now = Date.now();
    if (!cached || !Number.isFinite(cached.savedAt) || cached.savedAt > now || now - cached.savedAt > posterCacheDuration || !cached.posters || typeof cached.posters !== 'object') return {};
    return Object.fromEntries(Object.entries(cached.posters).filter(([, path]) => typeof path === 'string' && path.startsWith('/')));
  } catch (error) {
    console.warn('Unable to read cached TMDB posters.', error);
    return {};
  }
}

function TmdbPostersProvider({ children }) {
  const { session } = useAuth();
  const [posters, setPosters] = useState(readPosterCache);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [loaded, setLoaded] = useState(false);
  const [retryCount, setRetryCount] = useState(0);

  useEffect(() => {
    if (!session?.user?.id || !supabase) return undefined;
    const missing = media.filter((item) => !posters[item.title]).map(({ title, year }) => ({ title, year }));
    if (!missing.length) return undefined;
    let active = true;
    setLoading(true);
    setError('');
    const load = async () => {
      try {
        if (!pendingPosterRequest) {
          pendingPosterRequest = supabase.functions.invoke('tmdb-posters', { body: { titles: missing } })
            .finally(() => { pendingPosterRequest = undefined; });
        }
        const { data, error: functionError } = await pendingPosterRequest;
        if (functionError) {
          const response = functionError.context;
          if (response instanceof Response) {
            const details = await response.clone().json().catch(() => null);
            throw new Error(details?.error || `${functionError.message} (HTTP ${response.status})`);
          }
          throw functionError;
        }
        if (!data?.posters || typeof data.posters !== 'object') throw new Error('The TMDB poster service returned an invalid response.');
        const found = Object.fromEntries(Object.entries(data.posters).filter(([, path]) => typeof path === 'string' && path.startsWith('/')));
        const merged = { ...posters, ...found };
        if (!active) return;
        setPosters(merged);
        setLoaded(true);
        try {
          localStorage.setItem(posterCacheKey, JSON.stringify({ savedAt: Date.now(), posters: merged }));
        } catch (cacheError) {
          console.warn('Unable to cache TMDB posters.', cacheError);
        }
      } catch (cause) {
        if (!active) return;
        console.error('Unable to load TMDB posters.', cause);
        setError(cause instanceof Error ? cause.message : 'An unexpected error occurred.');
      } finally {
        if (active) setLoading(false);
      }
    };
    load();
    return () => { active = false; };
  }, [session?.user?.id, retryCount]);

  const retry = () => {
    setError('');
    setRetryCount((count) => count + 1);
  };

  return <TmdbPostersContext.Provider value={{ posters, loading, error, loaded, retry }}>{children}</TmdbPostersContext.Provider>;
}

function SetupNotice() {
  if (isSupabaseConfigured) return null;
  return <div className="setup-notice"><strong>Supabase setup needed</strong><span>Copy <code>.env.example</code> to <code>.env</code>, then add your project URL and publishable key.</span></div>;
}

function App() {
  const [watchlist, setWatchlist] = useState(() => JSON.parse(localStorage.getItem('screenhub-watchlist') || '[]'));
  useEffect(() => localStorage.setItem('screenhub-watchlist', JSON.stringify(watchlist)), [watchlist]);
  const toggleWatchlist = (title) => setWatchlist((current) => current.includes(title) ? current.filter((item) => item !== title) : [...current, title]);
  const browseProps = { watchlist, toggleWatchlist };
  return <AuthProvider><SetupNotice /><TmdbPostersProvider><Routes>
    <Route path="/" element={<SignIn />} />
    <Route path="/signup" element={<SignUp />} />
    <Route path="/forgot-password" element={<ForgotPassword />} />
    <Route path="/reset-password" element={<ResetPassword />} />
    <Route path="/home" element={<Protected><Home {...browseProps} /></Protected>} />
    <Route path="/browse" element={<Protected><Browse {...browseProps} /></Protected>} />
    <Route path="/genre/:genre" element={<Protected><GenrePage {...browseProps} /></Protected>} />
    <Route path="/search" element={<Protected><SearchPage {...browseProps} /></Protected>} />
    <Route path="/watchlist" element={<Protected><WatchlistPage {...browseProps} /></Protected>} />
    <Route path="/title/:slug" element={<Protected><TitlePage {...browseProps} /></Protected>} />
    <Route path="/profile" element={<Protected><Profile /></Protected>} />
    <Route path="/series/squid-game" element={<Protected><TitlePage {...browseProps} legacySquid /></Protected>} />
    <Route path="*" element={<Navigate to="/" replace />} />
  </Routes></TmdbPostersProvider></AuthProvider>;
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

function LegacyHeader() {
  const { profile } = useAuth(); const navigate = useNavigate(); const [menu, setMenu] = useState(false);
  async function signOut() { await supabase.auth.signOut(); navigate('/'); }
  const initials = `${profile?.first_name?.[0] ?? ''}${profile?.last_name?.[0] ?? ''}`.toUpperCase() || profile?.username?.[0]?.toUpperCase() || 'S';
  return <header className="site-header"><Link className="wordmark" to="/home">SCREENHUB</Link><nav><Link to="/home">Home</Link><a href="#catalog">Movies &amp; series</a></nav><div className="header-user"><button className="avatar" onClick={() => setMenu(!menu)} aria-label="Open account menu">{initials}{profile?.avatar && <img src={profile.avatar} alt="" onError={(event) => { event.currentTarget.style.display = 'none'; }} />}</button>{menu && <div className="account-menu"><b>{[profile?.first_name, profile?.last_name].filter(Boolean).join(' ') || 'Your account'}</b><span>@{profile?.username}</span><Link to="/profile" onClick={() => setMenu(false)}>Edit profile</Link><button onClick={signOut}>Sign out</button></div>}</div></header>;
}

function PosterRow({ title, items }) { return <section className="catalog-section" id="catalog"><div className="section-heading"><h2>{title}</h2><span>Explore all</span></div><div className="poster-row">{items.map((item) => <Link className="poster-card" to={item.title === 'Squid Game' ? '/series/squid-game' : '/home'} key={item.title}><div className="poster-placeholder" aria-label={`${item.title} poster placeholder`}><span>{item.title}</span></div><span>{item.title}</span></Link>)}</div></section>; }

function LegacyHome() {
  const [query, setQuery] = useState(''); const [genre, setGenre] = useState('All');
  const filtered = media.filter((item) => item.title.toLowerCase().includes(query.toLowerCase()) && (genre === 'All' || item.genre === genre));
  const popular = [{ title: 'Squid Game', genre: 'Drama' }, ...media.slice(2, 9)];
  return <><Header /><main className="home-page"><section className="featured-hero"><div className="hero-shade"/><div className="hero-copy"><div className="eyebrow">✦ &nbsp;FEATURED TONIGHT</div><h1>Predator:<br />Badlands</h1><p>Banished from his group, a young Predator forms an unexpected partnership with a human survivor deep in the dangerous Badlands.</p><div className="hero-meta"><span>IMDb 7.3</span><span>2025</span><span>Action · Sci-Fi</span><span>1h 52m</span></div><a className="primary-btn hero-btn" href="#catalog">Explore titles <span>→</span></a></div></section>
    <div className="discover-tools"><div className="genre-tabs">{['All', 'Action', 'Horror', 'Drama', 'Animation'].map((item) => <button key={item} className={genre === item ? 'selected' : ''} onClick={() => setGenre(item)}>{item}</button>)}</div><label className="search"><span>⌕</span><input placeholder="Search titles" value={query} onChange={(event) => setQuery(event.target.value)} /></label></div>
    {filtered.length ? <PosterRow title={query || genre !== 'All' ? 'Search results' : 'Trending movies'} items={filtered} /> : <p className="empty-state">No titles match that search.</p>}
    <PosterRow title="Popular series" items={popular} />
  </main></>;
}

function Header() {
  const { profile } = useAuth(); const navigate = useNavigate(); const location = useLocation(); const [menu, setMenu] = useState(false); const [search, setSearch] = useState(new URLSearchParams(location.search).get('q') || '');
  useEffect(() => setSearch(new URLSearchParams(location.search).get('q') || ''), [location.search]);
  async function signOut() { await supabase.auth.signOut(); navigate('/'); }
  function submitSearch(event) { event.preventDefault(); navigate(`/search?q=${encodeURIComponent(search.trim())}`); }
  const initials = `${profile?.first_name?.[0] ?? ''}${profile?.last_name?.[0] ?? ''}`.toUpperCase() || profile?.username?.[0]?.toUpperCase() || 'S';
  return <header className="site-header"><Link className="wordmark" to="/home">SCREEN<span>HUB</span></Link><nav className="main-nav"><Link className={location.pathname === '/home' ? 'active' : ''} to="/home">Home</Link><Link className={location.pathname === '/browse' ? 'active' : ''} to="/browse">Discover</Link><Link className={location.pathname === '/watchlist' ? 'active' : ''} to="/watchlist">My list</Link></nav><form className="header-search" onSubmit={submitSearch}><span>⌕</span><input aria-label="Search movies and series" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Titles, genres, moods..." /><kbd>↵</kbd></form><div className="header-user"><button className="avatar" onClick={() => setMenu(!menu)} aria-label="Open account menu">{initials}{profile?.avatar && <img src={profile.avatar} alt="" onError={(event) => { event.currentTarget.style.display = 'none'; }} />}</button>{menu && <div className="account-menu"><b>{[profile?.first_name, profile?.last_name].filter(Boolean).join(' ') || 'Your account'}</b><span>@{profile?.username}</span><Link to="/profile" onClick={() => setMenu(false)}>Edit profile</Link><button onClick={signOut}>Sign out</button></div>}</div></header>;
}

function PosterCard({ item, watchlist = [], toggleWatchlist }) {
  const { posters } = useContext(TmdbPostersContext);
  const [imageFailed, setImageFailed] = useState(false);
  const posterPath = imageFailed ? null : posters[item.title];
  const saved = watchlist.includes(item.title);
  return <article className="poster-card"><Link className={`poster-art art-${item.mood || 'default'} ${posterPath ? 'has-tmdb-poster' : ''}`} to={`/title/${titleSlug(item.title)}`} aria-label={`View ${item.title}`}><span className="art-kicker">{posterPath ? 'TMDB POSTER' : 'SCREENHUB'}</span>{posterPath && <img className="poster-image" src={`https://image.tmdb.org/t/p/w500${posterPath}`} alt={`${item.title} poster`} loading="lazy" decoding="async" onError={() => setImageFailed(true)} />}<strong>{item.title}</strong><span className="art-genre">{item.genre}</span><span className="art-glow" /></Link><div className="poster-info"><Link to={`/title/${titleSlug(item.title)}`} className="poster-title">{item.title}</Link><span>{item.year} <i>·</i> ★ {item.rating}</span></div>{toggleWatchlist && <button type="button" className={`save-title ${saved ? 'saved' : ''}`} aria-label={saved ? `Remove ${item.title} from My list` : `Add ${item.title} to My list`} onClick={() => toggleWatchlist(item.title)}>{saved ? '✓' : '+'}</button>}</article>;
}

function PosterServiceNotice() {
  const { posters, loading, error, loaded, retry } = useContext(TmdbPostersContext);
  const found = Object.values(posters).filter((path) => typeof path === 'string' && path.startsWith('/')).length;
  if (error) return <div className="poster-service-notice error" role="alert"><span>TMDB posters could not load: {error}</span><button type="button" onClick={retry}>Retry</button></div>;
  if (loading) return <div className="poster-service-notice" role="status">Loading movie posters from TMDB…</div>;
  if (found) return <div className="poster-service-notice success" role="status">{found} movie posters loaded from TMDB.</div>;
  if (loaded) return <div className="poster-service-notice error" role="status">TMDB responded, but no posters were found. Check the function logs and TMDB token.</div>;
  return null;
}

function MovieRow({ title, items, watchlist, toggleWatchlist, link = '/browse' }) {
  return <section className="catalog-section"><div className="section-heading"><h2>{title}</h2><Link to={link}>Explore all <span>→</span></Link></div><div className="poster-row">{items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />)}</div></section>;
}

function Home({ watchlist, toggleWatchlist }) {
  const featured = media[0];
  return <><Header /><PosterServiceNotice /><main className="home-page"><section className={`featured-hero feature-${featured.mood}`}><div className="hero-grain"/><div className="hero-copy"><div className="eyebrow"><span className="live-dot" /> THE STORY EVERYONE'S TALKING ABOUT</div><div className="hero-label">THIS WEEK’S PREMIERE</div><h1>Predator:<br /><em>Badlands</em></h1><p>{featured.description}</p><div className="hero-meta"><span className="rating-pill">★ {featured.rating}</span><span>{featured.year}</span><span>{featured.genre}</span><span>{featured.runtime}</span></div><div className="hero-actions"><Link className="primary-btn hero-btn" to={`/title/${titleSlug(featured.title)}`}>Discover film <span>→</span></Link><button className="quiet-btn" onClick={() => toggleWatchlist(featured.title)}>{watchlist.includes(featured.title) ? '✓ In my list' : '+ Add to my list'}</button></div></div><div className="hero-index"><span>01</span><i /> 04</div></section>
    <div className="home-intro"><div><div className="eyebrow">CURATED FOR YOUR NEXT MOVIE NIGHT</div><h2>Find your <em>kind</em> of story.</h2></div><Link className="all-genres-link" to="/browse">Browse all genres <span>→</span></Link></div><div className="genre-shortcuts">{genres.slice(0, 6).map((genre, index) => <Link key={genre} className={`genre-tile genre-tile-${index}`} to={`/genre/${encodeURIComponent(genre.toLowerCase())}`}><span>0{index + 1}</span><strong>{genre}</strong><i>↗</i></Link>)}</div>
    <MovieRow title="Popular this week" items={media.slice(0, 8)} watchlist={watchlist} toggleWatchlist={toggleWatchlist} /><MovieRow title="Made for the big screen" items={media.filter((item) => ['Adventure', 'Sci-Fi', 'Action'].includes(item.genre)).slice(0, 7)} watchlist={watchlist} toggleWatchlist={toggleWatchlist} /><MovieRow title="Stories after dark" items={media.filter((item) => ['Horror', 'Thriller'].includes(item.genre))} watchlist={watchlist} toggleWatchlist={toggleWatchlist} link="/genre/horror" /><Footer /></main></>;
}

function PageShell({ eyebrow, title, description, children }) { return <><Header /><PosterServiceNotice /><main className="browse-page"><div className="page-heading"><div className="eyebrow">{eyebrow}</div><h1>{title}</h1><p>{description}</p></div>{children}<Footer /></main></>; }

function Browse({ watchlist, toggleWatchlist }) {
  const [genre, setGenre] = useState('All');
  const [query, setQuery] = useState('');
  const [sort, setSort] = useState('popular');
  const visibleMedia = media
    .filter((item) => (genre === 'All' || item.genre === genre) && `${item.title} ${item.genre} ${item.description}`.toLowerCase().includes(query.trim().toLowerCase()))
    .slice()
    .sort((a, b) => sort === 'title' ? a.title.localeCompare(b.title) : sort === 'year' ? b.year - a.year : Number(b.rating) - Number(a.rating));
  return <PageShell eyebrow="THE SCREENHUB GUIDE" title={<>Find your next<br /><em>favorite story.</em></>} description="Search the collection, filter by genre, then open any title for its details.">
    <section className="catalog-section browse-catalog">
      <div className="browse-controls">
        <label className="browse-search"><span aria-hidden="true">⌕</span><input aria-label="Search the catalog" placeholder="Search titles or genres" value={query} onChange={(event) => setQuery(event.target.value)} />{query && <button type="button" onClick={() => setQuery('')} aria-label="Clear search">×</button>}</label>
        <label className="sort-control"><span>Sort by</span><select value={sort} onChange={(event) => setSort(event.target.value)}><option value="popular">Top rated</option><option value="year">Newest</option><option value="title">Title A–Z</option></select></label>
      </div>
      <div className="browse-filters" aria-label="Filter movies by genre">{['All', ...genres].map((item) => <button type="button" key={item} className={genre === item ? 'selected' : ''} aria-pressed={genre === item} onClick={() => setGenre(item)}>{item}</button>)}</div>
      <div className="section-heading browse-results-heading"><h2>{genre === 'All' ? 'All movies' : `${genre} movies`}</h2><span>{visibleMedia.length} {visibleMedia.length === 1 ? 'title' : 'titles'}</span></div>
      <div className="result-grid">{visibleMedia.length ? visibleMedia.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">No movies match these filters. Try a different title or genre.</p>}</div>
    </section>
  </PageShell>;
}

function GenrePage({ watchlist, toggleWatchlist }) {
  const { genre = '' } = useParams(); const name = genres.find((item) => item.toLowerCase() === decodeURIComponent(genre).toLowerCase()); const items = media.filter((item) => item.genre.toLowerCase() === (name || genre).toLowerCase());
  return <PageShell eyebrow="EXPLORE COLLECTION" title={<>{name || 'Genre'} <em>stories.</em></>} description={`A hand-picked collection of ${name || genre} films for your next movie night.`}><section className="result-grid">{items.length ? items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">This collection is coming soon. Explore all genres to find your next film.</p>}</section><Link className="back-to-browse" to="/browse">← All genres</Link></PageShell>;
}

function SearchPage({ watchlist, toggleWatchlist }) {
  const [params, setParams] = useSearchParams(); const query = params.get('q') || ''; const matches = media.filter((item) => `${item.title} ${item.genre} ${item.description}`.toLowerCase().includes(query.trim().toLowerCase()));
  return <PageShell eyebrow="SEARCH THE COLLECTION" title={query ? <>Results for <em>“{query}”</em></> : <>What are you <em>in the mood for?</em></>} description={query ? `${matches.length} ${matches.length === 1 ? 'story' : 'stories'} found in the ScreenHub collection.` : 'Search for a title, genre, or feeling.'}><form className="large-search" onSubmit={(event) => event.preventDefault()}><span>⌕</span><input autoFocus aria-label="Search the collection" placeholder="Try “space”, “animation”, or a title..." value={query} onChange={(event) => setParams(event.target.value ? { q: event.target.value } : {})} /><button type="button" onClick={() => setParams({})}>Clear</button></form>{query && <section className="result-grid">{matches.length ? matches.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">No titles match that search yet. Try a genre, like “Sci-Fi” or “Horror”.</p>}</section>}{!query && <div className="search-genres"><span>POPULAR GENRES</span>{genres.slice(0, 6).map((genre) => <Link key={genre} to={`/genre/${genre.toLowerCase()}`}>{genre} <b>↗</b></Link>)}</div>}</PageShell>;
}

function WatchlistPage({ watchlist, toggleWatchlist }) {
  const items = media.filter((item) => watchlist.includes(item.title));
  return <PageShell eyebrow="SAVED FOR LATER" title={<>Your personal <em>collection.</em></>} description="Every good story you want to come back to, all in one place."><section className="result-grid">{items.length ? items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <div className="watchlist-empty"><span>＋</span><h2>Your list starts here.</h2><p>Save films as you explore and they’ll be waiting for you here.</p><Link className="primary-btn" to="/browse">Explore the collection</Link></div>}</section></PageShell>;
}

function TitlePage({ watchlist, toggleWatchlist, legacySquid = false }) {
  const { slug = '' } = useParams(); const item = legacySquid ? { title: 'Squid Game', genre: 'Thriller', year: 2021, rating: '8.0', runtime: '1 season', mood: 'squid', description: 'Hundreds of cash-strapped players accept a strange invitation to compete in children’s games for a tempting prize. One by one, the stakes turn deadly.' } : media.find((entry) => titleSlug(entry.title) === slug); const saved = item && watchlist.includes(item.title); const { posters } = useContext(TmdbPostersContext); const [imageFailed, setImageFailed] = useState(false); const posterPath = item && !imageFailed && posters[item.title];
  if (!item) return <PageShell eyebrow="SCREENHUB" title="Story not found." description="That title isn’t in our collection yet."><Link to="/browse">Explore the collection →</Link></PageShell>;
  return <><Header /><main className="title-page"><Link to="/browse" className="back-link detail-back">← Back to discover</Link><section className="title-detail-hero"><div className={`detail-poster art-${item.mood || 'default'} ${posterPath ? 'has-tmdb-poster' : ''}`}>{posterPath && <img src={`https://image.tmdb.org/t/p/w500${posterPath}`} alt={`${item.title} poster`} onError={() => setImageFailed(true)} />}<span>{item.title}</span></div><div className="title-copy"><div className="eyebrow">MOVIE DETAILS</div><h1>{item.title}</h1><div className="detail-tags"><span>{item.year}</span><span>{item.genre}</span><span>{item.runtime}</span><span className="rating-pill">★ {item.rating}</span></div><p>{item.description}</p><div className="hero-actions"><a className="primary-btn" href={`https://www.youtube.com/results?search_query=${encodeURIComponent(`${item.title} official trailer`)}`} target="_blank" rel="noreferrer">▶ Find trailer</a><button className="quiet-btn" onClick={() => toggleWatchlist(item.title)}>{saved ? '✓ Saved to my list' : '+ Add to my list'}</button></div></div></section><section className="title-details"><div><span>ABOUT THIS TITLE</span><h2>A story worth settling in for.</h2><p>{item.description}</p></div><Link className="detail-genre" to={`/genre/${encodeURIComponent(item.genre.toLowerCase())}`}>More {item.genre} films <b>Explore collection →</b></Link></section><MovieRow title="You may also like" items={media.filter((other) => other.title !== item.title && other.genre === item.genre).slice(0, 6)} watchlist={watchlist} toggleWatchlist={toggleWatchlist} /><Footer /></main></>;
}

function Footer() { return <><div className="tmdb-attribution"><a href="https://www.themoviedb.org/" target="_blank" rel="noreferrer"><img src="https://www.themoviedb.org/assets/2/v4/logos/stacked-green.svg" alt="The Movie Database (TMDB)" /></a><p>This application uses TMDB and the TMDB APIs but is not endorsed, certified, or otherwise approved by TMDB.</p></div><footer className="site-footer"><Link className="wordmark" to="/home">SCREEN<span>HUB</span></Link><span>Good stories bring us together.</span><Link to="/profile">Your account ↗</Link></footer></>; }

function Profile() {
  const { profile, session, setProfile } = useAuth(); const navigate = useNavigate(); const [username, setUsername] = useState(profile?.username ?? ''); const [avatar, setAvatar] = useState(profile?.avatar ?? ''); const [characterQuery, setCharacterQuery] = useState(''); const [characters, setCharacters] = useState([]); const [searching, setSearching] = useState(false); const [searchError, setSearchError] = useState(''); const [password, setPassword] = useState(''); const [notice, setNotice] = useState(''); const [error, setError] = useState(''); const [busy, setBusy] = useState(false);
  useEffect(() => { setUsername(profile?.username ?? ''); setAvatar(profile?.avatar ?? ''); }, [profile]);
  useEffect(() => {
    const query = characterQuery.trim();
    if (query.length < 2) { setCharacters([]); setSearchError(''); return undefined; }
    const controller = new AbortController();
    const timer = window.setTimeout(async () => {
      setSearching(true); setSearchError('');
      try {
        const response = await fetch(`https://api.tenrai.org/v1/characters?q=${encodeURIComponent(query)}&limit=12`, { signal: controller.signal });
        if (!response.ok) throw new Error(response.status === 429 ? 'Too many searches right now. Wait a few seconds and try again.' : `Character search is temporarily unavailable (HTTP ${response.status}). Please try again.`);
        const result = await response.json();
        const choices = (result.data ?? []).map((character) => ({
          id: character.mal_id,
          name: character.name,
          image: character.images?.webp?.image_url || character.images?.jpg?.image_url || character.images?.jpg?.small_image_url,
        })).filter((character) => character.image);
        setCharacters(choices);
      } catch (requestError) {
        if (requestError.name !== 'AbortError') setSearchError(requestError instanceof TypeError ? 'Could not connect to the character search service. Check your internet connection and try again.' : (requestError.message || 'Could not load anime characters.'));
      } finally {
        if (!controller.signal.aborted) setSearching(false);
      }
    }, 500);
    return () => { window.clearTimeout(timer); controller.abort(); };
  }, [characterQuery]);
  async function save(event) {
    event.preventDefault(); setNotice(''); setError(''); setBusy(true);
    const { error: profileError } = await supabase.from('profiles').update({ username: username.trim(), avatar: avatar || null }).eq('id', session.user.id);
    if (profileError) { setError(profileError.code === '23505' ? 'That username is already taken.' : profileError.message); setBusy(false); return; }
    let passwordError;
    if (password) ({ error: passwordError } = await supabase.auth.updateUser({ password }));
    setBusy(false);
    if (passwordError) setError(passwordError.message); else { setProfile({ ...profile, username: username.trim(), avatar }); setPassword(''); setNotice('Your profile has been saved.'); }
  }
  return <><Header /><main className="profile-page"><Link className="back-link" to="/home">← Back to ScreenHub</Link><div className="profile-panel"><div className="eyebrow">ACCOUNT</div><h1>Your profile</h1><p className="muted">Manage your username, anime avatar, and sign-in details.</p><form className="form" onSubmit={save}><Field label="Username" value={username} onChange={setUsername} minLength={3} maxLength={30} required /><div className="anime-avatar-picker"><label className="field"><span>Find an anime character</span><input type="search" value={characterQuery} onChange={(event) => setCharacterQuery(event.target.value)} placeholder="Try Naruto, Gojo, or Luffy" /></label><p className="avatar-help">Type at least two characters. Choose a result to preview it in your profile menu.</p>{searching && <p className="avatar-help">Searching characters…</p>}{searchError && <p className="form-error">{searchError}</p>}{characters.length > 0 && <div className="anime-avatar-grid">{characters.map((character) => <button type="button" key={character.id} className={avatar === character.image ? 'anime-avatar-choice selected' : 'anime-avatar-choice'} onClick={() => setAvatar(character.image)} aria-pressed={avatar === character.image}><img src={character.image} alt={character.name} loading="lazy" /><span>{character.name}</span></button>)}</div>}{avatar && <button className="clear-avatar" type="button" onClick={() => setAvatar('')}>Use initials instead</button>}<small className="avatar-credit">Character data via <a href="https://tenrai.org/" target="_blank" rel="noreferrer">Tenrai</a>, a free unofficial MyAnimeList API.</small></div><Field label="New password (leave blank to keep current)" type="password" value={password} onChange={setPassword} minLength={8} /><div className="profile-email"><span>Email</span><strong>{session?.user?.email}</strong><small>Email changes are managed through Supabase Auth.</small></div>{error && <p className="form-error">{error}</p>}{notice && <p className="form-success">{notice}</p>}<button className="primary-btn" disabled={busy}>{busy ? 'Saving…' : 'Save changes'}</button></form><button className="signout-link" onClick={async () => { await supabase.auth.signOut(); navigate('/'); }}>Sign out</button></div></main></>;
}

function Series() {
  return <><Header /><main className="series-page"><div className="series-hero"><div className="hero-shade" /><div className="series-copy"><div className="eyebrow">#1 ON SCREENHUB</div><h1>SQUID<br />GAME</h1><p>2021 – 2024　·　TV-MA　·　Thriller · Drama · Mystery</p><p>456 cash-strapped players accept a mysterious invitation to compete in deadly children's games for a life-changing prize.</p><a className="primary-btn hero-btn" href="https://www.youtube.com/results?search_query=Squid+Game+official+trailer" target="_blank" rel="noreferrer">▶ &nbsp;Play trailer</a></div></div><section className="catalog-section"><div className="section-heading"><h2>More to discover</h2><Link to="/home">Browse all</Link></div><div className="poster-row">{media.slice(0, 6).map((item) => <div className="poster-card" key={item.title}><div className="poster-placeholder"><span>{item.title}</span></div><span>{item.title}</span></div>)}</div></section></main></>;
}

export default App;
