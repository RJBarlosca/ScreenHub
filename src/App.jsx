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

const genres = ['Adventure', 'Comedy', 'Drama', 'Thriller', 'Mystery', 'Horror', 'Romance', 'Crime'];
const titleSlug = (title) => title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
const FreeMoviesContext = createContext({ movies: [], loading: false, error: '', loaded: false, retry: () => {} });
const freeMoviesCacheKey = 'screenhub-free-movies-v2';
const freeMoviesCacheDuration = 12 * 60 * 60 * 1000;
const openFilmLicenses = new Set([
  'https://creativecommons.org/licenses/by/3.0/',
  'https://creativecommons.org/licenses/by/3.0/us/',
  'https://creativecommons.org/licenses/by/4.0/',
  'https://creativecommons.org/publicdomain/mark/1.0/',
]);
let pendingFreeMoviesRequest;

function isValidFreeMovie(movie) {
  return movie &&
    typeof movie.identifier === 'string' &&
    typeof movie.title === 'string' &&
    typeof movie.description === 'string' &&
    typeof movie.embed_url === 'string' &&
    movie.embed_url === `https://archive.org/embed/${encodeURIComponent(movie.identifier)}` &&
    openFilmLicenses.has(movie.license_url) &&
    typeof movie.license_name === 'string' &&
    typeof movie.creator === 'string' &&
    movie.creator.length > 0 &&
    typeof movie.genre === 'string' &&
    Array.isArray(movie.genres) &&
    movie.genres.every((genre) => typeof genre === 'string') &&
    Number.isInteger(movie.year) &&
    typeof movie.runtime === 'string';
}

function readFreeMoviesCache() {
  try {
    const cached = JSON.parse(localStorage.getItem(freeMoviesCacheKey) || 'null');
    if (
      !cached ||
      !Number.isFinite(cached.savedAt) ||
      cached.savedAt > Date.now() ||
      Date.now() - cached.savedAt > freeMoviesCacheDuration ||
      !Array.isArray(cached.movies)
    ) return [];
    return cached.movies.filter(isValidFreeMovie).map(toOpenCatalogMovie);
  } catch (error) {
    console.warn('Unable to read cached free movie catalog.', error);
    return [];
  }
}

function toOpenCatalogMovie(movie) {
  const posterUrl = `https://archive.org/services/img/${encodeURIComponent(movie.identifier)}`;
  return {
    id: movie.identifier,
    archiveId: movie.identifier,
    title: movie.title,
    genre: movie.genre,
    genres: movie.genres,
    year: movie.year,
    rating: 'Free',
    runtime: movie.runtime,
    description: movie.description,
    mood: 'open-movie',
    posterPath: posterUrl,
    backdropPath: posterUrl,
    embedUrl: movie.embed_url,
    creator: movie.creator,
    licenseUrl: movie.license_url,
    licenseName: movie.license_name,
  };
}

async function invokeFreeMoviesLookup() {
  const { data, error } = await supabase.functions.invoke('tmdb-posters', { body: { action: 'free-movies' } });
  if (error) {
    const response = error.context;
    if (response instanceof Response) {
      const details = await response.clone().json().catch(() => null);
      throw new Error(details?.error || `${error.message} (HTTP ${response.status})`);
    }
    throw error;
  }
  if (!Array.isArray(data?.movies)) throw new Error('The free movie catalog returned an invalid response.');
  return data.movies.filter(isValidFreeMovie);
}

function FreeMoviesProvider({ children }) {
  const { session } = useAuth();
  const [movies, setMovies] = useState(readFreeMoviesCache);
  const [loading, setLoading] = useState(movies.length === 0);
  const [error, setError] = useState('');
  const [loaded, setLoaded] = useState(movies.length > 0);
  const [retryCount, setRetryCount] = useState(0);

  useEffect(() => {
    if (!session?.user?.id || !supabase) return undefined;
    let active = true;
    setLoading(true);
    setError('');
    if (!pendingFreeMoviesRequest) {
      pendingFreeMoviesRequest = invokeFreeMoviesLookup().finally(() => { pendingFreeMoviesRequest = undefined; });
    }
    pendingFreeMoviesRequest.then((result) => {
      if (!active) return;
      const mapped = result.map(toOpenCatalogMovie);
      setMovies(mapped);
      setLoaded(true);
      try {
        localStorage.setItem(freeMoviesCacheKey, JSON.stringify({ savedAt: Date.now(), movies: result }));
      } catch (cacheError) {
        console.warn('Unable to cache free movie catalog.', cacheError);
      }
    }).catch((cause) => {
      if (!active) return;
      console.error('Unable to load free full movies.', cause);
      setError(cause instanceof Error ? cause.message : 'An unexpected error occurred.');
    }).finally(() => {
      if (active) setLoading(false);
    });
    return () => { active = false; };
  }, [session?.user?.id, retryCount]);

  const retry = () => {
    setError('');
    setRetryCount((count) => count + 1);
  };

  return <FreeMoviesContext.Provider value={{ movies, loading, error, loaded, retry }}>{children}</FreeMoviesContext.Provider>;
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
  return <AuthProvider><SetupNotice /><FreeMoviesProvider><Routes>
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
    <Route path="*" element={<Navigate to="/" replace />} />
  </Routes></FreeMoviesProvider></AuthProvider>;
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
  const { profile } = useAuth(); const navigate = useNavigate(); const location = useLocation(); const [menu, setMenu] = useState(false); const [search, setSearch] = useState(new URLSearchParams(location.search).get('q') || '');
  useEffect(() => setSearch(new URLSearchParams(location.search).get('q') || ''), [location.search]);
  async function signOut() { await supabase.auth.signOut(); navigate('/'); }
  function submitSearch(event) { event.preventDefault(); navigate(`/search?q=${encodeURIComponent(search.trim())}`); }
  const initials = `${profile?.first_name?.[0] ?? ''}${profile?.last_name?.[0] ?? ''}`.toUpperCase() || profile?.username?.[0]?.toUpperCase() || 'S';
  return <header className="site-header"><Link className="wordmark" to="/home">SCREEN<span>HUB</span></Link><nav className="main-nav"><Link className={location.pathname === '/home' ? 'active' : ''} to="/home">Home</Link><Link className={location.pathname === '/browse' ? 'active' : ''} to="/browse">Discover</Link><Link className={location.pathname === '/watchlist' ? 'active' : ''} to="/watchlist">My list</Link></nav><form className="header-search" onSubmit={submitSearch}><span>⌕</span><input aria-label="Search free films" value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Titles, genres, moods..." /><kbd>↵</kbd></form><div className="header-user"><button className="avatar" onClick={() => setMenu(!menu)} aria-label="Open account menu">{initials}{profile?.avatar && <img src={profile.avatar} alt="" onError={(event) => { event.currentTarget.style.display = 'none'; }} />}</button>{menu && <div className="account-menu"><b>{[profile?.first_name, profile?.last_name].filter(Boolean).join(' ') || 'Your account'}</b><span>@{profile?.username}</span><Link to="/profile" onClick={() => setMenu(false)}>Edit profile</Link><button onClick={signOut}>Sign out</button></div>}</div></header>;
}

function PosterCard({ item, watchlist = [], toggleWatchlist }) {
  const [imageFailed, setImageFailed] = useState(false);
  const posterPath = imageFailed ? null : item.posterPath;
  const saved = watchlist.includes(item.title);
  return <article className="poster-card"><Link className={`poster-art art-${item.mood || 'default'} ${posterPath ? 'has-poster' : ''}`} to={`/title/${titleSlug(item.title)}`} aria-label={`Watch ${item.title}`}><span className="art-kicker">{item.licenseName?.startsWith('Public Domain Mark') ? 'ARCHIVE: PUBLIC DOMAIN MARK' : 'OPEN LICENSE'}</span>{posterPath && <img className="poster-image" src={posterPath} alt={`${item.title} poster`} loading="lazy" decoding="async" onError={() => setImageFailed(true)} />}<strong>{item.title}</strong><span className="art-genre">{item.genre}</span><span className="art-glow" /></Link><div className="poster-info"><Link to={`/title/${titleSlug(item.title)}`} className="poster-title">{item.title}</Link><span>{item.year} <i>·</i> {item.runtime} · Free</span></div>{toggleWatchlist && <button type="button" className={`save-title ${saved ? 'saved' : ''}`} aria-label={saved ? `Remove ${item.title} from My list` : `Add ${item.title} to My list`} onClick={() => toggleWatchlist(item.title)}>{saved ? '✓' : '+'}</button>}</article>;
}

function PosterServiceNotice() {
  const { movies, loading, error, loaded, retry } = useContext(FreeMoviesContext);
  if (error) return <div className="poster-service-notice error" role="alert"><span>Classic films could not load: {error}</span><button type="button" onClick={retry}>Retry</button></div>;
  if (loading) return <div className="poster-service-notice" role="status">Checking Archive rights labels and full-video availability…</div>;
  if (movies.length) return <div className="poster-service-notice success" role="status">{movies.length} classic features available from Internet Archive · copyright status may vary by country</div>;
  if (loaded) return <div className="poster-service-notice" role="status">No classic films are available right now. Please try again.</div>;
  return null;
}

function MovieRow({ title, items, watchlist, toggleWatchlist, link = '/browse' }) {
  return <section className="catalog-section"><div className="section-heading"><h2>{title}</h2><Link to={link}>Explore all <span>→</span></Link></div><div className="poster-row">{items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />)}</div></section>;
}

function TrendingHero({ movies, loading, error, watchlist, toggleWatchlist }) {
  const [activeIndex, setActiveIndex] = useState(0);
  const [touchStartX, setTouchStartX] = useState(null);
  if (!movies.length) return <section className="featured-hero trending-hero-empty" aria-live="polite"><div className="hero-grain" /><div className="hero-copy"><div className="eyebrow">CLASSIC FULL-LENGTH FILMS</div><h1>{loading ? 'Checking full-feature films…' : 'Classic films are unavailable'}</h1><p>{error || 'No classic features are currently available from the source. Please try again shortly.'}</p></div></section>;
  const slideIndex = activeIndex % movies.length;
  const featured = movies[slideIndex];
  const heroBackdrop = featured.backdropPath || featured.posterPath;
  const moveSlide = (direction) => setActiveIndex((current) => (current + direction + movies.length) % movies.length);
  const endSwipe = (event) => {
    if (touchStartX === null) return;
    const distance = event.changedTouches[0].clientX - touchStartX;
    if (Math.abs(distance) > 45) moveSlide(distance < 0 ? 1 : -1);
    setTouchStartX(null);
  };
  return <section className={`featured-hero trending-hero feature-${featured.genre.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`} onTouchStart={(event) => setTouchStartX(event.changedTouches[0].clientX)} onTouchEnd={endSwipe} aria-roledescription="carousel" aria-label="Classic full-length films">
    {heroBackdrop && <img className="feature-backdrop" src={heroBackdrop} alt="" aria-hidden="true" />}
    <div className="hero-grain" />
    <div className="hero-copy" aria-live="polite"><div className="eyebrow"><span className="live-dot" /> CLASSIC FULL-LENGTH FILMS</div><div className="hero-label">ARCHIVE LISTING · {slideIndex + 1} OF {movies.length}</div><h1>{featured.title}</h1><p>{featured.description}</p><div className="hero-meta"><span className="rating-pill">FREE</span><span>{featured.year}</span><span>{featured.runtime}</span></div><div className="hero-actions"><Link className="primary-btn hero-btn" to={`/title/${titleSlug(featured.title)}`}>Watch full movie <span>→</span></Link><button className="quiet-btn" onClick={() => toggleWatchlist(featured.title)}>{watchlist.includes(featured.title) ? '✓ In my list' : '+ Add to my list'}</button></div></div>
    <div className="hero-carousel-controls"><button type="button" onClick={() => moveSlide(-1)} aria-label="Previous free movie">←</button><div className="hero-carousel-dots">{movies.map((movie, index) => <button type="button" key={movie.id} className={index === slideIndex ? 'selected' : ''} onClick={() => setActiveIndex(index)} aria-label={`Show free movie ${index + 1}: ${movie.title}`} aria-current={index === slideIndex ? 'true' : undefined} />)}</div><button type="button" onClick={() => moveSlide(1)} aria-label="Next free movie">→</button></div>
  </section>;
}

function Home({ watchlist, toggleWatchlist }) {
  const { movies, loading, error } = useContext(FreeMoviesContext);
  const usedGenreArtwork = new Set();
  return <><Header /><PosterServiceNotice /><main className="home-page"><TrendingHero movies={movies} loading={loading} error={error} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />
    <div className="home-intro"><div><div className="eyebrow">CLASSIC FEATURES · FREE TO WATCH</div><h2>Find your <em>kind</em> of story.</h2></div><Link className="all-genres-link" to="/browse">Browse classic films <span>→</span></Link></div><div className="genre-shortcuts">{genres.slice(0, 6).map((genre, index) => {
      const candidates = movies.filter((movie) => movie.genres.includes(genre)).sort((a, b) => Number(b.genre === genre) - Number(a.genre === genre));
      const genreMovie = candidates.find((movie) => !usedGenreArtwork.has(movie.backdropPath || movie.posterPath)) || candidates[0];
      const image = genreMovie && (genreMovie.backdropPath || genreMovie.posterPath);
      if (image) usedGenreArtwork.add(image);
      const style = image ? { '--genre-image': `url("${image}")` } : undefined;
      return <Link key={genre} className={`genre-tile genre-tile-${index}`} style={style} to={`/genre/${encodeURIComponent(genre.toLowerCase())}`}><span>0{index + 1}</span><strong>{genre}</strong><i>↗</i></Link>;
    })}</div>
    <MovieRow title="Classic full-length movies" items={movies} watchlist={watchlist} toggleWatchlist={toggleWatchlist} /><Footer /></main></>;
}

function PageShell({ eyebrow, title, description, children }) { return <><Header /><PosterServiceNotice /><main className="browse-page"><div className="page-heading"><div className="eyebrow">{eyebrow}</div><h1>{title}</h1><p>{description}</p></div>{children}<Footer /></main></>; }

function Browse({ watchlist, toggleWatchlist }) {
  const { movies } = useContext(FreeMoviesContext);
  const [genre, setGenre] = useState('All');
  const [query, setQuery] = useState('');
  const [sort, setSort] = useState('featured');
  const visibleMedia = movies
    .filter((item) => (genre === 'All' || item.genre === genre) && `${item.title} ${item.genre} ${item.description}`.toLowerCase().includes(query.trim().toLowerCase()))
    .slice()
    .sort((a, b) => sort === 'title' ? a.title.localeCompare(b.title) : sort === 'year' ? b.year - a.year : 0);
  return <PageShell eyebrow="THE SCREENHUB GUIDE" title={<>Find your next<br /><em>favorite story.</em></>} description="Search the collection, filter by genre, then open any title for its details.">
    <section className="catalog-section browse-catalog">
      <div className="browse-controls">
        <label className="browse-search"><span aria-hidden="true">⌕</span><input aria-label="Search the catalog" placeholder="Search titles or genres" value={query} onChange={(event) => setQuery(event.target.value)} />{query && <button type="button" onClick={() => setQuery('')} aria-label="Clear search">×</button>}</label>
        <label className="sort-control"><span>Sort by</span><select value={sort} onChange={(event) => setSort(event.target.value)}><option value="featured">Featured</option><option value="year">Newest</option><option value="title">Title A–Z</option></select></label>
      </div>
      <div className="browse-filters" aria-label="Filter movies by genre">{['All', ...genres].map((item) => <button type="button" key={item} className={genre === item ? 'selected' : ''} aria-pressed={genre === item} onClick={() => setGenre(item)}>{item}</button>)}</div>
      <div className="section-heading browse-results-heading"><h2>{genre === 'All' ? 'All movies' : `${genre} movies`}</h2><span>{visibleMedia.length} {visibleMedia.length === 1 ? 'title' : 'titles'}</span></div>
      <div className="result-grid">{visibleMedia.length ? visibleMedia.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">No movies match these filters. Try a different title or genre.</p>}</div>
    </section>
  </PageShell>;
}

function GenrePage({ watchlist, toggleWatchlist }) {
  const { genre = '' } = useParams(); const { movies } = useContext(FreeMoviesContext); const name = genres.find((item) => item.toLowerCase() === decodeURIComponent(genre).toLowerCase()); const items = movies.filter((item) => item.genres.some((itemGenre) => itemGenre.toLowerCase() === (name || genre).toLowerCase()));
  return <PageShell eyebrow="EXPLORE COLLECTION" title={<>{name || 'Genre'} <em>stories.</em></>} description={`A hand-picked collection of ${name || genre} films for your next movie night.`}><section className="result-grid">{items.length ? items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">This collection is coming soon. Explore all genres to find your next film.</p>}</section><Link className="back-to-browse" to="/browse">← All genres</Link></PageShell>;
}

function SearchPage({ watchlist, toggleWatchlist }) {
  const { movies } = useContext(FreeMoviesContext); const [params, setParams] = useSearchParams(); const query = params.get('q') || ''; const matches = movies.filter((item) => `${item.title} ${item.genres.join(' ')} ${item.description}`.toLowerCase().includes(query.trim().toLowerCase()));
  return <PageShell eyebrow="SEARCH THE COLLECTION" title={query ? <>Results for <em>“{query}”</em></> : <>What are you <em>in the mood for?</em></>} description={query ? `${matches.length} ${matches.length === 1 ? 'story' : 'stories'} found in the ScreenHub collection.` : 'Search for a title, genre, or feeling.'}><form className="large-search" onSubmit={(event) => event.preventDefault()}><span>⌕</span><input autoFocus aria-label="Search the collection" placeholder="Try “mystery”, “horror”, or a title..." value={query} onChange={(event) => setParams(event.target.value ? { q: event.target.value } : {})} /><button type="button" onClick={() => setParams({})}>Clear</button></form>{query && <section className="result-grid">{matches.length ? matches.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <p className="empty-state">No titles match that search yet. Try a genre, like “Thriller” or “Horror”.</p>}</section>}{!query && <div className="search-genres"><span>POPULAR GENRES</span>{genres.slice(0, 6).map((genre) => <Link key={genre} to={`/genre/${genre.toLowerCase()}`}>{genre} <b>↗</b></Link>)}</div>}</PageShell>;
}

function WatchlistPage({ watchlist, toggleWatchlist }) {
  const { movies } = useContext(FreeMoviesContext);
  const items = movies.filter((item) => watchlist.includes(item.title));
  return <PageShell eyebrow="SAVED FOR LATER" title={<>Your personal <em>collection.</em></>} description="Every good story you want to come back to, all in one place."><section className="result-grid">{items.length ? items.map((item) => <PosterCard key={item.title} item={item} watchlist={watchlist} toggleWatchlist={toggleWatchlist} />) : <div className="watchlist-empty"><span>＋</span><h2>Your list starts here.</h2><p>Save films as you explore and they’ll be waiting for you here.</p><Link className="primary-btn" to="/browse">Explore the collection</Link></div>}</section></PageShell>;
}

function TitlePage({ watchlist, toggleWatchlist }) {
  const { slug = '' } = useParams(); const { movies, loading, error } = useContext(FreeMoviesContext); const item = movies.find((entry) => titleSlug(entry.title) === slug); const saved = item && watchlist.includes(item.title); const [imageFailed, setImageFailed] = useState(false); const posterPath = item && !imageFailed && item.posterPath;
  if (!item && loading) return <PageShell eyebrow="CLASSIC FEATURES" title="Checking the catalog…" description="Checking the Archive listing and full-video availability." />;
  if (!item) return <PageShell eyebrow="CLASSIC FEATURES" title="Movie not found." description={error || 'That title is not currently available from the Archive source.'}><Link to="/browse">Explore classic films →</Link></PageShell>;
  return <><Header /><main className="title-page"><Link to="/browse" className="back-link detail-back">← Back to classic films</Link><section className="title-detail-hero"><div className={`detail-poster art-${item.mood || 'default'} ${posterPath ? 'has-poster' : ''}`}>{posterPath && <img src={posterPath} alt={`${item.title} poster`} onError={() => setImageFailed(true)} />}<span>{item.title}</span></div><div className="title-copy"><div className="eyebrow">CLASSIC · FULL MOVIE</div><h1>{item.title}</h1><div className="detail-tags"><span>{item.year}</span><span>{item.genre}</span><span>{item.runtime}</span><span className="rating-pill">FREE TO WATCH</span></div><p>{item.description}</p><div className="hero-actions"><button className="primary-btn" type="button" onClick={() => document.getElementById('free-movie-player')?.scrollIntoView({ behavior: 'smooth', block: 'center' })}>▶ Watch full movie</button><button className="quiet-btn" onClick={() => toggleWatchlist(item.title)}>{saved ? '✓ Saved to my list' : '+ Add to my list'}</button></div></div></section><FreeMoviePlayer movie={item} /><section className="title-details"><div><span>FILM DETAILS</span><h2>{item.title}</h2><p>{item.description}</p><p>Director: {item.creator}. Internet Archive displays a Public Domain Mark for this item; this is a source label, not proof of public-domain status in every country.</p></div><a className="detail-genre" href={item.licenseUrl} target="_blank" rel="noreferrer">About the Public Domain Mark <b>↗</b></a></section><MovieRow title="More classic full-length movies" items={movies.filter((other) => other.id !== item.id)} watchlist={watchlist} toggleWatchlist={toggleWatchlist} /><Footer /></main></>;
}

function FreeMoviePlayer({ movie }) {
  const [playing, setPlaying] = useState(false);
  return <section className="movie-viewing-options free-movie-player-section" id="free-movie-player" aria-labelledby="free-movie-heading">
    <div className="viewing-options-copy"><div className="eyebrow">CLASSIC FEATURE · INTERNET ARCHIVE SOURCE</div><h2 id="free-movie-heading">Watch {movie.title}</h2><p>Internet Archive currently lists this item with a Public Domain Mark. That label is not a legal determination, and copyright status can vary by country. Playback is provided by the source; ScreenHub does not host the video.</p></div>
    <div className="viewing-actions"><button className="primary-btn" type="button" onClick={() => setPlaying((current) => !current)}>{playing ? 'Close player' : '▶ Play full movie'}</button><a className="quiet-btn" href={`https://archive.org/details/${encodeURIComponent(movie.archiveId)}`} target="_blank" rel="noreferrer">Open source ↗</a></div>
    {playing && <div className="archive-player"><iframe src={`${movie.embedUrl}?autoplay=1`} title={`Play the complete movie ${movie.title}`} allow="autoplay; fullscreen; picture-in-picture" allowFullScreen referrerPolicy="strict-origin-when-cross-origin" /></div>}
    <p className="license-disclaimer">“{movie.title}” directed by {movie.creator}. <a href={movie.licenseUrl} target="_blank" rel="noreferrer">About the Public Domain Mark</a> · <a href={`https://archive.org/details/${encodeURIComponent(movie.archiveId)}`} target="_blank" rel="noreferrer">View source record</a>.</p>
  </section>;
}

function Footer() { return <><div className="source-attribution"><a href="https://archive.org/" target="_blank" rel="noreferrer" aria-label="Visit Internet Archive">INTERNET ARCHIVE</a><p>Films are embedded from Internet Archive. Its Public Domain Mark is an informational label, not a legal guarantee; copyright and availability can vary by country. Check the source record and local rules.</p></div><footer className="site-footer"><Link className="wordmark" to="/home">SCREEN<span>HUB</span></Link><span>Good stories bring us together.</span><Link to="/profile">Your account ↗</Link></footer></>; }

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

export default App;
