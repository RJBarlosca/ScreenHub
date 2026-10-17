const corsHeaders = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Headers': 'authorization, x-client-info, apikey, content-type',
  'Access-Control-Allow-Methods': 'POST, OPTIONS',
  'Content-Type': 'application/json',
};

type MovieRequest = { title: string; year?: number };
type MovieSearchResult = {
  id: number;
  title: string;
  original_title: string;
  release_date?: string;
  genre_ids: number[];
  vote_average: number;
  overview: string;
  poster_path: string | null;
};

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

  let payload: { titles?: unknown };
  try {
    payload = await request.json();
  } catch {
    return jsonResponse({ error: 'Request body must be valid JSON.' }, 400);
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
    titles.push({ title: item.title.trim(), year: item.year });
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
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
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
