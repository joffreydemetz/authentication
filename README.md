# JDZ Authentication

Simple authentication library with support for multiple authentication connectors.

## Features

- Multiple authentication connectors support
- Type-safe authentication status enum
- PDO-based database authentication
- Basic authentication connector
- Extensible connector interface
- Comprehensive test suite
- Automatic password hashing
- Secure password verification

## Installation

```bash
composer require jdz/authentication
```

## Requirements

- PHP 8.0 or higher
- Composer

## Examples

All examples can be run directly from the command line:

**Note**: Examples 03 and 05 require PDO SQLite extension. If not available, you can:
1. Enable `pdo_sqlite` in your `php.ini`, OR
2. Modify the examples to use MySQL/PostgreSQL (see Database Setup Notes below)

Check available PDO drivers: `php -m | grep -i pdo`

See the [examples](examples/) directory for detailed examples:

- `01-basic-authentication.php` - Basic authentication
- `02-multiple-connectors.php` - Multiple authentication connectors
- `03-database-authentication.php` - Database authentication with PDO (requires PDO SQLite)
- `04-error-handling.php` - Error handling with exceptions
- `05-advanced-mysql.php` - Advanced MySQL authentication (requires PDO SQLite or MySQL)

Run example:

```bash
php examples/01-basic-authentication.php
```

### 01-basic-authentication.php
**Basic Authentication with BasicConnector**

Demonstrates:
- Creating a basic authentication instance
- Using BasicConnector for simple username/password authentication
- Testing various authentication scenarios (valid, invalid, missing credentials)
- Checking authentication status and error messages

**Use Case**: Simple applications with hardcoded or configuration-based credentials.

---

### 02-multiple-connectors.php
**Multiple Authentication Connectors**

Demonstrates:
- Adding multiple connectors to a single authentication instance
- How connectors are tried in reverse order
- Authenticating different users with different credentials
- Converting response to array format

**Use Case**: Applications supporting multiple authentication methods or user sources.

---

### 03-database-authentication.php
**Database Authentication with PDO**

Demonstrates:
- Creating a custom DatabaseConnector implementation
- Using PDO for database queries
- Storing and verifying hashed passwords
- Setting up and testing with SQLite (easily adaptable to MySQL/PostgreSQL)
- Proper SQL prepared statements for security

**Use Case**: Standard web applications with user accounts stored in a database.

**Key Points**:
- Uses `password_hash()` and `password_verify()` for secure password storage
- Demonstrates proper PDO usage with prepared statements
- Shows how to extend DatabaseConnector

---

### 04-error-handling.php
**Error Handling with Exceptions**

Demonstrates:
- Creating custom authentication exceptions
- Proper exception handling patterns
- Silent mode authentication (without exceptions)
- Custom error message mapping
- Using AuthStatusEnum for detailed error information

**Use Case**: Production applications requiring robust error handling and user-friendly error messages.

**Key Points**:
- Shows how to extract status codes and messages from exceptions
- Demonstrates both exception and return-value error handling patterns
- Custom error message mapping for better UX

---

### 05-advanced-mysql.php
**Advanced MySQL Authentication with User Data**

Demonstrates:
- Advanced DatabaseConnector with additional features
- Loading user profile data during authentication
- Checking user account status (active/inactive)
- Populating AuthenticationResponse with user details
- Production-ready MySQL connector implementation

**Use Case**: Full-featured applications requiring user profile data, account status checks, and multi-language support.

**Key Points**:
- Shows how to extend authenticate() method
- Demonstrates loading additional user data
- Includes account status validation
- Multi-language support example

---

## Database Setup Notes

For examples using databases:

**SQLite** No setup required - creates in-memory database automatically.

## Testing

```bash
# Run all tests
composer test

# Or use PHPUnit directly
vendor/bin/phpunit
```

The test suite includes **30 tests** with **65 assertions**:

- **AuthStatusEnumTest** (6 tests): Tests for the authentication status enum code() and message() methods
- **AuthenticationResponseTest** (3 tests): Tests for the authentication response object and toArray() conversion
- **AuthenticationTest** (7 tests): Tests for the main authentication class including empty credentials validation and connector flow
- **BasicConnectorTest** (7 tests): Tests for the basic authentication connector including constructor validation and authentication scenarios
- **DatabaseConnectorTest** (4 tests): Tests for the database authentication connector using anonymous classes

## Authentication Status

The library uses `AuthStatusEnum` for type-safe status handling:

| Status | Code | Description |
|--------|------|-------------|
| `FAILURE` | 0 | Authentication failed (initial status) |
| `SUCCESS` | 1 | Successful authentication |
| `EMPTY_USER` | 2 | Missing username in credentials |
| `EMPTY_PASS` | 3 | Missing password in credentials |
| `BAD_CREDENTIALS` | 4 | Account not found |
| `BAD_PASS` | 5 | Invalid password |

Each status provides:
- `code()` - Returns the integer status code
- `message()` - Returns the descriptive error message
- `name` - The enum case name (e.g., "SUCCESS", "BAD_PASS")

Example usage:
```php
$response = $auth->authenticate($credentials);

echo "Status Code: " . $response->status->code();     // 5
echo "Message: " . $response->status->message();      // "Invalid password"
echo "Name: " . $response->status->name;              // "BAD_PASS"
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.