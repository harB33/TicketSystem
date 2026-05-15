# Staged a Ticket System 🎫

A high-fidelity, modern ticketing platform designed for seamless event discovery and ticket management. This system features a premium **glassmorphic UI**, responsive platform-specific views, and a robust PHP-based architecture.

## ✨ Features

- **Centralized Routing**: Implements a Front Controller pattern for secure and efficient request handling.
- **Platform-Specific UX**: Dedicated UI layouts for **Desktop** and **Mobile** devices, ensuring an optimal experience across all screens.
- **Event Discovery**: 
  - Featured events carousel.
  - Search by Artist or Arena.
  - Interactive seating and event details.
- **User Management**:
  - Secure Login & Registration.
  - Personalized user profiles.
  - Transaction history and ticket management.
- **Premium UI/UX**:
  - **Glassmorphism**: Elegant backdrop blurs and translucent surfaces.
  - **Animated Transitions**: Custom SVG-based loading screens and smooth page transitions.
  - **Styling**: Built with Tailwind CSS 4 and DaisyUI 5.
- **Admin Dashboard**: Comprehensive management interface for administrators.

## 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: 
  - Tailwind CSS 4
  - DaisyUI 5
  - Vanilla JavaScript
- **Tools**: 
  - npm for dependency management
  - PostCSS / Autoprefixer

## 🚀 Getting Started

### Prerequisites

- **XAMPP / WAMP** (or any PHP/MySQL environment)
- **Node.js & npm** (for Tailwind CSS builds)

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/harB33/TicketSystem.git
   cd TicketSystem
   ```

2. **Database Setup**:
   - Create a MySQL database (e.g., `ticket_db`).
   - Import the database schema (if provided in `src/ticket_db`).
   - Configure your credentials in `src/ticket_db/connectdb.php`.

3. **Install Dependencies**:
   ```bash
   npm install
   ```

4. **Watch CSS (Optional)**:
   If you are making styling changes, run the Tailwind watch script:
   ```bash
   npm run watch:tailwind
   ```

5. **Deploy**:
   Move the project folder to your local server's root (e.g., `htdocs` for XAMPP) and access it via:
   `http://localhost/AppDevTicketSystem/TicketSystem/src/`

## 📂 Project Structure

```text
TicketSystem/
├── src/
│   ├── index.php           # Front Controller (Entry Point)
│   ├── view/               # View Templates
│   │   ├── desktop/        # Desktop-specific UI
│   │   ├── mobile/         # Mobile-specific UI
│   │   └── view.php        # Central View Dispatcher
│   ├── ticket_db/          # Database Logic
│   ├── style/              # CSS & Tailwind Output
│   └── asset/              # Images, Icons, and Media
├── package.json            # Dependencies & Scripts
└── .env                    # Environment Variables
```

## 🎨 Design Philosophy

The HJJC Ticket System prioritizes **Visual Excellence** and **Interactive Design**. By utilizing modern CSS techniques like `backdrop-blur`, custom gradients, and micro-animations, the application provides a premium feel that rivals modern native apps.

## 👥 Development Team

- **[harB33](https://github.com/harB33)** - Front-End Developer
- **[adielaide-adi](https://github.com/adielaide-adi)** - Front-End & Back-End Developer
- **[jomariwamil1012-ai](https://github.com/jomariwamil1012-ai)** - Back-End Developer

---
*Built with ❤️ by Us.*
