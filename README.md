# NX-Content-Service-API

A small backend system in PHP that manages different types of content and exposes them via a REST API using MySQL database.

## Requirements

- PHP >= 7.4
- MySQL >= 5.7
- Web server (Apache/Nginx) or PHP built-in server

## Installation

1. Clone the repository
2. Copy `config/.env.example` to `.env` and configure your database credentials
3. Import the database schema:
   ```bash
   mysql -u root -p < database/schema.sql
   ```
4. Install dependencies (if any):
   ```bash
   composer install
   ```

## Running the API

### Using PHP built-in server:
```bash
php -S localhost:8000 -t public
```

### Using Apache/Nginx:
Configure your web server to point to the `public` directory as the document root.

## API Endpoints

### GET /api/contents
Get all contents (optional: filter by type)
- Query params: `type`, `limit`, `offset`

### GET /api/contents/{id}
Get a specific content by ID

### POST /api/contents
Create new content
- Body: `{"type": "article", "title": "Title", "content": "Content", "metadata": {}}`

### PUT /api/contents/{id}
Update existing content
- Body: `{"title": "New Title", "content": "New Content"}`

### DELETE /api/contents/{id}
Delete a content by ID

## Project Structure

```
├── config/
│   ├── database.php      # Database configuration
│   └── .env.example      # Environment variables template
├── database/
│   └── schema.sql        # Database schema
├── public/
│   └── index.php         # API entry point
├── routes/
│   └── api.php           # API routes
├── src/
│   ├── Database.php      # Database connection class
│   ├── Models/
│   │   └── Content.php   # Content model
│   └── Controllers/      # (for future controllers)
└── composer.json
```
