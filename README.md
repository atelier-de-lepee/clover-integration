# Clover OAuth Integration

A simple PHP application that demonstrates the OAuth 2.0 authentication flow with the Clover API. This application provides a straightforward way to authenticate users and manage access tokens for Clover's API services.

## Features

- Complete OAuth 2.0 authentication flow
- Token management (storage and refresh)
- Environment variable configuration
- Simple routing system
- Support for both production and sandbox environments

## Setup

1. Clone this repository
2. Install dependencies:
```
composer install
```
3. Create a `.env` file based on `.env.example` and set your Clover API credentials.
4. Run the application:
```
php -S localhost:8080 -t public
```


## Project Structure

- `public/index.php` - Main application file with routing and request handling
- `src/CloverOAuth.php` - OAuth authentication logic
- `src/TokenManager.php` - Token management and refresh functionality

## Available Routes

- `/` - Home page with navigation links
- `/auth` - Initiates the OAuth flow
- `/callback` - Handles the OAuth callback from Clover
- `/refresh` - Refreshes the access token using the stored refresh token

## Security

This application implements:
- Session management for token storage
- Environment variable protection
- Error handling and validation

## Contributing

Feel free to submit issues and enhancement requests!

---

*"Authentication is not about building walls, it's about building bridges - secure ones! 🌉 Keep coding, keep securing! 🔐✨"*