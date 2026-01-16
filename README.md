# 🚀 ChatRooms: Advanced Real-Time Community Platform

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)
![Inertia.js](https://img.shields.io/badge/Inertia-1.x-9553E9?style=for-the-badge&logo=inertia)
![Reverb](https://img.shields.io/badge/Real--Time-Reverb-orange?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php)

**ChatRooms** is a scalable, modular, and high-performance real-time chat application. It combines robust backend architecture with a dynamic, reactive frontend to deliver an immersive community experience featuring RPG-style progression, granule permission management, and a complete social ecosystem.

---

## 🏗 Technology Stack

- **Backend Protocol**: Laravel 13 (PHP 8.4+) utilizing robust Service Pattern architecture.
- **Frontend Engine**: Vue 3 (Composition API) via Inertia.js for a seamless SPA experience.
    - *Routing*: Dynamic **Ziggy** integration (shared dynamically via Inertia).
- **Real-Time Layer**: **Laravel Reverb** (High-performance WebSocket server) + **Laravel Echo**.
- **Styling & Components**: Tailwind CSS & **Shadcn Vue** for premium, accessible UI components (HoverCards, Popovers, Modals).
- **Database**: PostgreSQL/MySQL optimized for chat indexing and JSON-based configuration.

---

## ✨ Core Features

### 👤 User Social System
A comprehensive profile and engagement engine.
- **Dynamic Profiles**: Native file uploads for **Avatars** and **Banners**, custom **Bio**, and real-time XP tracking.
- **RPG Leveling**: Automated XP awarding with anti-spam cooldowns and visual "Level Up" notifications.
- **HoverCards**: Interactive profile previews (via Shadcn) appearing throughout the chat for quick social interaction.

### 🤝 Friendship & Presence
Building connections within the community.
- **Friendship Lifecycle**: Send, receive, and manage friend requests.
- **Global Presence**: Real-time "Total Online" indicator and room-specific presence lists.
- **Privacy Control**: Ability to block users and manage social visibility.

### ✉️ Private Messaging (DM)
Seamless 1-on-1 communication outside public rooms.
- **Real-Time DMs**: Instant message delivery via dedicated private channels.
- **Persistence**: Full conversation history with "Mark as Read" synchronization.
- **Smart Sidebar**: Dedicated Direct Messages section with real-time **Unread Notification Badges**.

### 🔐 Account Verification Gate
Strict access control for community safety.
- **Verification Flow**: New users are restricted to a dedicated **"Guest Room"** until email verification.
- **Automatic Promotion**: Users are automatically upgraded from `Guest` to `User` rank upon successful verification.
- **Testing Tools**: Custom CLI commands for rapid developer testing.

### 🖼️ GIF Integration (Giphy)
Express yourself with rich media.
- **Giphy Search**: Integrated GIF picker with debounced search and trending categories.
- **Optimized Rendering**: Lazy-loaded GIFs with rounded corners and consistent UI integration.

### 🛡️ Moderation Suite
- **Granular Permissions**: Hierarchy-based moderation (Mute, Kick, Ban).
- **Instant Enforcement**: Real-time UI lockouts and redirects for restricted users.

---

## 📸 Visuals

| Dashboard | Chat Room | User Popover |
| :---: | :---: | :---: |
| ![Dashboard Placeholder](https://placehold.co/600x400?text=Dashboard+Overview) | ![Chat Placeholder](https://placehold.co/600x400?text=Chat+Room+Interface) | ![Popover Placeholder](https://placehold.co/600x400?text=User+Profile+Popover) |

---

## 🏛 Software Architecture

- **Service Pattern**: Business logic (e.g., `LevelService`, `ChatService`, `FriendshipService`) is decoupled from Controllers.
- **Standardized Broadcasting**: Unified channel naming (e.g., `conversations.{id}`, `chat.room.{id}`) for predictable real-time messaging.
- **Environment Driven**: Sensitive keys (like Giphy API) are managed via `.env` variables for security.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.4+
- Node.js 20+
- Supervisor (for Reverb & Queue Workers)

### Installation

1. **Clone & Dependencies**
   ```bash
   git clone git@github.com:yourusername/chatrooms.git
   composer install && npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Required Variables:*
   - `DB_*`: Your database credentials.
   - `REVERB_*`: WebSocket configuration.
   - `VITE_GIPHY_API_KEY`: Your Giphy API key.

3. **Database & Migrations**
   ```bash
   php artisan migrate --seed
   ```

4. **Start Servers**
   ```bash
   # Start WebSocket Server
   php artisan reverb:start
   
   # Start Frontend & Application (Separate terminals)
   npm run dev
   php artisan serve
   ```

### 🛠 Custom Commands

| Command | Description |
| :--- | :--- |
| `php artisan user:verify {email}` | Manually verify a user's email and upgrade their rank to User. |
| `php artisan user:set-admin {email}` | Directly assign the Administrator rank to a specific user. |
| `php artisan db:seed --class=RankPermissionSeeder` | Refresh the rank and permission hierarchy. |

---

**Crafted with ❤️ for scalable, engaged communities.**
