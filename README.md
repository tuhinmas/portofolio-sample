Modular System Platform For Sample
=================


Notes for development using Docker
----------------------------------

A helper script named `dev` is provided as a shortcut for the docker-compose commands to simplify the development process.

### Requirements

- Docker Engine
- Docker Compose


### Getting Started

Create .env file by copying from .env.example

```bash
cp .env.example .env
```

```bash
cp .Dockerfile.example Dockerfile or copy manually
```

```bash
cp .docker-compose.yml.example docker-compose.yml
```

Install project dependencies

```bash
./dev composer install
```

Generate application key

```bash
./dev artisan key:generate
```

Migration

```bash
./dev artisan migrate
```

Generate JWT Secret

```bash
./dev artisan jwt:secret
```

Run code above before seeding. then ou can seed with
```bash
./dev artisan db:seed
```

### Available command

#### serve

Start the containers

```bash
./dev serve
```

You can change the port where PHP server is running on different port from the one definend in .env file

```bash
APP_PORT=8080 ./dev serve
```

#### down

Stop the containers

```bash
./dev down
```

#### composer

Run `composer` and pass-thru any extra arguments inside a new app container

```bash
./dev composer help
```

#### artisan

Run `artisan` and pass-thru any extra arguments inside a new app container

```bash
./dev artisan help
```
#### Modular Documentation
```Nwidart
https://nwidart.com/laravel-modules/v6/introduction
```
Instalation package
cd Modules\name-your-module

composer require package-name

cd ../../

./dev artisan module:update name-your-module

#### Spatie Documentation 

```bash
https://spatie.be/docs/laravel-permission/v4/basic-usage/basic-usage
```

#### test

Run `phpunit` and pass-thru any extra arguments inside a new app container

```bash
./dev test --help
```

#### Province in indonesia

```bash
https://github.com/laravolt/indonesia
```

Run `./dev artisan laravolt:indonesia:seed` to seed all Province, district, in Indonesia

#### Laravel UUID
all table id generate using uuid, except view tables
```
https://github.com/ramsey/uuid
```

#### JWT (Json Web Token)

```bash
https://jwt-auth.readthedocs.io/en/develop/
```

#### Country seeder ISO-3166
```bash
https://github.com/kedweber/laravel_seeder_country_codes
```

#### CSV to Seeder
```bash
https://packagist.org/packages/jeroenzwart/laravel-csv-seeder
```

#### csv to array
```bash
https://packagist.org/packages/ogrrd/csv-iterator
```

### Compass rest API
```bash
composer update --dev
php artisan compass:install

php artisan migrate

fresh: php artisan compass:build
rebuild: php artisan compass:rebuild

path: {domain}/compass
```

### Laravel Orion
To make it easier during apllication development our team use Laravel Orion,
```bash
https://tailflow.github.io/laravel-orion-docs/
```

for detail how to use Laravel Orion please visit link above

Note: there is issue if you use scope and multiple filters with type OR. For example if you want to filter data with condition 
```
if (A and B) OR (A and C)
```
Orion returned invalid data with condition 
```
if (A and B) OR C
```
To resolve this issue you can use array on filter value instead of string with multiple filters as documentation say, for example:

```
"scope":[
  {"name":"whereNam", "parameters":[]}
],
"filters:[
  {"field":"status", "operator":"=", "value"=["filed", "submission of changes"]}
]
```
This format work for me as far as i know

### Laravel Eloquent
Make reusable eloquent filter, and pretty filter on query
```bash
https://github.com/pricecurrent/laravel-eloquent-filters
```

### Laravel Soft Delete Cascade
soft delete all relation data as child
```bash
https://packagist.org/packages/dyrynda/laravel-cascade-soft-deletes
```

### Log Activity
save all changes log
```bash
https://spatie.be/docs/laravel-activitylog/v4/introduction
```

as far as i know, by default this package is not supported if you use uuid in all model instead of integer,
because the causer_id and subject_id on this package was set as integer, you need to change these two column type directly to char 36 (uuid) or you can create new migration to change it.

### Pusher
push notification
```bash
https://pusher.com/tutorials/web-notifications-laravel-pusher-channels/
```

### Enum
Enumeration on model using trait
```bash
https://gist.github.com/jhoff/b68dc2ac0e106a68488475e8430b38dc
```

### SOft Delete Pivot
```bash
https://github.com/ddzobov/laravel-pivot-softdeletes
```

### Git Command

Push local update into repository
```bash
php artisan git:push 
```

Versioning Tag
```bash
php artisan git:tag 
```

### Deep Relation
retrieve relational data through multiple tables
```bash
https://github.com/staudenmeir/eloquent-has-many-deep
```

### API Postman Generator
```bash
https://github.com/andreaselia/laravel-api-to-postman
```

### Self Reference
```bash
https://joaorbrandao.medium.com/self-reference-laravel-model-fa8a7b37360d
```

### Excel Reader
```bash
https://github.com/rap2hpoutre/fast-excel
```