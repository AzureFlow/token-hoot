# TokenHoot

An automated tool for collecting [OWL](https://overwatchleague.com/en-us/) tokens on multiple accounts.

### Get your User ID

- Login to [OverwatchLeague.com](https://overwatchleague.com/en-us/)
- Extract your account token from the `account_token` cookie.
  - Easy method - open the Chrome DevTools and run the following: 
    ```javascript
    JSON.parse(atob(Object.fromEntries(document.cookie.split('; ').map(v=>v.split(/=(.*)/s).map(decodeURIComponent)))['account_token'].split('.')[1]))['id']
    ```
  - Manual method: decode the `account_token` JWT cookie and select the `id` field.

### Running with Cron

Install the dependencies:
```bash
composer install
```

Edit your crontab:
```bash
crontab -e
# Add the following: */1 * * * * php /app/owl-tkn.php >> /var/log/cron.log
```

### Running with Docker

Copy `.env.example` to `.env` and edit:

```dotenv
OWL_ACCOUNTS=000000000,100000000
```

Build:
```bash
docker build -t azureflow/owl-tkn .
```

Run:

```bash
docker-compose up --build -d
```

Logs:

```bash
watch docker logs owl_tkn
```

Testing:

```bash
docker exec -it owl_tkn /bin/sh
```

### Deploying with Fly.io for **Free**

Setup [`flyctl`](https://fly.io/docs/speedrun/) and login.

```bash
flyctl create --name owl-tkn --no-deploy
flyctl secrets set OWL_ACCOUNTS=000000000,100000000
fly volumes create data --size 1
flyctl deploy

# flyctl logs --app owl-tkn
# flyctl ssh console
#   printenv OWL_ACCOUNTS
```

### Can I get banned for using this?

Yes, absolutely. However, I've been running this for years without a problem.
As the license states this program is provided "without warranty" and should be used at your own risk.

### Why is this Writen in PHP?

Because I hate myself.