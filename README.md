# 🚀 ChatRooms: Advanced Real-Time Community Platform

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)
![Inertia.js](https://img.shields.io/badge/Inertia-1.x-9553E9?style=for-the-badge&logo=inertia)
![Reverb](https://img.shields.io/badge/Real--Time-Reverb-orange?style=for-the-badge&logo=laravel)
![PHP](https://img.shields.io/badge/PHP-8.4+-777BB4?style=for-the-badge&logo=php)

**ChatRooms** is a scalable, modular, and high-performance real-time chat application built with modern web technologies. It combines robust backend architecture with a dynamic, reactive frontend to deliver an immersive community experience featuring RPG-style progression, granule permission management, and real-time moderation.

---

## 🏗 Technology Stack

- **Backend Protocol**: Laravel 13 (PHP 8.4+) utilizing robust Service Pattern architecture.
- **Frontend Engine**: Vue 3 (Composition API) via Inertia.js for a seamless SPA experience.
- **Real-Time Layer**: **Laravel Reverb** (WebSocket server) + **Laravel Echo** for instant messaging, presence tracking, and events.
- **Styling**: Tailwind CSS & ShadCN UI for modern, accessible components.
- **Database**: PostgreSQL/MySQL optimized for chat indexing and JSON-based configuration.

---

## ✨ Core Features

### 👑 Advanced Rank & Permission System
A fully dynamic role-management engine allowing granular control over user capabilities and aesthetics.
- **Visual Customization**: Ranks support complex visual effects like `Glow`, `Rainbow` animations, and custom colors stored via JSON.
- **Hierarchy Protection**: Strict priority-based logic ensures moderators cannot act against users with equal or higher rank.
- **Granular Permissions**: System-based checks (e.g., `mute_user`, `bypass_lock`) enable fine-tuned access control.

### ⚡ Real-Time Engine (Reverb)
Powered by WebSockets for an instantaneous user experience.
- **Live Messaging**: Zero-latency message delivery with optimistic UI updates.
- **Presence Channels**: "Who's Online" sidebars that update instantly when users join or leave.
- **Typing Indicators**: "Whisper" events show when users are composing messages without hitting the database.
- **System Events**: Real-time broadcasts for user kicks, bans, and system announcements.

### ⚔️ RPG Leveling System
Gamification features to drive user engagement.
- **XP Algorithm**: Intelligent XP awarding with anti-spam cooldowns (configurable via `config/chat.php`).
- **Progression**: Visual level indicators and "Level Up" toast notifications.
- **Access Control**: Chat rooms can be locked behind specific **Level** or **Rank** requirements.

### 🛡️ Real-Time Moderation Suite
A comprehensive toolset for community management with immediate enforcement.
- **Instant Mutes**: Disables the chat input for the target user in real-time and rejects backend requests (403).
- **Hard Kicks**: Triggers a browser event that immediately redirects the user out of the chat room.
- **IP Banning**: Global Middleware (`CheckIpBan`) blocks restricted IP addresses at the application firewall level.
- **Contextual Tools**: Moderators get a dedicated UI (Shield Icon permissions) to open moderation modals directly from the chat stream.

---

## 🏛 Software Architecture

This project adheres to **SOLID principles** and Clean Code practices to ensure scalability.

- **Service Pattern**: Business logic (e.g., `LevelService`, `ChatService`, `ModerationService`) is decoupled from Controllers.
- **Middleware Security**: Custom middleware like `CheckIpBan` and broad usage of Authorization Policies.
- **Single Source of Truth**: The frontend relies on reusable, intelligent components like `RankedUserLabel.vue` which handles rank visualization logic centrally.
- **Optimistic UI**: The chat interface updates state locally before network confirmation to ensure perceived instantaneity.

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.4+
- Node.js 20+
- Supervisor (for Reverb & Queue Workers)

### Installation

1. **Clone the Repository**
   ```bash
   git clone git@github.com:yourusername/chatrooms.git
   cd chatrooms
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your `DB_*` and `REVERB_*` credentials in `.env`.*

4. **Database & Migrations**
   ```bash
   php artisan migrate --seed
   ```

5. **Start Real-Time Server**
   ```bash
   php artisan reverb:start
   ```

6. **Compile Frontend & Serve**
   ```bash
   npm run dev
   # In a separate terminal
   php artisan serve
   ```

---

## 🔮 Upcoming Features
- [ ] **Direct Messaging (DM)**: Private 1-on-1 conversations.
- [ ] **Rich Media Support**: Image and file uploads in chat.
- [ ] **Emoji Picker**: Native integration for rich text responses.
- [ ] **Audit Logs**: Deep history of all moderation actions.

---

**Crafted with ❤️ for scalable communities.**
