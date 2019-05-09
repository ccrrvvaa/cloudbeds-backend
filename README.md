# cloudbeds-backend
* Project for a Test task made by Cloudbeds - Backend Engineer

## Installation
* Execute `composer install`
* Create a MYSQL database and execute the script `db/backup_structure.sql`
* Make a copy of the file `config.php.dist` in a file called `config.php` and edit it with your database credentials
* To Execute tests: `./vendor/bin/phpunit --bootstrap vendor/autoload.php tests` This is mocking the Repository, the idea was to avoid using a database for unit testing. All data would be managed in memory as array of models, only for running unittests
* To run the server for testing purposes: `php -S localhost:8080 -t public/`

## Notes
* I implemented only a part of CRUD operations. I didn't implemented `update` operation because I'd need a little more time of development
* Haven't uploaded to a server yet
* I'm going to finish with this two tasks these days (Decided to send this first version becasue I don't know the time limit to present this project)
