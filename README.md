# ScreenHub (React + Vite + Supabase)

ScreenHub is a React single-page app built with Vite. Supabase Auth handles sign-in, registration, and password resets. Usernames and profile details live in the Supabase `profiles` table with row-level security enabled.

## Start locally

1. Create a Supabase project.
2. In the Supabase dashboard, open **SQL Editor** and run [`supabase/schema.sql`](supabase/schema.sql).
3. Copy `.env.example` to `.env` and fill in the project URL and the publishable/anon key from **Project Settings → API**. Never put a `service_role` key in a browser app.
4. In this folder, run `npm install`, then `npm run dev`.
5. Open the local address Vite prints in the terminal.

For email confirmation and password reset links, add your local Vite address (usually `http://localhost:5173`) to **Authentication → URL Configuration → Redirect URLs** in Supabase.

## Existing accounts

Passwords from the former MySQL database cannot be copied into Supabase Auth as working accounts; existing users need to register again or use a separately planned account migration.

The existing movie posters and avatar images are served from `public/`. The app's movie catalog is sample frontend data; it is not a streaming service or a Supabase-backed catalog yet.
