# 🛡️ Secure Comm Messenger

> **Military-Grade End-to-End Encrypted Real-Time Messaging Platform**
> Built for privacy-first communication where **only the sender and receiver can read messages** — not even the server.

---

## 📖 Overview

**Secure Comm Messenger** is a modern real-time chat platform engineered with a strict **Zero-Knowledge Architecture**.
Every message is encrypted directly inside the client browser using the **Web Crypto API**, ensuring that plaintext data never touches the backend server.

The server only stores and transmits encrypted ciphertext.

Designed with scalability, security, and real-time performance in mind, the application combines:

* ⚡ Real-time bidirectional communication
* 🔐 True End-to-End Encryption (E2EE)
* 🌐 SPA experience with React + Inertia
* 📦 Flexible NoSQL architecture using MongoDB
* 🔄 Secure Private Key Backup & Restore system

---

# ✨ Features

## 🔐 True End-to-End Encryption (E2EE)

The application follows the **Golden Rule of Secure Messaging**:

> **Encryption and decryption happen ONLY on the client side.**

### How it works:

* Each user generates a **public/private key pair** in the browser during registration.
* The **public key** is uploaded to the backend.
* The **private key never leaves the user’s device**.
* Messages are encrypted using the recipient’s public key before transmission.
* Laravel only receives encrypted ciphertext.

### Result:

✅ Server cannot read messages
✅ Database leaks reveal only encrypted data
✅ Maximum communication privacy

---

## ⚡ Real-Time Communication Engine

Powered by:

* **Laravel Reverb** → Native WebSocket server
* **Redis** → Queue + broadcasting engine
* **Laravel Echo** → Frontend event listener

This enables:

* Instant message delivery
* Live conversation updates
* Real-time typing & presence events
* Scalable event broadcasting

---

## 🌐 Modern SPA Experience

The frontend is built using:

* **React**
* **Inertia.js**
* **Tailwind CSS**

Benefits:

* Smooth SPA navigation
* No separate REST API complexity
* Fast page transitions
* Minimal frontend/backend overhead

---

## 📦 MongoDB NoSQL Architecture

MongoDB is used for storing:

* Users
* Conversations
* Encrypted Messages
* Public Keys

Advantages:

* Flexible document-based structure
* Faster iteration for messaging systems
* Scalable storage for real-time applications

---

## 🔑 Secure Key Backup & Restore

One of the biggest E2EE problems is:

> “What happens if a user changes devices?”

This project solves that using a **Private Key Backup & Restore System**.

### Features:

* Export private key as encrypted `.txt` backup
* Restore keys on:

  * New devices
  * Incognito windows
  * Fresh browser installs

### Why it matters:

Without the private key:

* Old encrypted messages become unreadable

This system ensures users never permanently lose access to their chats.

---

# 🧠 Architecture: The Golden Rule

## 🔒 Zero-Knowledge Messaging Flow

```text
User A → Fetches User B Public Key
       → Encrypts Message in Browser
       → Sends Ciphertext to Laravel
       → Laravel Broadcasts Encrypted Data
       → User B Decrypts Message Locally
```

### Important Security Principle

The backend:

* ❌ Cannot decrypt messages
* ❌ Cannot access private keys
* ❌ Cannot read conversations

Only authenticated clients with valid private keys can decrypt messages.

---

# 🛠️ Tech Stack

| Layer          | Technology         |
| -------------- | ------------------ |
| Backend        | PHP + Laravel      |
| Frontend       | React + Inertia.js |
| Styling        | Tailwind CSS       |
| Database       | MongoDB            |
| Real-Time      | Laravel Reverb     |
| Queue System   | Redis              |
| Authentication | Laravel Sanctum    |
| Encryption     | Web Crypto API     |

---

# 🚀 Getting Started

Follow these steps carefully to run the project locally.

---

# 📋 Prerequisites

Make sure your system has:

* PHP 8.2+
* Composer
* Node.js & npm
* MongoDB Server
* Redis Server
* MongoDB PHP Extension

---

# 1️⃣ Clone Repository

```bash
git clone <your-repo-url>
cd secure-comm-messenger
```

---

# 2️⃣ Install Dependencies

## Backend Dependencies

```bash
composer install
```

## Frontend Dependencies

```bash
npm install
```

---

# 3️⃣ Configure Environment

Copy the environment file:

```bash
cp .env.example .env
```

Generate Laravel application key:

```bash
php artisan key:generate
```

---

# 4️⃣ Configure Database & Broadcasting

Update your `.env` file:

```env
DB_CONNECTION=mongodb
DB_URI="mongodb://127.0.0.1:27017"
DB_DATABASE="secure_messenger"

BROADCAST_CONNECTION=reverb
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

---

# 5️⃣ Install MongoDB Laravel Package

```bash
composer require mongodb/laravel-mongodb
```

---

# 6️⃣ Run Migrations

```bash
php artisan migrate
```

---

# 7️⃣ Start Application Services

You must run **three terminals simultaneously**.

---

## 🖥️ Terminal 1 — Laravel Backend

```bash
php artisan serve
```

---

## ⚡ Terminal 2 — WebSocket Server

```bash
php artisan reverb:start
```

---

## 🎨 Terminal 3 — Frontend Compiler

```bash
npm run dev
```

---

# 🌍 Application Access

After starting all services:

| Service         | URL                     |
| --------------- | ----------------------- |
| Laravel Backend | `http://127.0.0.1:8000` |
| Frontend        | Vite Development Server |
| WebSocket       | Laravel Reverb          |

---

# 🔐 Security Highlights

## ✔ Client-Side Encryption

Messages are encrypted before leaving the browser.

## ✔ Zero Plaintext Storage

Database only stores encrypted ciphertext.

## ✔ Private Key Isolation

Private keys remain exclusively on user devices.

## ✔ Secure Authentication

Laravel Sanctum secures SPA sessions.

## ✔ Real-Time Secure Broadcasting

Encrypted payloads are broadcast safely through Reverb.

---

# 📁 Suggested Project Structure

```text
secure-comm-messenger/
│
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
│   ├── js/
│   ├── css/
│
├── routes/
├── storage/
├── .env
├── composer.json
├── package.json
└── README.md
```

---

# 🧪 Future Enhancements

Planned improvements:

* ✅ Group Chat Encryption
* ✅ Voice & Video Calling
* ✅ Message Self-Destruct
* ✅ File Encryption & Sharing
* ✅ Device Management Dashboard
* ✅ Multi-Device Sync
* ✅ Forward Secrecy
* ✅ Secure Push Notifications

---

# 🤝 Contributing

Contributions are welcome.

To contribute:

```bash
fork → clone → create branch → commit → push → create PR
```

---

# 📜 License

This project is licensed under the MIT License.

---

# 👨‍💻 Author

Developed with security-first architecture using:

* Laravel
* React
* MongoDB
* Redis
* Web Crypto API

---

# ⭐ Final Note

> “Privacy is not a feature. It is the foundation.”

Secure Comm Messenger is designed to prove that modern real-time applications can remain scalable, beautiful, and truly private at the same time.
