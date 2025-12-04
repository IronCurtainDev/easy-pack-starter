# Changelog

All notable changes to Easy Pack will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-11-30

### Added

#### Core Features
- Authentication system with login, register, logout, token refresh
- Email verification with OTP codes
- Password reset via email
- User profile management with avatar upload
- Device/session management with push token support
- Sanctum-based API authentication with custom PersonalAccessToken model

#### Push Notifications
- Firebase Cloud Messaging (FCM) integration
- Push notification management API
- Topic subscriptions
- Notification preferences per user
- Quiet hours support
- Notification categories and priorities

#### Media Management
- Spatie Media Library integration
- File upload, view, download endpoints
- Custom file keys for easy retrieval
- Automatic image conversions (thumbnails)

#### Settings System
- Configurable application settings
- Setting groups for organization
- Public guest settings endpoint

#### Admin Panel
- Web-based admin dashboard
- User management (CRUD)
- Role management (Spatie Permission)
- Permission management
- Device management
- Invitation system
- Push notification composer

#### API Documentation
- Automatic API documentation generation
- OpenAPI/Swagger output
- Postman collection export
- APIDoc HTML documentation

#### Developer Tools
- CRUD scaffolding commands (`make:crud`)
- Model generator with repository pattern
- API controller generator
- Admin controller generator

#### Version Compatibility
- Abstraction layer for Sanctum (TokenRepositoryInterface)
- Abstraction layer for Spatie Permission (PermissionServiceInterface)
- Abstraction layer for Spatie Media Library (MediaServiceInterface)
- Version detection helpers (`oxygen_version()`, `oxygen_supports()`, etc.)
- Multi-version support for dependencies

### Supported Versions
- Laravel: 11.x, 12.x
- PHP: 8.2+
- Laravel Sanctum: 4.x
- Spatie Laravel Permission: 5.x, 6.x
- Spatie Laravel Media Library: 10.x, 11.x

---

## Version History Template

### [X.Y.Z] - YYYY-MM-DD

#### Added
- New features

#### Changed
- Changes in existing functionality

#### Deprecated
- Soon-to-be removed features

#### Removed
- Removed features

#### Fixed
- Bug fixes

#### Security
- Security vulnerability fixes

---

## Upgrade Notes

See [UPGRADE.md](UPGRADE.md) for detailed upgrade instructions between versions.
