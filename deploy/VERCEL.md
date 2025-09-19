Vercel: change site display name and redeploy

This short guide explains how to update the project display name in Vercel and trigger a redeploy from this repository.

1) Change the project display name in Vercel UI
- Open https://vercel.com and sign in.
- Open your project.
- In Project Settings -> General, change "Name" or "Display name" to "Prise de RDV".
- Save changes.

2) Trigger a redeploy
Option A: push a small commit (recommended)
- Make a trivial commit in the `client/` folder, e.g. update `client/index.html` (already updated in the repo) or bump a comment.
- Push to `main`: git add -A && git commit -m "chore: redeploy site branding to Prise de RDV" && git push

Option B: use the Vercel CLI
- Install Vercel CLI: npm i -g vercel
- From project root: vercel --prod

Option C: GitHub Actions (automatic)
- This repo includes a workflow `.github/workflows/vercel-deploy.yml` which builds the `client/` folder and deploys to Vercel on changes to `main`.
- To enable this workflow you must add the following repository secrets:
  - VERCEL_TOKEN: a personal token from https://vercel.com/account/tokens
  - VERCEL_ORG_ID: organization ID where the project is located
  - VERCEL_PROJECT_ID: the project ID
- Once the secrets are configured, pushing to `main` (or changes under `client/`) will automatically trigger a build+deploy.

3) Post-deploy checks
- Open the site (your Vercel URL) and verify the browser title and header now show "Prise de RDV".
- If cached content still shows old text, try a hard refresh or remove the build cache in Vercel.

If you want, I can:
- Add a tiny commit and push it to trigger a redeploy (requires permission to push)
- Help you create the Vercel token and set the repository secrets step-by-step
- Add an alternative workflow that uses the official `vercel` CLI instead of third-party actions
