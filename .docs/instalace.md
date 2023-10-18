# Instalace pro lokální vývoj

Aplikace vyžaduje:
- PHP 8.1
- MySQL 8
- Apache
- Composer
- Yarn

## Docker
Pro lokální vývoj je připraven Docker image a konfigurace pro **docker-compose**.

Před prvním spuštěním je třeba vytvořit docker volume pro databázi pomocí:

```bash
docker volume create srs_mysql
```

Kontejner je možné spustit z root adresáře projektu pomocí:

```bash
docker-compose up -d
```

Zastavení kontejneru je možné pomocí:

```bash
docker-compose down
```


Ke kontejneru je možné se připojit a spustit bash pomocí:

```bash
docker exec -it srs.app bash
```

## Nastavení hosts
SkautIS při přihlašování přesměrovává na `srs.loc`.
Proto je třeba nastavit si mapování této domény na localhost.

Stačí přidat následující řádek do `/etc/hosts` souboru: 

```
127.0.0.1   srs.loc
```

- Windows: `C:\Windows\System32\drivers\etc\hosts`
- Linux/MAC: `/etc/hosts`

## Příprava projektu
1. Instalace PHP závislostí přes composer (v kontejneru nebo lokálně s nainstalovaným PHP a composerem):
   ```bash
   composer install
   ```
2. Spuštění databázových migrací (v kontejneru):
   ```bash
   php www/console.php migrations:migrate
   ```
3. Instalace frontend závislostí (lokálně):
   ```bash
   yarn install
   ```
   > Instalace Yarn je popsána na: https://classic.yarnpkg.com/lang/en/docs/install/.
4. Sestavení frontendu (lokálně):
   ```bash
   yarn build
   ```   
