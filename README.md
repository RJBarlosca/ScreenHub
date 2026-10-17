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

ScreenHub's classic-film collection currently includes feature-length titles such as *The General*, *D.O.A.*, *The Little Shop of Horrors*, *The Killer Shrews*, and *The Brain That Wouldn't Die*. The Supabase `tmdb-posters` Edge Function checks each curated Internet Archive record for its expected title, displayed rights label, and a playable MP4 before showing it. Movie pages embed the full film from Internet Archive; ScreenHub does not host the video files.

Internet Archive's Public Domain Mark is an informational label, not a legal determination or guarantee. Copyright status and playback availability can differ by country, and a source listing alone does not establish worldwide clearance. Each film page links to the Archive item and explains this limitation. To deploy the catalog function, run `npx supabase functions deploy tmdb-posters --project-ref YOUR_PROJECT_REF` from this folder. Catalog records are cached in the browser for up to 12 hours.

## Existing accounts

Passwords from the former MySQL database cannot be copied into Supabase Auth as working accounts; existing users need to register again or use a separately planned account migration.

Profile avatar images are served from `public/`. The app does not provide movie streaming.
