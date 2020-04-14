## Installing
 - Download the files to your host.
 - Set the TOKEN in deploy.php that will be the Secret used in the GitHub webhook.
 - Maybe add .htaccess to allow access only to deploy.php

## Adding new Plugins
### 1 - Add a SSH key pair and grant access to www-data to at least read it
```
cd /var/www/.ssh
ssh-keygen
sudo chown -R www-data /var/www/.ssh
```

### 2 - Set the ssh config file
`vim var/www/.ssh/config`
(add something like example below)
```
Host gsg-onboard.github.com
  HostName github.com
  User git
  IdentityFile ~/.ssh/gsg-onboard.id_rsa
```

### 3 - Add the webhook on Github
*Payload URL:* http://playground.gsgmothership.com/plugin-updater/git-deploy/deploy.php <br>
*Secret:* Your Token, set during installation <br>
*Content Type:* application/json <br>
Set to push events
<br><br>
*obs: first request will fail. Test pushing to the release branch, other branches will result in a error. The errors can be seen in the Webhook page, under the Recent Deliveries section, on the Response tabs. <br>
Then, add the [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) code to your plugin and pass the url as in the example below <br>
https://playground.gsgmothership.com/plugin-updater/wp-update-server/?action=get_metadata&slug=gsg-onboard
