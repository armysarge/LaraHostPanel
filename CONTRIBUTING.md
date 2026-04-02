# Contributing to LaraHostPanel

Thank you for considering contributing to LaraHostPanel! This is a self-hosted Laravel project manager, and community contributions help make it more capable and reliable for everyone.

## 📜 Code of Conduct

By participating in this project, you are expected to uphold our Code of Conduct:

- Use welcoming and inclusive language
- Be respectful of differing viewpoints and experiences
- Gracefully accept constructive criticism
- Focus on what is best for the community
- Show empathy towards other community members

## 🗺️ How Can I Contribute?

### 🐛 Reporting Bugs

Before creating a bug report, please check the issue list to avoid duplicates. When reporting a bug, include:

* A clear and descriptive title
* Exact steps to reproduce the problem
* What you expected to happen vs. what actually happened
* Your environment (OS, Docker version, Docker Compose version)
* Relevant logs from `docker compose logs` or the panel's deployment log
* Screenshots if applicable

### 💡 Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When submitting one, please include:

* A clear and descriptive title
* A detailed description of the proposed feature and its use case
* Any potential drawbacks or trade-offs
* Mock-ups or examples if applicable

Check the [Roadmap](README.md#roadmap) first — your idea may already be planned.

### 🔧 Pull Requests

1. Fork the repo and create your branch from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
2. Make your changes, adding tests where appropriate
3. If you've changed APIs, update the documentation
4. Ensure the test suite passes
5. Make sure your code follows the style guidelines below
6. Open a pull request against `main`

## 🏗️ Development Setup

### Requirements

- Docker >= 24
- Docker Compose v2
- Git

### Local Setup

1. Fork and clone the repo:
   ```bash
   git clone https://github.com/youruser/LaraHostPanel.git
   cd LaraHostPanel
   ```

2. Configure environment:
   ```bash
   cp .env.example .env
   ```
   Edit `.env` and set your DB credentials and ports.

3. Build and start the stack:
   ```bash
   docker compose up -d --build
   ```

4. Bootstrap Laravel:
   ```bash
   docker exec larahostpanel_app php artisan key:generate
   docker exec larahostpanel_app php artisan migrate --force
   docker exec larahostpanel_app php artisan db:seed
   ```

5. Open the panel at `http://localhost:8000`
   - Default email: `admin@larahostpanel.local`
   - Default password: `password`

See the [README Quick Start](README.md#quick-start) for more detail.

## 📚 Style Guidelines

### 💻 Code Style

- PHP: Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) conventions
- JavaScript/Vue: Use the project's ESLint and Prettier configuration
- Keep code modular — new features should fit within the existing Laravel service/controller/model structure
- Write meaningful commit messages (imperative mood: "Add X", "Fix Y", "Remove Z")

### 📝 Documentation Style

- Use clear, consistent Markdown
- Include code examples where helpful
- Update `README.md` if your change affects setup, configuration, or features

## 🧪 Testing

- Write feature or unit tests for new functionality using PHPUnit / Laravel's testing helpers
- Run the test suite before submitting:
  ```bash
  docker exec larahostpanel_app php artisan test
  ```
- Include edge-case coverage where relevant

## ❓ Questions?

Open a GitHub issue for support or discussion.

## 📖 License

By contributing, you agree that your contributions will be licensed under the AGPL License.