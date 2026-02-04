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

- PHP 8.1 or higher

## Quick Start

```php
use JDZ\Authentication\Authentication;
use JDZ\Authentication\Connector\BasicConnector;

$auth = new Authentication();
$auth->addConnector(new BasicConnector('admin', 'secret123'));

$result = $auth->authenticate([
    'identifier' => 'admin',
    'password' => 'secret123',
]);

if ($result->isSuccess()) {
    echo "Welcome, " . $result->getUsername();
} else {
    echo "Error: " . $result->getMessage();
}
```

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
- Using BasicConnector for simple identifier/password authentication
- Testing various authentication scenarios (valid, invalid, missing credentials)
- Checking authentication status and error messages

**Use Case**: Simple applications with hardcoded or configuration-based credentials.

---

### 02-multiple-connectors.php
**Multiple Authentication Connectors**

Demonstrates:
- Adding multiple connectors to a single authentication instance
- How connectors are tried by priority order
- Authenticating different users with different credentials
- Converting result to array format

**Use Case**: Applications supporting multiple authentication methods or user sources.

---

### 03-database-authentication.php
**Database Authentication with PDO**

Demonstrates:
- Creating a custom connector by extending AbstractConnector
- Using PDO for database queries
- Storing and verifying hashed passwords
- Setting up and testing with SQLite (easily adaptable to MySQL/PostgreSQL)
- Proper SQL prepared statements for security

**Use Case**: Standard web applications with user accounts stored in a database.

**Key Points**:
- Uses `password_hash()` and `password_verify()` for secure password storage
- Demonstrates proper PDO usage with prepared statements
- Shows how to create custom connectors

---

### 04-error-handling.php
**Error Handling with Exceptions**

Demonstrates:
- Using the built-in AuthenticationException
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
- Advanced custom connector with additional features
- Loading user profile data during authentication
- Checking user account status (active/inactive)
- Populating AuthenticationResult with user details
- Production-ready MySQL connector implementation

**Use Case**: Full-featured applications requiring user profile data, account status checks, and multi-language support.

**Key Points**:
- Shows how to implement custom authenticate() method
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

The test suite includes **59 tests** with **143 assertions**:

- **AuthStatusEnumTest** (9 tests): Tests for the authentication status enum values and methods
- **AuthenticationResultTest** (12 tests): Tests for the authentication result object, factory methods, and toArray() conversion
- **AuthenticationTest** (13 tests): Tests for the main authentication class including credentials normalization, priority, and connector flow
- **BasicConnectorTest** (11 tests): Tests for the basic authentication connector including constructor validation and authentication scenarios
- **DatabaseConnectorTest** (14 tests): Tests for the database authentication connector using mocked DatabaseInterface

## Authentication Status

The library uses `AuthStatusEnum` for type-safe status handling:

| Status | Value | Description |
|--------|-------|-------------|
| `FAILURE` | 0 | Authentication failed (generic) |
| `SUCCESS` | 1 | Successful authentication |
| `EMPTY_IDENTIFIER` | 2 | Missing identifier (email/username) in credentials |
| `EMPTY_PASSWORD` | 3 | Missing password in credentials |
| `USER_NOT_FOUND` | 4 | User account not found |
| `INVALID_PASSWORD` | 5 | Invalid password |
| `USER_BANNED` | 6 | Account has been suspended |
| `USER_NOT_CONFIRMED` | 7 | Email not confirmed |
| `ACCOUNT_LOCKED` | 8 | Account locked due to failed attempts |

Each status provides:
- `value` - Returns the integer status code
- `message()` - Returns the descriptive error message
- `isSuccess()` - Returns true only for SUCCESS status
- `name` - The enum case name (e.g., "SUCCESS", "INVALID_PASSWORD")

Example usage:
```php
$result = $auth->authenticate($credentials);

if ($result->isSuccess()) {
    echo "User ID: " . $result->getUserId();
    echo "Email: " . $result->getEmail();
} else {
    echo "Status Code: " . $result->getStatus()->value;      // 5
    echo "Message: " . $result->getStatus()->message();      // "Invalid password"
    echo "Name: " . $result->getStatus()->name;              // "INVALID_PASSWORD"
}
```

## AuthenticationResult

The `AuthenticationResult` object provides the following methods:

```php
$result->isSuccess();       // bool - Check if authentication succeeded
$result->getStatus();       // AuthStatusEnum - Get the status enum
$result->getMessage();      // string - Get error message (or status message)
$result->getUserId();       // ?int - Get user ID (if available)
$result->getIdentifier();   // string - Get the identifier used
$result->getEmail();        // string - Get user email
$result->getUsername();     // string - Get username
$result->getFirstname();    // string - Get first name
$result->getLastname();     // string - Get last name
$result->getFullname();     // string - Get full name (or fallback to email/identifier)
$result->getType();         // string - Get connector type (e.g., "basic", "database")
$result->get($key);         // mixed - Get custom data
$result->toArray();         // array - Convert to array
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
