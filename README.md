# NX-Content-Service-API

A lightweight PHP REST API for managing structured content types stored in a MySQL database.  
The system supports CRUD operations, slug-based public content resolution, and relational content types such as authors, industries, and contractors.

---

# 📌 Features

- RESTful API built in raw PHP (no framework)
- MySQL-backed relational data model
- Multiple content types (blog posts, articles, experiences, cost guides)
- Slug generation with uniqueness tracking
- Public slug-based content resolution
- Internal API for full content management
- Flexible filtering system (type, author, industry, contractor)
- Basic validation layer for business rules per content type

---

# ⚙️ Requirements

- PHP >= 7.4
- MySQL >= 5.7
- Composer
- Apache/Nginx or PHP built-in server

---

# 🚀 Installation & Setup

## 1. Clone repository
git clone <repo-url>  
cd NX-Content-Service-API  

---

## 2. Install dependencies
composer install  

---

## 3. Environment setup
Copy:
config/.env.example → .env  

Update DB credentials.

---

## 4. Create database
mysql -u root -p < database/schema.sql  

---

## 5. Run locally
php -S localhost:8000 -t public  

Or point your web server to:
/public  

---

# 📡 API Overview

## Internal Content API

GET /api/content  
GET /api/content/{id}  
POST /api/content  
PUT /api/content/{id}  
DELETE /api/content/{id}  
PUT /api/content/{id}/publish  

---

### Filtering
GET /api/content?type=article&author=John

---

## Authors

GET /api/authors/{id}  
POST /api/authors  

---

## Public Content API

GET /{content_type}/{slug}

Example:
GET /blog_post/my-first-post

Returns only:
- published
- not deleted

---

# 🧠 Key Design Decisions

## Public vs Internal API
- /api/* = internal CMS
- /{type}/{slug} = public access layer

Simulates real CMS architecture separation.

---

## Slug System
- Auto-generated on creation
- Ensures uniqueness per content type
- Stored in slug_history table for traceability

---

## Validation
- Type-specific validation rules
- Empty strings are not considered null
- Supports partial updates

---

## Content Type Rules
- Blog → requires industry
- Experience → requires contractor
- Cost guide → requires min/max cost

---

## Authentication (Future)
Planned improvements:
- JWT auth
- role-based access control
- restricted draft access

---

# 🧱 Project Structure

├── config/  
├── database/  
├── public/  
├── routes/  
├── src/  
└── vendor/  

---

# 🤖 AI Usage

Tools used:
- Windsurf → project scaffolding
- ChatGPT → debugging, architecture decisions, slug logic, validation design, documentation refinement

---

# 🧪 Testing

Use Postman or cURL:

GET http://localhost:8000/api/content?type=article  

---

# 📌 Limitations

- No authentication
- No pagination
- Basic error handling
- Recursive slug generation
- No logging system

---

# 📈 Future Improvements

- Authentication system
- Pagination + sorting
- Centralized logging
- Unit testing
- Improved slug performance
- Stronger separation between public/admin APIs
- Add caching for frequently requested content to improve performance and reduce database load

---

# 📄 License

Built as part of a technical assignment.
