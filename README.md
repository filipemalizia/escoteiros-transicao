<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# escoteiros-transicao

## Deploy - notas pra quem for mexer

- **Conectar via SSH / pasta do projeto no servidor**: dados de conexão
  (host, porta, usuário, caminho) são sensíveis e este repositório é
  público — ver `DEPLOY-PRIVADO.md` (arquivo local, fora do git) ou os
  Secrets do GitHub do repositório.
- **A CLI do SSH usa PHP 8.3, o projeto exige 8.4+**: tanto `php` quanto
  `lsphp` (os binários padrão do sistema) resolvem pra PHP 8.3.33, mas o
  `composer.json` exige `>= 8.4.1` — rodar `artisan`/`composer` direto dá
  erro de "Composer detected issues in your platform". O binário certo é:
  ```
  /opt/alt/php84/usr/bin/php
  ```
  Use sempre esse caminho completo por SSH, ex.:
  ```
  /opt/alt/php84/usr/bin/php artisan migrate:status
  /opt/alt/php84/usr/bin/php artisan migrate --force
  /opt/alt/php84/usr/bin/php artisan tinker
  ```
- **Rodar migrations em produção**: o normal é disparar a action **"Rodar
  migrations em produção (manual)"** na aba Actions do GitHub
  (`.github/workflows/rodar-migrations-producao.yml`) — ela roda
  `composer install` + `migrate --force` + rebuild de cache no servidor
  via SSH sozinha. Usar o SSH manual (acima) só se a action falhar ou pra
  debug.
- Mais contexto sobre a arquitetura de deploy (por que existem 2 workflows,
  o que cada um faz) está em `CONTEXTO.md`, seção "Deploy".
