<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Model\Book;

$book = new Book(1, 'Kürk Mantolu Madonna');

echo $book->title . PHP_EOL;