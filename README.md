# KE Backend
This is the backend repository for the project KE. It is powered by Laravel and Python. To deploy, first please follow the guide in the [ke-docker](https://github.com/ke-4140/ke-docker) repository.

## Project setup
After the docker is set up, 
1. cd into this repository and run ```composer install```
2. copy the file ```.env.example``` and rename it to ```.env```
3. run ```php artisan key:generate``` to generate app key
4. fill out the fields in the ```.env``` file
5. run ```php artisan migrate``` to initialize database
