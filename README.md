# Laravel Kotuko Challenge
The challenge requires developing a server-side application in Laravel exposing RSS feeds
corresponding to the categories of The Guardian, a leading UK newspaper

## Features

- Caching
- RSS

## Deployment



To get the project started
```bash
  docker-compose up
```

Perform migrations
```bash
  php artisan migrate
```


## API Reference

#### Get all items



```http
  GET /api/${category}
```

| Parameter | Type     | Description                       |
| :-------- | :------- | :-------------------------------- |
| `category`      | `rss` | **Required**. Category whose articles you want to search for |





