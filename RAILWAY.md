# Railway

#### References
- https://docs.railway.app/deploy/config-as-code
- https://invariance.dev/2022-08-04-deploy-laravel-on-railway.html

## Install and configure Railway CLI
1. Install 
```sh
brew install railway
```
2. Login 
```sh
railway login --browserless
```
3. Follow and accept the link
```sh
Your pairing code is: random-pairing-code
To authenticate with Railway, please go to
    https://railway.app/cli-login?d=XYZ
```

## Setup and configure existing repository 
We're taking an existing Laravel repository and deploying it to Railway.

### From web interface
1. Go to https://railway.app/new
2. Select "Deploy from GitHub repo"
3. Select teh repository
4. Deploy

#### Configure
Some configurations will be neccessary.


#### Access
1. Log in to railway shell
```
railway login --browserless
```
2. Link
```
railway link
```
3. Shell
```
railway shell
```
