# 🍽️ TiffinCraft – Cloud Kitchen Marketplace

TiffinCraft is a **cloud kitchen marketplace platform** that connects **local home kitchens with customers**.
Buyers can order home-cooked meals while kitchen owners manage menus, orders, and subscriptions.

The platform includes **three roles**:

* Buyers (customers)
* Sellers (kitchen owners)
* Administrators

---

## 📋 Features

### 🛒 Buyer Features

* Browse kitchens and menu items
* Search and filter dishes by category or price
* Add items to cart and place orders
* Track orders
* View order history
* Submit reviews and ratings

### 🍳 Seller Features

**Dashboard**

* Order statistics
* Revenue analytics
* Popular menu items

**Menu Management**

* Add/edit/delete menu items
* Upload item images
* Assign categories
* Set spice levels (Mild / Medium / Spicy)
* Track daily stock
* Toggle availability

**Order Management**

* View incoming orders
* Update order status: `PENDING → ACCEPTED → READY → DELIVERED`
* Cancel orders with reason
* Search and filter orders

**Kitchen Management**

* Update kitchen profile
* Manage kitchen images
* Configure service areas

**Subscription System**

* View active plan
* Track item limits and commission rates

### 🛡️ Administrator Features

* Manage users (buyers and sellers)
* Manage kitchens and categories
* Monitor subscriptions
* Platform analytics

---

## 🛠️ Technology Stack

**Backend:** PHP (Native PHP with MVC-inspired structure)
**Database:** Oracle Database (OCI8)
**Frontend:** HTML5, CSS3, Vanilla JavaScript
**Libraries:**

* Font Awesome – icons
* Chart.js – analytics charts
  **Payment Integration:** SSLCommerz

---

## 🚀 Installation

### Requirements

* PHP 8+
* Oracle Database
* Oracle Instant Client
* Web server (Apache/Nginx)

### Setup

1️⃣ **Clone repository**

```bash
git clone https://github.com/rakibdevhub/tiffincraft.git
cd tiffincraft
```

2️⃣ **Configure environment**

```bash
cp .env.example .env
```

Edit `.env` with your database and application credentials.

3️⃣ **Setup Database**

* Import schema from `sql/schema.sql`

4️⃣ **Web Server**

* Point web root to `/public`
* Enable URL rewriting if necessary

5️⃣ **Run Application**
Open in browser:

```
http://localhost/tiffincraft
```

---

## 📊 Database Structure

Key tables:

* `users` – user accounts
* `kitchens` – kitchen/seller information
* `categories` – food categories
* `menu_items` – menu items with pricing and stock
* `menu_item_categories` – item-category mapping
* `orders` – customer orders
* `order_items` – individual order items
* `reviews` – kitchen/item reviews
* `subscriptions` – seller subscription plans
* `service_areas` – kitchen delivery zones
* `payment_transactions` – transactions and payments
* `refunds` – order refunds

---

## 🎨 UI & Features

* Responsive and mobile-friendly design
* Plain CSS styling
* Dashboard layouts for sellers and admins
* Modal-based CRUD operations
* Search, filter, and status badges
* Flash notifications for actions

---

## 🔒 Security Features

* CSRF protection on forms
* Password hashing
* Session-based authentication
* Role-based access control
* Input validation and sanitization
* Prepared statements for Oracle queries

---

## 📁 Project Structure

```text
tiffincraft/
│
├── public/
│   ├── uploads/       # Uploaded images
│   ├── index.php
│   └── assets/
│
├── src/
│   ├── controllers/
│   ├── models/
│   ├── views/
│   └── config/
│
├── sql/
│   └── schema.sql
│
├── logs/
├── .env.example
└── README.md
```


---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add YourFeature'`)
4. Push to branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

---

## 🙏 Acknowledgments

* Font Awesome – icons
* Chart.js – analytics charts
* All contributors and testers

---

**TiffinCraft** – Bringing home-cooked meals to your doorstep! 🍛

---