# Deploying Mandem to Render

## Before deployment

1. Push this repository to GitHub or GitLab.
2. Change `MANDEM_INVITE_CODE` to a private, unguessable value. Share it only with the six members.
3. Confirm the FPL league ID is `707395`.

## Create the Render services

1. In Render, choose **New → Blueprint** and connect the repository.
2. Render reads `render.yaml` and proposes a web service plus PostgreSQL database.
3. Enter the three environment values marked `sync: false`:
   - `APP_KEY`: run `php artisan key:generate --show` locally and paste the complete `base64:...` value
   - `APP_URL`: the Render URL, for example `https://mandem.onrender.com`
   - `MANDEM_INVITE_CODE`: your private invitation code
4. Deploy. The container builds Vue, installs production PHP dependencies, runs database migrations, and starts Laravel.
5. Visit `/join` and create the six accounts. Each member should enter the number from their FPL team URL (`/entry/123456/...`) as their FPL entry ID.

## First data import

Open the Render Shell and run:

```bash
php artisan fpl:sync
```

The container also runs Laravel's scheduler. It refreshes FPL data every ten minutes between 10:00 and midnight in the server timezone. Free instances sleep when inactive, so scheduled imports pause while the service sleeps. A paid worker or external cron ping is the clean upgrade if uninterrupted live updates become important.

## Updating

Push to the connected production branch. Render rebuilds the image and `php artisan migrate --force` applies safe pending migrations at startup.

## Troubleshooting

```bash
php artisan about
php artisan route:list
php artisan schedule:list
php artisan fpl:sync
```

FPL's endpoints are unofficial. A failed import does not delete the last successful data stored by Mandem.
