# ScreenHub (React + Vite + Supabase)

ScreenHub is a React single-page app built with Vite. Supabase Auth handles sign-in, registration, and password resets. Usernames and profile details live in the Supabase `profiles` table with row-level security enabled.

## Start locally

1. Create a Supabase project.
2. In the Supabase dashboard, open **SQL Editor** and run [`supabase/schema.sql`](supabase/schema.sql).
3. Copy `.env.example` to `.env` and fill in the project URL and the publishable/anon key from **Project Settings → API**. Never put a `service_role` key in a browser app.
4. In this folder, run `npm install`, then `npm run dev`.
5. Open the local address Vite prints in the terminal.

For email confirmation and password reset links, add your local Vite address (usually `http://localhost:5173`) to **Authentication → URL Configuration → Redirect URLs** in Supabase.

## TMDB movie posters

ScreenHub verifies its curated movie titles against TMDB before displaying them. Displayed titles, genres, release years, ratings, overviews, and poster paths come from TMDB; movies that do not exactly match a TMDB movie result are omitted. To enable it:

1. Create an API Read Access Token in [TMDB API settings](https://www.themoviedb.org/settings/api).
2. In your Supabase dashboard, open **Edge Functions → Secrets** and add `TMDB_API_READ_ACCESS_TOKEN` with that token as its value. Do not put the token in a `VITE_` variable or browser code.
3. From this project folder, deploy the proxy with `npx supabase functions deploy tmdb-posters --project-ref YOUR_PROJECT_REF`. The deployed function uses Supabase JWT verification and calls TMDB without exposing the token to the browser.
4. Sign in to ScreenHub. Successfully resolved TMDB movie records are cached in the browser for up to 7 days.

The app displays TMDB's logo and required attribution in its footer. Review [TMDB's API terms](https://www.themoviedb.org/api-terms-of-use) before use; commercial use requires a separate written agreement with TMDB.

## Existing accounts

Passwords from the former MySQL database cannot be copied into Supabase Auth as working accounts; existing users need to register again or use a separately planned account migration.

Profile avatar images are served from `public/`. The app does not provide movie streaming.
