# ScreenHub (React + Vite + Supabase)

ScreenHub is a React single-page app built with Vite. Supabase Auth handles sign-in, registration, and password resets. Usernames and profile details live in the Supabase `profiles` table with row-level security enabled.

## Start locally

1. Create a Supabase project.
2. In the Supabase dashboard, open **SQL Editor** and run [`supabase/schema.sql`](supabase/schema.sql).
3. Copy `.env.example` to `.env` and fill in the project URL and the publishable/anon key from **Project Settings → API**. Never put a `service_role` key in a browser app.
4. In this folder, run `npm install`, then `npm run dev`.
5. Open the local address Vite prints in the terminal.

For email confirmation and password reset links, add your local Vite address (usually `http://localhost:5173`) to **Authentication → URL Configuration → Redirect URLs** in Supabase.

## Free full movies

ScreenHub lists free-to-watch open films from the Blender Foundation. This initial collection is primarily complete animated short films (about 3–15 minutes), not feature-length studio movies. The Supabase `tmdb-posters` Edge Function verifies each Internet Archive item before displaying it: the creator must match, its recorded license must match the curated catalog entry, and a playable MP4 file must be present. Movie pages play the complete film through Internet Archive's embedded player; ScreenHub does not host the video files.

The open-film catalog uses public-domain or Creative Commons licensed works. Each film page credits the creator and links to its license and source item. To deploy the catalog function, run `npx supabase functions deploy tmdb-posters --project-ref YOUR_PROJECT_REF` from this folder. Free movie catalog records are cached in the browser for up to 12 hours.

## Existing accounts

Passwords from the former MySQL database cannot be copied into Supabase Auth as working accounts; existing users need to register again or use a separately planned account migration.

Profile avatar images are served from `public/`. The app does not provide movie streaming.
