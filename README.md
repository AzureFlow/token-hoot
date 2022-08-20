# TokenHoot

An automated tool for collecting [OWL](https://overwatchleague.com/en-us/) tokens on multiple accounts.

### Get your User ID

- Login to [OverwatchLeague.com](https://overwatchleague.com/en-us/)
- Go to [`https://account.battle.net/api/user`](https://account.battle.net/api/user) and copy the value of `accountId`.

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
docker logs owl_tkn -f
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