<H1>Laravel Storefront Project</H1>
<br>Small storefront proyect i had for my laravel course. 
<br>Features include: Creating account, admin privileges, catalog, ordering, modifying products<br>
<h3>Required:</h3>
<br>php artisan migrate --seed
<br>php artisan storage:link
<br>php artisan serve
<br>Correo: admin@example.com
<br>Contraseña: password

<h3>Development data:</h3>
<br>In local and testing environments, <code>php artisan migrate --seed</code> creates 100 products, 200 development users, saved addresses, shipping cities, 300 orders and stock subscriptions.
<br>To seed this dataset explicitly in CI, run <code>php artisan db:seed --class=Database\Seeders\DevelopmentSeeder</code>.
<br>Development user emails follow <code>dev.user001@example.test</code> through <code>dev.user200@example.test</code>.
<br>All seeded users use the password <code>password</code>.
