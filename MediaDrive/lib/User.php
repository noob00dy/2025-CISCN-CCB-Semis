<?php
declare(strict_types=1);

class User {
    public string $name = "guest";
    public string $encoding = "UTF-8";
    public string $basePath = "/var/www/html/uploads/";

    public function __construct(string $name = "guest") {
        $this->name = $name;
    }
}
