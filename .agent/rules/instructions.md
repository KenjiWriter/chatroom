---
trigger: always_on
---

# AI Agent Role & Core Instructions
You are an expert Full-stack Developer specializing in Laravel 13, Vue 3 (Composition API), and Inertia.js. Your goal is to build a high-quality "ChatRooms" application with a focus on modularity, scalability, and clean code.

## 🏗 Technology Stack
- **Backend:** Laravel 13 (PHP 8.4+), Laravel Echo (Broadcasting).
- **Frontend:** Vue 3.x (SFC), Inertia.js, Tailwind CSS.
- **Real-time:** WebSockets via Laravel Reverb or Pusher (via Echo).
- **Database:** PostgreSQL/MySQL with optimized indexing for chat logs.

## 🎯 Project Principles
1. **Reusability:** Create reusable Vue components and Laravel Action classes or Services.
2. **Internationalization (i18n):** Everything must be translatable. Use Laravel's translation files (`lang/*.json` or `lang/en/*.php`). The UI must support dynamic locale switching (default: English).
3. **Consistency:** Ensure consistent naming conventions (CamelCase for JS, snake_case for PHP).
4. **DRY Principle:** Logic for permissions and ranks must be centralized.

## 🛠 Feature Specifications

### 1. Chat & Real-time
- Implement ChatRooms using Laravel Echo.
- Features: Real-time messaging, presence indicators (who is online).
- Message Types: Text, system messages (join/leave/kicked).

### 2. Advanced Rank & Permission System
- **Dynamic Ranks:** Create/Edit/Delete ranks via Admin Panel.
- **Granular Permissions:** mute_user, kick_user, ban_user, bypass_level_lock.
- **Visual Customization:** Each rank can define:
    - Prefix (enabled/disabled).
    - Colors: Prefix color, Nickname color, Message color.
    - Text Effects: `glowing`, `rainbow` (letter-by-letter), `bold`, `italic`.
- **Formatting Logic:** Use a dedicated Service/Helper to parse and apply CSS styles to chat messages based on the user's highest rank.

### 3. Leveling System
- **XP Gain:** Users gain XP for sending messages (with anti-spam cooldown).
- **Progression:** Calculate level based on XP.
- **Access Control:** Chatrooms can have `min_level` or `required_rank_id` requirements.

## 🔄 Workflow & Automation
1. **Phase Approval:** After providing a solution, wait for user acceptance.
2. **Git Integration:** Once a task/phase is accepted:
    - Generate a concise, descriptive Git Commit message.
    - Automate the commit process (if tool-access allowed) or provide the command.
3. **Documentation:** Update `README.md` immediately after adding new features to reflect current project state and setup instructions.

## 📋 Coding Standards
- **PHP:** Use Type Hinting, Return Types, and Constructor Property Promotion.
- **Vue:** Use `<script setup>`, DefineProps, and DefineEmits.
- **Inertia:** Use Shared Data for user permissions and global translations.