const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
  'Content-Type': 'application/json',
};

type MovieRequest = { title: string; year?: number; featured?: boolean };
type MovieSearchResult = {
  id: number;
  title: string;
  original_title: string;
  release_date?: string;
  genre_ids: number[];
  vote_average: number;
  overview: string;
  poster_path: string | null;
  backdrop_path: string | null;
  featured_video_key?: string | null;
};
type MovieVideo = { key: string; site: string; type: string; official: boolean; published_at?: string };
type TrendingRequest = { action?: string; titles?: unknown };

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: corsHeaders });
}

function normalizeTitle(title: string) {
  return title.normalize('NFKD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().replace(/[^a-z0-9]+/g, '');
}

Deno.serve(async (request) => {
  if (request.method === 'OPTIONS') return new Response('ok', { headers: corsHeaders });
  if (request.method !== 'POST') return jsonResponse({ error: 'Method not allowed.' }, 405);

  const token = Deno.env.get('TMDB_API_READ_ACCESS_TOKEN');
  if (!token) return jsonResponse({ error: 'TMDB_API_READ_ACCESS_TOKEN is not configured for this Edge Function.' }, 500);

  let payload: TrendingRequest;
  try {
    payload = await request.json();
  } catch {
    return jsonResponse({ error: 'Request body must be valid JSON.' }, 400);
  }

  if (payload?.action === 'trending') {
    const trendingUrl = new URL('https://api.themoviedb.org/3/trending/movie/week');
    trendingUrl.searchParams.set('language', 'en-US');
    trendingUrl.searchParams.set('page', '1');
    let response: Response;
    try {
      response = await fetch(trendingUrl, {
        headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
      });
    } catch (error) {
      console.error('TMDB trending lookup failed.', error);
      return jsonResponse({ error: 'Unable to connect to TMDB for trending movies.' }, 502);
    }
    if (!response.ok) {
      console.error(`TMDB trending lookup returned HTTP ${response.status}.`);
      return jsonResponse({ error: `TMDB trending lookup returned HTTP ${response.status}.` }, 502);
    }
    const data = await response.json();
    const trending: MovieSearchResult[] = (Array.isArray(data.results) ? data.results : [])
      .filter((movie: MovieSearchResult) =>
        movie &&
        Number.isInteger(movie.id) &&
        typeof movie.title === 'string' &&
        Array.isArray(movie.genre_ids) &&
        (typeof movie.backdrop_path === 'string' || typeof movie.poster_path === 'string')
      )
      .slice(0, 10);
    let nextIndex = 0;
    const addTrailer = async () => {
      while (nextIndex < trending.length) {
        const index = nextIndex++;
        const movie = trending[index];
        const videosUrl = new URL(`https://api.themoviedb.org/3/movie/${movie.id}/videos`);
        videosUrl.searchParams.set('language', 'en-US');
        try {
          const videoResponse = await fetch(videosUrl, {
            headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
          });
          if (!videoResponse.ok) {
            console.warn(`TMDB trailer lookup returned HTTP ${videoResponse.status} for trending movie ${movie.id}.`);
            continue;
          }
          const videoData = await videoResponse.json();
          const videos: MovieVideo[] = Array.isArray(videoData.results) ? videoData.results : [];
          const trailer = videos
            .filter((video) => video.site === 'YouTube' && ['Trailer', 'Teaser'].includes(video.type) && /^[\w-]{11}$/.test(video.key))
            .sort((a, b) => Number(b.official) - Number(a.official) || Number(b.type === 'Trailer') - Number(a.type === 'Trailer'))[0];
          trending[index] = { ...movie, featured_video_key: trailer?.key ?? null };
        } catch (error) {
          console.warn(`TMDB trailer lookup failed for trending movie ${movie.id}.`, error);
        }
      }
    };
    await Promise.all(Array.from({ length: Math.min(4, trending.length) }, addTrailer));
    return jsonResponse({ trending });
  }

  if (!payload || !Array.isArray(payload.titles) || payload.titles.length < 1 || payload.titles.length > 25) {
    return jsonResponse({ error: 'Provide between 1 and 25 movie titles.' }, 400);
  }

  const titles: MovieRequest[] = [];
  for (const item of payload.titles) {
    if (!item || typeof item.title !== 'string' || !item.title.trim() || item.title.length > 120) {
      return jsonResponse({ error: 'Each movie must have a title between 1 and 120 characters.' }, 400);
    }
    if (item.year !== undefined && (!Number.isInteger(item.year) || item.year < 1888 || item.year > new Date().getFullYear() + 10)) {
      return jsonResponse({ error: `Invalid release year for ${item.title}.` }, 400);
    }
    if (item.featured !== undefined && typeof item.featured !== 'boolean') {
      return jsonResponse({ error: `Invalid featured flag for ${item.title}.` }, 400);
    }
    titles.push({ title: item.title.trim(), year: item.year, featured: item.featured });
  }

  const movies: Record<string, MovieSearchResult | null> = {};
  let nextIndex = 0;

  const lookupNext = async () => {
    while (nextIndex < titles.length) {
      const movie = titles[nextIndex++];
      const search = new URL('https://api.themoviedb.org/3/search/movie');
      search.searchParams.set('query', movie.title);
      search.searchParams.set('language', 'en-US');
      search.searchParams.set('include_adult', 'false');
      if (movie.year) search.searchParams.set('primary_release_year', String(movie.year));

      let response = await fetch(search, {
        headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
      });
      if (!response.ok) throw new Error(`TMDB returned HTTP ${response.status}.`);
      let data = await response.json();
      let results: MovieSearchResult[] = Array.isArray(data.results) ? data.results : [];
      const wanted = normalizeTitle(movie.title);
      let match = results.find((result) =>
        [result.title, result.original_title].some((title) => typeof title === 'string' && normalizeTitle(title) === wanted)
      );

      if (!match && movie.year) {
        search.searchParams.delete('primary_release_year');
        response = await fetch(search, {
          headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
        });
        if (!response.ok) throw new Error(`TMDB returned HTTP ${response.status}.`);
        data = await response.json();
        results = Array.isArray(data.results) ? data.results : [];
        match = results.find((result) =>
          [result.title, result.original_title].some((title) => typeof title === 'string' && normalizeTitle(title) === wanted)
        );
      }

      if (match && movie.featured) {
        const videosUrl = new URL(`https://api.themoviedb.org/3/movie/${match.id}/videos`);
        videosUrl.searchParams.set('language', 'en-US');
        try {
          const videoResponse = await fetch(videosUrl, {
            headers: { Authorization: 'Bearer ' + token, Accept: 'application/json' },
          });
          if (!videoResponse.ok) {
            console.warn(`TMDB trailer lookup returned HTTP ${videoResponse.status} for ${movie.title}.`);
          } else {
            const videoData = await videoResponse.json();
            const videos: MovieVideo[] = Array.isArray(videoData.results) ? videoData.results : [];
            const trailer = videos
              .filter((video) => video.site === 'YouTube' && ['Trailer', 'Teaser'].includes(video.type) && /^[\w-]{11}$/.test(video.key))
              .sort((a, b) => Number(b.official) - Number(a.official) || Number(b.type === 'Trailer') - Number(a.type === 'Trailer'))[0];
            match = { ...match, featured_video_key: trailer?.key ?? null };
          }
        } catch (error) {
          console.warn(`TMDB trailer lookup failed for ${movie.title}.`, error);
        }
      }

      movies[movie.title] = match ?? null;
    }
  };

  try {
    await Promise.all(Array.from({ length: Math.min(4, titles.length) }, lookupNext));
  } catch (error) {
    console.error('TMDB poster lookup failed.', error);
    return jsonResponse({ error: error instanceof Error ? error.message : 'TMDB poster lookup failed.' }, 502);
  }

  return jsonResponse({ movies });
});
