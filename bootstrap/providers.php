<?php

return array_filter([
    App\Providers\AppServiceProvider::class,
    App\Providers\VoltServiceProvider::class,
    class_exists(Barryvdh\DomPDF\ServiceProvider::class) ? Barryvdh\DomPDF\ServiceProvider::class : null,
]);
