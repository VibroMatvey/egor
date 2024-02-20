## Symfony 6 EasyAdmin Api-Platform template

- rebase .env to .env.local
- change DATABASE_URL

then:

```
composer install

php bin/console d:d:c

php bin/console d:s:u --force

symfony server:start
